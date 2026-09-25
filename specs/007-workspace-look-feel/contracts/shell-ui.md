# Contract: Shell UI (workspace autenticado)

**Feature**: 007-workspace-look-feel
**Date**: 2026-09-25
**Type**: UI contract — defines the component interfaces and the Inertia props the authenticated shell exposes. This is the integration surface between the Laravel server (shared props), the layout, and the shell components.

## 1. Shared props contract (server → layout)

`HandleInertiaRequests` shares, for authenticated requests:

```ts
page.props = {
  name: string;                        // app name (existing)
  auth: { user: User | null };         // existing, unchanged
  projects: ProjectSummary[];          // NEW — only when authenticated
}

interface ProjectSummary {
  id: number;
  title: string;
}
```

Rules:

- `projects` is the authenticated user's projects, alphabetical by `title`, max 50. `[]` when the user has none.
- Guests (unauthenticated) receive **no** `projects` key (or `null`); guest pages are unaffected.

## 2. Layout composition contract

`resources/js/Layouts/AuthenticatedLayout.vue` composes the shell regions. Pages keep consuming it exactly as today (default slot for content; `#header` slot is deprecated — new shell renders its own header; existing `#header` usages keep rendering but pages are migrated to in-content headers in this feature where applicable).

```vue
<AuthenticatedLayout> <!-- pages: unchanged usage -->
  <!-- page content (slot) renders inside <main> -->
</AuthenticatedLayout>
```

Regions (DOM order + semantics):

| Region   | Element                                     | Content                                                                      |
| -------- | ------------------------------------------- | ---------------------------------------------------------------------------- |
| Sidebar  | `<aside aria-label="Navegación principal">` | logo + collapse, ContextSwitcher, "Nueva Tarea", SidebarNav, SidebarUserCard |
| Topbar   | `<header aria-label="Barra superior">`      | sidebar toggle, search trigger (⌘K), notifications, help, profile avatar     |
| Content  | `<main id="main-content">`                  | page slot                                                                    |
| Overlays | fixed layers                                | CommandPalette, NotificationsPopover, ProfileMenu, mobile drawer veil        |

The layout owns the client-only state: `sidebarOpen` (mobile drawer), `sidebarCollapsed` (desktop, in-memory), `paletteOpen`, `activePopover` (`'notifications' \| 'profile' \| null`).

## 3. Component interfaces

### `AppIcon.vue`

```ts
defineProps<{
    name: string; // Material Symbols ligature name, e.g. 'folder', 'space_dashboard'
    size?: number; // px, default 18
    filled?: boolean; // font-variation-settings FILL 1, default false
}>();
```

### `SidebarNav.vue`

```ts
interface NavItem {
    label: string; // 'Panel' | 'Proyectos' | 'Mis Tareas' | 'Sprints' | 'Backlog' | 'Ajustes'
    icon: string; // Material Symbols name
    to: Route | null; // Wayfinder route when enabled; null when "Próximamente"
    match: string; // URL prefix for active state, e.g. '/projects'
    disabled?: boolean; // true when to === null
    badge?: number; // optional count; only from real data (none in this iteration)
}

defineProps<{ items: NavItem[] }>();
// active state: exactly one item, derived from current URL
// disabled items render with "Próximamente" indicator and never navigate
```

### `ContextSwitcher.vue`

```ts
defineProps<{
    projects: ProjectSummary[];
    current?: { id: number; title: string } | null; // matched from URL when on a project page
}>();
// opens a dropdown listing projects (alphabetical) → navigates to project.show
// empty state: CTA to projects.create
```

### `SidebarUserCard.vue`

```ts
defineProps<{
    user: { name: string; email: string; avatar?: string | null };
}>();
// avatar fallback: initials of name on primary-container background
```

### `AppShellTopbar.vue`

```ts
defineProps<{
    user: { name: string; email: string; avatar?: string | null };
    hasUnreadNotifications?: boolean; // false in this iteration — no indicator rendered
}>();
// emits: toggle-sidebar, open-palette, toggle-notifications, toggle-profile
```

### `CommandPalette.vue`

```ts
interface CommandAction {
    label: string;
    icon: string;
    shortcut?: string; // display hint only
    keywords: string[];
    run: () => void; // Wayfinder navigation in this iteration
}

defineProps<{ actions: CommandAction[] }>();
// open: ⌘K / Ctrl+K (global, prevented when typing in inputs for 'C' key), "Comandos rápidos" button, topbar search
// filter: case-insensitive match on label + keywords
// keyboard: ↑/↓ move, Enter runs, ESC closes (focus returns to trigger)
// rendered as centered modal over translucent veil (elevation level 3)
```

Initial action set (all navigation, client-only):

| Action            | Destination                            |
| ----------------- | -------------------------------------- |
| Crear nueva tarea | Proyectos (where task creation exists) |
| Ir al Panel       | home                                   |
| Ir a Proyectos    | projects.index                         |

### `NotificationsPopover.vue`

```ts
defineProps<{ open: boolean }>();
// this iteration: fixed empty state copy "Sin notificaciones"; no list data, no "mark read"
```

### `ProfileMenu.vue`

```ts
defineProps<{
    user: { name: string; email: string; avatar?: string | null };
}>();
// sections: identity (name/email) → items "Detalles del perfil" (disabled, "Próximamente"),
// "Ajustes de cuenta" (disabled, "Próximamente") → divider → "Cerrar sesión" (POST logout, works)
```

### `HomeCanvas.vue` (page-level, used by AppHome)

No props. Renders the welcome canvas per FR-010: context bar (breadcrumbs "Mi Espacio / Lienzo en Blanco", "Borrador" chip, "Comandos rápidos" + "Añadir bloque" buttons), centered hero ("Espacio listo para crear" + suggested action cards), status footer ("Sincronizado en la nube • Friday v2.4"). Buttons open the palette / are inert per FR-012 (no simulated behavior).

## 4. Visual contract (tokens)

All shell surfaces resolve to `DESIGN.md` tokens via the Tailwind v4 `@theme` map in `resources/css/app.css` (colors/text/spacing already present; radius + shadows added by this feature):

- Surface base `bg-surface` (`#F9F9FF`); sidebar `bg-surface-container-low`; cards/popovers `bg-surface-container-lowest` (`#FFFFFF`).
- Elevation: level 1 `shadow-card` (`0 1px 2px rgba(16,24,40,.04)`), level 2 `shadow-popover`, level 3 `shadow-modal` + veil `bg-inverse-surface/20 backdrop-blur`.
- Radius: buttons/inputs `rounded-lg`, containers/popovers `rounded-xl`, pills `rounded-full` (per DESIGN scale).
- Type: `text-headline-lg` hero, `text-label-md` nav/buttons, `text-label-xs` badges/hints; font Inter.
- Active nav item: `bg-surface-container-lowest` + semibold + `shadow-card`; hover: `bg-surface-container-high`.
- Focus: existing `.friday-focus-scope` ring pattern applied at shell root.

## 5. Behavior contract (interactions)

| Interaction                            | Behavior                                                                                  |
| -------------------------------------- | ----------------------------------------------------------------------------------------- |
| ⌘K / Ctrl+K anywhere                   | toggle palette; `preventDefault`; ignored while palette open                              |
| `C` (not in input)                     | open palette                                                                              |
| Sidebar toggle (topbar/sidebar button) | desktop: collapse/expand (animated, content reflows); mobile: open/close drawer with veil |
| ESC                                    | close palette/popovers/drawer; focus returns to trigger                                   |
| Click outside popover                  | closes it                                                                                 |
| Click disabled nav item                | no navigation, no request                                                                 |
| Notifications bell                     | opens empty-state popover; no unread dot (no real data)                                   |
| "Cerrar sesión"                        | POST logout → redirect to login (existing behavior preserved)                             |

## 6. Non-goals (explicit)

- No dark mode (tokens ship light-only).
- No backend for search, notifications, or global task creation.
- No persistence of sidebar state.
- No restyle of `Projects/*` page internals (mounted in shell as-is).
- Guest pages (`GuestLayout`, Auth/*) untouched.
