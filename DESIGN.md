---
name: Friday Productivity Workspace
colors:
  surface: '#f9f9ff'
  surface-dim: '#d3daef'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f1f3ff'
  surface-container: '#e9edff'
  surface-container-high: '#e1e8fd'
  surface-container-highest: '#dce2f7'
  on-surface: '#141b2b'
  on-surface-variant: '#464553'
  inverse-surface: '#293040'
  inverse-on-surface: '#edf0ff'
  outline: '#777585'
  outline-variant: '#c7c4d6'
  surface-tint: '#4e4ec9'
  primary: '#4241bc'
  on-primary: '#ffffff'
  primary-container: '#5b5bd6'
  on-primary-container: '#edeaff'
  inverse-primary: '#c1c1ff'
  secondary: '#555f70'
  on-secondary: '#ffffff'
  secondary-container: '#d6e0f4'
  on-secondary-container: '#596374'
  tertiary: '#804300'
  on-tertiary: '#ffffff'
  tertiary-container: '#a35700'
  on-tertiary-container: '#ffe8d9'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e2dfff'
  primary-fixed-dim: '#c1c1ff'
  on-primary-fixed: '#0a006b'
  on-primary-fixed-variant: '#3533b0'
  secondary-fixed: '#d9e3f7'
  secondary-fixed-dim: '#bdc7db'
  on-secondary-fixed: '#121c2a'
  on-secondary-fixed-variant: '#3d4757'
  tertiary-fixed: '#ffdcc3'
  tertiary-fixed-dim: '#ffb77e'
  on-tertiary-fixed: '#2f1500'
  on-tertiary-fixed-variant: '#6e3900'
  background: '#f9f9ff'
  on-background: '#141b2b'
  surface-variant: '#dce2f7'
typography:
  headline-xl:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.025em
  headline-xl-mobile:
    fontFamily: Inter
    fontSize: 26px
    fontWeight: '600'
    lineHeight: 34px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: -0.01em
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
    letterSpacing: -0.005em
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0em
  label-md:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: -0.005em
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0em
  label-xs:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.02em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-lg: 1.5rem
  margin: 1.5rem
  margin-mobile: 1rem
  space-2xs: 0.125rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 0.75rem
  space-lg: 1rem
  space-xl: 1.5rem
  space-2xl: 2rem
  space-3xl: 3rem
---

## Brand & Style

This design system delivers a calm, razor-sharp productivity workspace tailored for high-focus knowledge work, task orchestration, and cognitive clarity. Blending the refined minimalism of Things 3 with the technical precision and velocity of Linear, the interface operates as a neutral, unobtrusive canvas. 

The aesthetic is characterized by:
- Pure, light-reflective tonal layering that replaces heavy visual weight with breathability.
- High-contrast, scannable typography that establishes effortless hierarchy without visual clutter.
- Surgical precision in line work and structural borders, emphasizing structural discipline over decorative flourishes.
- An assertive electric indigo accent (`#5B5BD6`) deployed strategically to telegraph interactive states, key selections, and directional velocity.

The emotional signature is focused, orderly, and frictionless—eliminating decision fatigue while reinforcing user mastery and momentum.

## Colors

The palette is engineered for prolonged daylight and studio illumination, delivering immaculate readability through controlled contrast.

### Palette Architecture
- **Canvas Base (`#F8F9FA`)**: An ultra-clean, cool off-white that prevents screen glare while grounding work surfaces.
- **Card & Workspace Surface (`#FFFFFF`)**: Pure white primary containers that step forward naturally from the base canvas.
- **Tonal Layers (`#F1F3F5` & `#E9ECEF`)**: Subordinate tiers used for sidebars, grouped list regions, toolbars, and inactive tags.
- **Precision Boundaries (`#EAECF0` & `#E2E4E9`)**: Subtle hairpins that demarcate zones without fragmenting the unified plane.
- **Text Scale**:
  - `Text Primary` (`#111827`): Deep obsidian for titles, high-priority labels, and dense task descriptions.
  - `Text Secondary` (`#374151`): Balanced charcoal for contextual content, secondary metadata, and icon glyphs.
  - `Text Muted` (`#6B7280`): Slate neutral for passive timestamps, shortcuts, and placeholder text.
- **Accent & Interaction (`#5B5BD6`)**:
  - `Primary Accent` (`#5B5BD6`): Electric violet/indigo for primary CTAs, active indicator pills, and focus rings.
  - `Accent Tint` (`#EEF2FF`): Soft lilac/indigo tint for interactive hover states, selected chip backgrounds, and row highlights.
  - `Accent Hover` (`#4F46E5`): Deeper tone for active button engagement.

## Typography

The typographic engine uses Inter systematically across all contexts. Structural hierarchy is driven by tight line spacing, slight negative tracking at display scales, and deliberate weight shifts rather than dramatic point size expansions.

- **Headlines**: Tight letter-spacing (`-0.02em` to `-0.01em`) provides the clean density characteristic of Linear. Used for view headings, modal headers, and project anchors.
- **Body**: Standardized around `14px` (`body-md`) and `13px` (`body-sm`) to maximize information density while preserving scan paths across nested work items.
- **Labels & Badges**: Set in medium and semi-bold weights (`500` and `600`) to anchor tags, shortcuts, dates, and column headers. Micro labels (`label-xs`) leverage slight positive tracking for scannability at small sizes.

## Layout & Spacing

The layout model is driven by an 8px base rhythm augmented by a 4px sub-grid for micro-alignments (e.g., inline icons, badges, and segmented toggles).

### Structural Architecture
- **Workspaces & Sidebars**: A multi-pane fixed-fluid structure. Navigational sidebars remain pinned (240px–280px) with contextual sub-lists (e.g., project outline at 320px) transitioning into a fluid, auto-expanding primary work canvas.
- **Density Controls**: Vertical spacing within task lists, tables, and item nodes utilizes compact tokens (`space-sm` / `0.5rem` to `space-md` / `0.75rem`) to ensure optimal viewport utilization.
- **Responsive Adaptations**:
  - **Desktop (>= 1024px)**: Full multi-pane workflow, 24px margins, split detail panes.
  - **Tablet (768px - 1023px)**: Collapsible sidebar into a drawer overlay; main canvas takes full width with contextual slide-overs.
  - **Mobile (< 768px)**: Single column stream, unified 16px lateral padding, bottom-sheet inspector drawers, and floating action triggers.

## Elevation & Depth

Elevation eschews heavy drop shadows in favor of a low-elevation, perimeter-ruled hierarchy. Visual priority is communicated through pristine white surfaces resting atop `#F8F9FA`, framed by hairline structural borders.

- **Level 0 (Base Canvas)**: Background `#F8F9FA` with zero elevation.
- **Level 1 (Cards, Lists, Work Panes)**: Background `#FFFFFF`, bordered by 1px solid `#EAECF0`. Shadow: `0 1px 2px 0 rgba(16, 24, 40, 0.04)`.
- **Level 2 (Hovered Cards, Dropdowns, Popovers)**: Background `#FFFFFF`, border 1px solid `#E2E4E9`. Shadow: `0 4px 6px -1px rgba(16, 24, 40, 0.08), 0 2px 4px -2px rgba(16, 24, 40, 0.04)`.
- **Level 3 (Modals, Command K Palettes)**: Centered focal planes floating over a translucent veil (`rgba(17, 24, 39, 0.2)` with 4px backdrop blur). Border 1px solid `#E2E4E9`. Shadow: `0 20px 25px -5px rgba(16, 24, 40, 0.1), 0 8px 10px -6px rgba(16, 24, 40, 0.04)`.
- **Focus Indication**: Focused elements receive a clean double ring: `0 0 0 2px #FFFFFF, 0 0 0 4px rgba(91, 91, 214, 0.35)`.

## Shapes

The geometry reflects a balanced modern productivity standard: clean, controlled curvature that balances human softness with architectural discipline.

- **Base Radius (`rounded-md` / 6px to 8px)**: Applied to interactive primitives including buttons, form inputs, list row highlights, dropdown items, and tags.
- **Container Radius (`rounded-lg` / 10px to 12px)**: Applied to cards, panels, popover menus, slide-overs, and contextual modals.
- **Pill Radius (`rounded-full`)**: Reserved exclusively for dynamic status markers, notification counters, and circular utility buttons.
- **Border Rules**: All internal borders maintain a strict `1px` stroke. No heavy or decorative borders are permitted.

## Components

### Buttons
- **Primary**: Background `#5B5BD6`, text `#FFFFFF`, font weight `500`. Hover state `#4F46E5`. Active state `#4338CA`. Subtle inner border highlight: `inset 0 1px 0 rgba(255, 255, 255, 0.2)`. Radius `rounded-md`.
- **Secondary / Subtle**: Background `#FFFFFF`, text `#374151`, border `1px solid #EAECF0`. Hover state: background `#F8F9FA`, text `#111827`, border `#E2E4E9`.
- **Ghost**: Background transparent, text `#6B7280`. Hover state: background `#F1F3F5`, text `#111827`.

### Chips & Badges
- **Status Chips**: Height 22px, padding `2px 8px`, typography `label-xs`.
- **Active / Accent Tint**: Background `#EEF2FF`, border `1px solid rgba(91, 91, 214, 0.2)`, text `#5B5BD6`.
- **Neutral / Priority**: Background `#F1F3F5`, border `1px solid #E9ECEF`, text `#374151`.

### Input Fields & Controls
- **Inputs**: Background `#FFFFFF`, border `1px solid #EAECF0`, height 36px, padding `0 12px`, typography `body-md`. Focus state shifts border to `#5B5BD6` with a `2px` focus ring tint (`rgba(91, 91, 214, 0.15)`).
- **Checkboxes**: 16x16px square with 4px border radius. Inactive: border `1px solid #D1D5DB`, background `#FFFFFF`. Active: border `#5B5BD6`, background `#5B5BD6` with a white checkmark icon.

### Cards & Task Rows
- **Task Rows**: Seamless tabular items with zero margin, separated by a 1px baseline border `#EAECF0`. Hover state introduces a full row tint `#F8F9FA` with smooth 100ms transition. Active selection triggers `#EEF2FF` tint with a 2px left border strip in `#5B5BD6`.
- **Floating Cards**: Background `#FFFFFF`, border `1px solid #EAECF0`, radius `rounded-lg`, padding `16px`.

### Command Palette (Cmd+K)
- Centered modal container (`max-width: 640px`), background `#FFFFFF`, border `1px solid #E2E4E9`, radius `rounded-lg`, level 3 elevation.
- Dedicated search field with seamless inline styling, followed by high-density navigable option rows with keyboard shortcut indicator pills (`#F1F3F5` background with `#6B7280` text).