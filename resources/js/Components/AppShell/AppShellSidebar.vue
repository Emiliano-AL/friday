<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import AppLogo from '@/Components/AppShell/AppLogo.vue';
import ContextSwitcher from '@/Components/AppShell/ContextSwitcher.vue';
import SidebarNav from '@/Components/AppShell/SidebarNav.vue';
import SidebarUserCard from '@/Components/AppShell/SidebarUserCard.vue';
import type { NavItem, ProjectSummary, ShellUser } from '@/types/shell';

defineProps<{
    user: ShellUser;
    projects: ProjectSummary[];
    navItems: NavItem[];
    footerItems: NavItem[];
}>();

const emit = defineEmits<{
    collapse: [];
    newTask: [];
}>();
</script>

<template>
    <div class="flex h-full flex-col">
        <div
            class="px-space-lg flex h-14 shrink-0 items-center justify-between"
        >
            <Link
                :href="home.url()"
                class="gap-space-sm flex items-center rounded-lg"
            >
                <AppLogo />
                <span class="text-headline-sm text-on-surface">Friday</span>
            </Link>
            <button
                type="button"
                aria-label="Colapsar menú lateral"
                class="p-space-xs text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface rounded transition-colors"
                @click="emit('collapse')"
            >
                <AppIcon name="dock_to_right" :size="20" />
            </button>
        </div>

        <div class="px-space-md py-space-xs">
            <ContextSwitcher :projects="projects" />
        </div>

        <div class="px-space-md py-space-sm">
            <button
                type="button"
                class="gap-space-xs bg-primary-container text-label-md text-on-primary shadow-card hover:bg-primary flex h-9 w-full items-center justify-center rounded-lg transition-colors"
                @click="emit('newTask')"
            >
                <AppIcon name="add" />
                <span>Nueva Tarea</span>
                <span
                    class="bg-on-primary/20 text-label-xs ml-auto rounded px-1.5 py-0.5 opacity-70"
                    >C</span
                >
            </button>
        </div>

        <SidebarNav :items="navItems" :footer-items="footerItems" />

        <div class="p-space-md">
            <SidebarUserCard :user="user" />
        </div>
    </div>
</template>
