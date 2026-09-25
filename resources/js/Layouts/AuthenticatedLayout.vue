<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppShellSidebar from '@/Components/AppShell/AppShellSidebar.vue';
import AppShellTopbar from '@/Components/AppShell/AppShellTopbar.vue';
import ProfileMenu from '@/Components/AppShell/ProfileMenu.vue';
import CommandPalette from '@/Components/AppShell/CommandPalette.vue';
import NotificationsPopover from '@/Components/AppShell/NotificationsPopover.vue';
import type {
    CommandAction,
    NavItem,
    ProjectSummary,
    ShellPopover,
    ShellUser,
} from '@/types/shell';
import { home } from '@/routes';
import { index as projectsIndex } from '@/routes/projects';

const page = usePage();

const user = computed<ShellUser>(() => {
    const authUser = page.props.auth.user;

    return {
        name: authUser.name,
        email: authUser.email,
        avatar: authUser.avatar ?? null,
    };
});

const projects = computed<ProjectSummary[]>(() => page.props.projects ?? []);

const navItems: NavItem[] = [
    { label: 'Panel', icon: 'space_dashboard', to: home.url(), match: '/' },
    {
        label: 'Proyectos',
        icon: 'folder',
        to: projectsIndex.url(),
        match: '/projects',
    },
    { label: 'Mis Tareas', icon: 'check_circle', to: null, match: '' },
    { label: 'Sprints', icon: 'sprint', to: null, match: '' },
    { label: 'Backlog', icon: 'view_list', to: null, match: '' },
];

const footerItems: NavItem[] = [
    { label: 'Ajustes', icon: 'settings', to: null, match: '' },
];

// Below the lg breakpoint the sidebar becomes an overlay drawer.
const isMobile = ref(false);
const sidebarOpen = ref(false);
const collapsed = ref(false);

let mediaQueryList: MediaQueryList | null = null;

function syncViewport(event: MediaQueryListEvent | MediaQueryList): void {
    isMobile.value = event.matches;

    if (!event.matches) {
        sidebarOpen.value = false;
    }
}

onMounted(() => {
    mediaQueryList = window.matchMedia('(max-width: 1023px)');
    syncViewport(mediaQueryList);
    mediaQueryList.addEventListener('change', syncViewport);
});

onBeforeUnmount(() => {
    mediaQueryList?.removeEventListener('change', syncViewport);
});

const sidebarHidden = computed(() =>
    isMobile.value ? !sidebarOpen.value : collapsed.value,
);

const contentOffset = computed(() => !isMobile.value && !collapsed.value);

function toggleSidebar(): void {
    if (isMobile.value) {
        sidebarOpen.value = !sidebarOpen.value;
    } else {
        collapsed.value = !collapsed.value;
    }
}

const activePopover = ref<ShellPopover>(null);

function toggleProfile(): void {
    activePopover.value = activePopover.value === 'profile' ? null : 'profile';
}

function toggleNotifications(): void {
    activePopover.value =
        activePopover.value === 'notifications' ? null : 'notifications';
}

// Command palette (US3): client-only actions over existing routes.
const paletteOpen = ref(false);

function openPalette(): void {
    paletteOpen.value = true;
}

provide('friday:open-palette', openPalette);

const commandActions: CommandAction[] = [
    {
        label: 'Crear nueva tarea',
        icon: 'add_circle',
        shortcut: 'C',
        keywords: ['crear', 'nueva', 'tarea', 'task'],
        run: () => router.visit(projectsIndex.url()),
    },
    {
        label: 'Ir al Panel',
        icon: 'space_dashboard',
        keywords: ['panel', 'inicio', 'home', 'dashboard'],
        run: () => router.visit(home.url()),
    },
    {
        label: 'Ir a Proyectos',
        icon: 'folder',
        keywords: ['proyectos', 'projects'],
        run: () => router.visit(projectsIndex.url()),
    },
];

function isEditableTarget(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLElement &&
        (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable)
    );
}

function onGlobalKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        paletteOpen.value = !paletteOpen.value;
        activePopover.value = null;

        return;
    }

    if (event.key === 'Escape') {
        paletteOpen.value = false;
        activePopover.value = null;
        sidebarOpen.value = false;

        return;
    }

    if (
        event.key.toLowerCase() === 'c' &&
        !event.metaKey &&
        !event.ctrlKey &&
        !event.altKey &&
        !isEditableTarget(event.target)
    ) {
        paletteOpen.value = true;
    }
}

function onDocumentClick(event: MouseEvent): void {
    if (activePopover.value === null) {
        return;
    }

    const target = event.target as HTMLElement | null;

    if (
        target?.closest('[data-shell-popover]') ||
        target?.closest('[data-shell-popover-trigger]')
    ) {
        return;
    }

    activePopover.value = null;
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick, true);
    document.addEventListener('keydown', onGlobalKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick, true);
    document.removeEventListener('keydown', onGlobalKeydown);
});
</script>

<template>
    <div
        class="friday-focus-scope bg-surface font-inter text-on-surface selection:bg-primary-container selection:text-on-primary min-h-screen"
    >
        <aside
            class="bg-surface-container-low shadow-card fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-200"
            :class="sidebarHidden ? '-translate-x-full' : 'translate-x-0'"
            aria-label="Navegación principal"
        >
            <AppShellSidebar
                :user="user"
                :projects="projects"
                :nav-items="navItems"
                :footer-items="footerItems"
                @collapse="toggleSidebar"
                @new-task="openPalette"
            />
        </aside>

        <div
            v-if="isMobile && sidebarOpen"
            class="bg-inverse-surface/20 fixed inset-0 z-40 backdrop-blur-sm"
            @click="sidebarOpen = false"
        />

        <div
            class="flex min-h-screen flex-col transition-[padding] duration-200"
            :class="contentOffset ? 'lg:pl-64' : 'lg:pl-0'"
        >
            <AppShellTopbar
                :user="user"
                :active-popover="activePopover"
                @toggle-sidebar="toggleSidebar"
                @toggle-notifications="toggleNotifications"
                @toggle-profile="toggleProfile"
                @open-search="openPalette"
            />

            <main class="px-space-xl pb-space-2xl pt-space-md flex-1">
                <slot />
            </main>
        </div>

        <ProfileMenu v-if="activePopover === 'profile'" :user="user" />
        <NotificationsPopover v-if="activePopover === 'notifications'" />
        <CommandPalette
            :open="paletteOpen"
            :actions="commandActions"
            @close="paletteOpen = false"
        />
    </div>
</template>
