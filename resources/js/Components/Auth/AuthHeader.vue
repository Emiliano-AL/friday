<script setup lang="ts">
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
</script>

<template>
    <header
        class="flex w-full items-center justify-between px-margin-mobile py-space-lg lg:px-margin"
    >
        <div class="flex items-center gap-space-sm">
            <span class="h-2 w-2 animate-pulse rounded-full bg-primary" />
            <span
                class="text-label-xs uppercase tracking-wider text-on-surface-variant"
            >
                Friday Cloud OS
            </span>
        </div>
        <div
            class="flex items-center gap-space-sm text-label-xs text-on-surface-variant"
        >
            <AuthIcon name="lock" :size="14" class="text-primary" />
            <span>Conexión segura SSL</span>
        </div>
    </header>
</template>
