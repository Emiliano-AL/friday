<script setup lang="ts">
import AppLogo from '@/Components/AppShell/AppLogo.vue';

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div
        class="bg-surface-container-lowest p-space-2xl shadow-surface-tint/5 relative z-10 flex w-full max-w-[440px] flex-col rounded-xl shadow-xl"
    >
        <div class="flex flex-col items-center text-center">
            <AppLogo size="lg" class="mb-space-lg shadow-sm" />
            <h1 class="text-headline-lg text-on-surface">{{ title }}</h1>
            <p
                v-if="subtitle"
                class="mt-space-xs text-body-md text-on-surface-variant"
            >
                {{ subtitle }}
            </p>
        </div>
        <div class="mt-space-2xl gap-space-md flex flex-col">
            <slot />
        </div>
    </div>
</template>
