<script setup lang="ts">
import Avatar from '@/Components/AppShell/Avatar.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { ShellUser } from '@/types/shell';

defineProps<{
    user: ShellUser;
}>();
</script>

<template>
    <div
        class="gap-space-sm bg-surface-container-lowest p-space-sm shadow-card flex items-center rounded-lg"
    >
        <Avatar :name="user.name" :src="user.avatar" :size="32" />
        <div class="min-w-0 flex-1">
            <p class="text-label-md text-on-surface truncate leading-tight">
                {{ user.name }}
            </p>
            <p
                class="text-label-xs text-on-surface-variant truncate leading-tight"
            >
                {{ user.email }}
            </p>
        </div>
        <button
            type="button"
            aria-label="Opciones de cuenta"
            class="p-space-xs text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface rounded transition-colors"
        >
            <AppIcon name="more_horiz" />
        </button>
    </div>
</template>
