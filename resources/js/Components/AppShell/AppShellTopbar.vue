<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { ShellPopover, ShellUser } from '@/types/shell';

defineProps<{
    user: ShellUser;
    activePopover: ShellPopover;
    hasUnreadNotifications?: boolean;
}>();

const emit = defineEmits<{
    toggleSidebar: [];
    openSearch: [];
    toggleNotifications: [];
    toggleProfile: [];
}>();
</script>

<template>
    <header
        class="bg-surface/85 shadow-card sticky top-0 z-40 backdrop-blur-xl"
    >
        <div class="gap-space-md px-space-xl flex h-14 w-full items-center">
            <button
                type="button"
                aria-label="Alternar barra lateral"
                class="p-space-xs text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface rounded transition-colors"
                @click="emit('toggleSidebar')"
            >
                <AppIcon name="menu_open" :size="20" />
            </button>

            <div class="min-w-0 flex-1">
                <button
                    type="button"
                    class="bg-surface-container-low px-space-md text-on-surface-variant hover:bg-surface-container flex h-9 w-full max-w-xl min-w-0 items-center rounded-lg transition-colors"
                    @click="emit('openSearch')"
                >
                    <AppIcon name="search" class="mr-space-sm shrink-0" />
                    <span class="text-body-md truncate text-left"
                        >Buscar o teclear comando...</span
                    >
                    <kbd
                        class="bg-surface-container-lowest text-label-xs text-on-surface-variant shadow-card hidden rounded px-1.5 py-0.5 sm:inline-flex"
                        >⌘K</kbd
                    >
                </button>
            </div>

            <div class="gap-space-sm flex items-center">
                <button
                    type="button"
                    aria-label="Notificaciones"
                    :aria-expanded="activePopover === 'notifications'"
                    data-shell-popover-trigger="notifications"
                    class="p-space-sm text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface relative rounded-lg transition-colors"
                    @click="emit('toggleNotifications')"
                >
                    <AppIcon name="notifications" :size="20" />
                    <span
                        v-if="hasUnreadNotifications"
                        class="bg-primary-container ring-surface absolute top-1.5 right-1.5 h-2 w-2 rounded-full ring-2"
                    />
                </button>

                <button
                    type="button"
                    aria-label="Ayuda"
                    class="p-space-sm text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface rounded-lg transition-colors"
                >
                    <AppIcon name="help_outline" :size="20" />
                </button>

                <button
                    type="button"
                    aria-label="Menú de perfil"
                    :aria-expanded="activePopover === 'profile'"
                    data-shell-popover-trigger="profile"
                    class="hover:ring-surface-container-highest rounded-full p-0.5 transition-all hover:ring-2"
                    @click="emit('toggleProfile')"
                >
                    <Avatar :name="user.name" :src="user.avatar" :size="32" />
                </button>
            </div>
        </div>
    </header>
</template>
