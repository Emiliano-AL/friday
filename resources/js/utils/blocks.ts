export interface EditorBlock {
    type: 'paragraph' | 'header' | 'list' | 'quote' | 'code';
    data: Record<string, unknown>;
}

export interface EditorBlocks {
    time?: number;
    blocks: EditorBlock[];
    version?: string;
}

export function escapeHtml(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const SAFE_INLINE_TAGS = /&lt;(\/?(b|i|strong|em|code|mark))&gt;/gi;
const BREAK_TAG = /&lt;br\s*\/?&gt;/gi;
const ANCHOR_OPEN = /&lt;a\s+href=&quot;(.*?)&quot;&gt;/gis;
const ANCHOR_CLOSE = /&lt;\/a&gt;/gi;
const ALLOWED_HREF = /^(https?:|mailto:|#)/i;

function unescapeAttr(value: string): string {
    return value
        .replace(/&amp;/g, '&')
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'");
}

function asText(value: unknown): string {
    return typeof value === 'string' ? value : '';
}

export function sanitizeInline(html: string): string {
    const escaped = escapeHtml(html);

    return escaped
        .replace(SAFE_INLINE_TAGS, '<$1>')
        .replace(BREAK_TAG, '<br>')
        .replace(ANCHOR_OPEN, (_match, href: string) => {
            const target = unescapeAttr(href).trim();

            if (!ALLOWED_HREF.test(target)) {
                return '';
            }

            return `<a href="${escapeHtml(target)}" target="_blank" rel="noopener noreferrer nofollow">`;
        })
        .replace(ANCHOR_CLOSE, '</a>');
}

export function isBlocksJson(value: unknown): value is string {
    if (typeof value !== 'string' || value.trim() === '') {
        return false;
    }

    try {
        const parsed = JSON.parse(value) as unknown;

        return (
            typeof parsed === 'object' &&
            parsed !== null &&
            Array.isArray((parsed as { blocks?: unknown }).blocks)
        );
    } catch {
        return false;
    }
}

export function plainTextToBlocks(text: string): EditorBlocks {
    return {
        blocks: [{ type: 'paragraph', data: { text } }],
    };
}

export function normalizeToBlocks(
    value: string | null | undefined,
): EditorBlocks {
    if (value === null || value === undefined || value.trim() === '') {
        return { blocks: [] };
    }

    if (isBlocksJson(value)) {
        const parsed = JSON.parse(value) as EditorBlocks;

        return { blocks: parsed.blocks ?? [] };
    }

    return plainTextToBlocks(value);
}

export function blocksToHtml(blocks: EditorBlocks): string {
    return blocks.blocks
        .map((block) => {
            switch (block.type) {
                case 'paragraph':
                    return `<p>${sanitizeInline(asText(block.data.text))}</p>`;
                case 'header': {
                    const level = Number(block.data.level ?? 2);
                    const clamped = Number.isFinite(level)
                        ? Math.min(4, Math.max(2, level))
                        : 2;

                    return `<h${clamped}>${sanitizeInline(asText(block.data.text))}</h${clamped}>`;
                }
                case 'list': {
                    const items = Array.isArray(block.data.items)
                        ? block.data.items
                        : [];
                    const tag = block.data.style === 'ordered' ? 'ol' : 'ul';
                    const lis = items
                        .map(
                            (item) =>
                                `<li>${sanitizeInline(asText(item))}</li>`,
                        )
                        .join('');

                    return `<${tag}>${lis}</${tag}>`;
                }
                case 'quote': {
                    const text = sanitizeInline(asText(block.data.text));
                    const caption = block.data.caption
                        ? `<cite>${sanitizeInline(asText(block.data.caption))}</cite>`
                        : '';

                    return `<blockquote><p>${text}</p>${caption}</blockquote>`;
                }
                case 'code':
                    return `<pre><code>${escapeHtml(asText(block.data.code))}</code></pre>`;
                default:
                    return '';
            }
        })
        .join('');
}

export function blocksToPlainText(blocks: EditorBlocks): string {
    return blocks.blocks
        .map((block) => {
            switch (block.type) {
                case 'paragraph':
                case 'header':
                    return asText(block.data.text);
                case 'list':
                    return Array.isArray(block.data.items)
                        ? block.data.items.map((item) => asText(item)).join(' ')
                        : '';
                case 'quote':
                    return asText(block.data.text);
                case 'code':
                    return asText(block.data.code);
                default:
                    return '';
            }
        })
        .filter((text) => text.trim() !== '')
        .join('\n');
}
