<script setup lang="ts">
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
</script>

<template>
    <header
        class="px-margin-mobile py-space-lg lg:px-margin flex w-full items-center justify-between"
    >
        <div class="gap-space-sm flex items-center">
            <span class="bg-primary h-2 w-2 animate-pulse rounded-full" />
            <span
                class="text-label-xs text-on-surface-variant tracking-wider uppercase"
            >
                Friday Cloud OS
            </span>
        </div>
        <div
            class="gap-space-sm text-label-xs text-on-surface-variant flex items-center"
        >
            <AuthIcon name="lock" :size="14" class="text-primary" />
            <span>Conexión segura SSL</span>
        </div>
    </header>
</template>
