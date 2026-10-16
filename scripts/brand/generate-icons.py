"""Generate the Eunox brand icons (favicons, home screen icons, mail logo).

Run once whenever the mark changes, then commit the files in public/:

    python3 -I scripts/brand/generate-icons.py

Requires Google Chrome (headless rendering of the SVG) and Pillow.
"""

import base64
import subprocess
import sys
import tempfile
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
PUBLIC = ROOT / "public"
CHROME = "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"

BRAND = "#5B5BD6"
SYMBOL = "#FFFFFF"

# The mark from AppLogo.vue: a 24-unit symbol drawn at 16/28 of a 28-unit tile.
# In the app the tile's radius (rounded-lg = 1rem on 28px) clamps to a full circle.
MARK = (
    '<path d="M4 4h7a4 4 0 0 1 4 4v1H8a1 1 0 0 0-1 1v10H4V4z"/>'
    '<path d="M13 10h6a1 1 0 0 1 1 1v2a4 4 0 0 1-4 4h-3v-7z" opacity="0.6"/>'
    '<circle cx="16.5" cy="18.5" r="2.5"/>'
)

# Chrome refuses to lay out windows narrower than ~500px, so render big and downscale.
RENDER_SIZE = 512


def svg(rounded: bool) -> str:
    radius = ' rx="14"' if rounded else ""

    return (
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28 28">'
        f'<rect width="28" height="28"{radius} fill="{BRAND}"/>'
        f'<g transform="translate(6 6) scale(0.6667)" fill="{SYMBOL}">{MARK}</g>'
        "</svg>"
    )


def render(svg_text: str, workdir: Path) -> Image.Image:
    encoded = base64.b64encode(svg_text.encode()).decode()
    page = workdir / "mark.html"
    shot = workdir / "mark.png"
    page.write_text(
        '<html><body style="margin:0;background:transparent">'
        f'<img src="data:image/svg+xml;base64,{encoded}" '
        f'width="{RENDER_SIZE}" height="{RENDER_SIZE}" style="display:block">'
        "</body></html>"
    )

    subprocess.run(
        [
            CHROME,
            "--headless=new",
            "--disable-gpu",
            "--hide-scrollbars",
            "--default-background-color=00000000",
            f"--user-data-dir={workdir / 'profile'}",
            f"--window-size={RENDER_SIZE},{RENDER_SIZE}",
            f"--screenshot={shot}",
            page.as_uri(),
        ],
        check=True,
        capture_output=True,
    )

    image = Image.open(shot).convert("RGBA")

    return image.crop((0, 0, RENDER_SIZE, RENDER_SIZE))


def save_png(image: Image.Image, size: int, path: Path, opaque: bool = False) -> None:
    resized = image.resize((size, size), Image.LANCZOS)

    if opaque:
        resized = resized.convert("RGB")

    path.parent.mkdir(parents=True, exist_ok=True)
    resized.save(path, optimize=True)


def main() -> int:
    favicon_svg = PUBLIC / "favicon.svg"
    favicon_svg.write_text(svg(rounded=True) + "\n")

    with tempfile.TemporaryDirectory() as tmp:
        workdir = Path(tmp)
        rounded = render(svg(rounded=True), workdir)
        full_bleed = render(svg(rounded=False), workdir)

    save_png(full_bleed, 180, PUBLIC / "apple-touch-icon.png", opaque=True)
    save_png(full_bleed, 192, PUBLIC / "icon-192.png")
    save_png(full_bleed, 512, PUBLIC / "icon-512.png")
    save_png(rounded, 96, PUBLIC / "images" / "eunox-mail-logo.png")

    rounded.resize((256, 256), Image.LANCZOS).save(
        PUBLIC / "favicon.ico", sizes=[(16, 16), (32, 32), (48, 48)]
    )

    for path in [
        favicon_svg,
        PUBLIC / "favicon.ico",
        PUBLIC / "apple-touch-icon.png",
        PUBLIC / "icon-192.png",
        PUBLIC / "icon-512.png",
        PUBLIC / "images" / "eunox-mail-logo.png",
    ]:
        detail = "" if path.suffix == ".svg" else " %sx%s" % Image.open(path).size
        print(f"{path.relative_to(ROOT)}{detail} ({path.stat().st_size} bytes)")

    return 0


if __name__ == "__main__":
    sys.exit(main())
