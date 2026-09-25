<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { logout } from '@/routes';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { ShellUser } from '@/types/shell';

defineProps<{
    user: ShellUser;
}>();
</script>

<template>
    <div
        data-shell-popover
        role="dialog"
        aria-label="Menú de perfil"
        class="bg-surface-container-lowest py-space-xs shadow-popover fixed top-16 right-6 z-50 w-72 rounded-xl"
    >
        <div
            class="mb-space-xs gap-space-sm bg-surface-container-low/60 px-space-md py-space-md flex items-center rounded-t-xl"
        >
            <Avatar :name="user.name" :src="user.avatar" :size="40" />
            <div class="min-w-0 flex-1">
                <p
                    class="text-label-md text-on-surface truncate leading-tight font-semibold"
                >
                    {{ user.name }}
                </p>
                <p
                    class="text-label-xs text-on-surface-variant truncate leading-tight"
                >
                    {{ user.email }}
                </p>
            </div>
        </div>

        <div class="px-space-xs pb-space-2xs flex flex-col gap-0.5">
            <span
                title="Detalles del perfil (próximamente)"
                class="gap-space-sm px-space-md py-space-sm text-label-md text-on-surface-variant/60 flex cursor-not-allowed items-center rounded-lg"
            >
                <AppIcon name="person" />
                <span class="flex-1 truncate">Detalles del perfil</span>
                <span
                    class="bg-surface-container-high text-label-xs rounded px-1.5 py-0.5"
                    >Próximamente</span
                >
            </span>
            <span
                title="Ajustes de cuenta (próximamente)"
                class="gap-space-sm px-space-md py-space-sm text-label-md text-on-surface-variant/60 flex cursor-not-allowed items-center rounded-lg"
            >
                <AppIcon name="tune" />
                <span class="flex-1 truncate">Ajustes de cuenta</span>
                <span
                    class="bg-surface-container-high text-label-xs rounded px-1.5 py-0.5"
                    >Próximamente</span
                >
            </span>
        </div>

        <div
            class="mx-space-md my-space-2xs bg-surface-container-highest h-px"
        />

        <div class="px-space-xs pb-space-2xs">
            <Link
                :href="logout.url()"
                method="post"
                as="button"
                class="gap-space-sm px-space-md py-space-sm text-label-md text-error hover:bg-error-container/20 flex w-full items-center rounded-lg text-left font-medium transition-colors"
            >
                <AppIcon name="logout" />
                <span class="flex-1 truncate">Cerrar sesión</span>
            </Link>
        </div>
    </div>
</template>
