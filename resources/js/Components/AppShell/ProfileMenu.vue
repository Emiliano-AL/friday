<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { logout } from '@/routes';
import { edit as settingsEdit } from '@/routes/settings';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { ShellUser } from '@/types/shell';

defineProps<{
    user: ShellUser;
}>();

const emit = defineEmits<{
    close: [];
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
            <Link
                :href="`${settingsEdit.url()}#perfil`"
                @click="emit('close')"
                class="gap-space-sm px-space-md py-space-sm text-label-md text-on-surface hover:bg-surface-container flex items-center rounded-lg font-medium transition-colors"
            >
                <AppIcon name="person" />
                <span class="flex-1 truncate">Detalles del perfil</span>
            </Link>
            <Link
                :href="`${settingsEdit.url()}#seguridad`"
                @click="emit('close')"
                class="gap-space-sm px-space-md py-space-sm text-label-md text-on-surface hover:bg-surface-container flex items-center rounded-lg font-medium transition-colors"
            >
                <AppIcon name="tune" />
                <span class="flex-1 truncate">Ajustes de cuenta</span>
            </Link>
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
