<script setup lang="ts">
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
            <div
                class="mb-space-lg bg-primary-container/10 text-primary-container flex h-12 w-12 items-center justify-center rounded-xl shadow-sm"
            >
                <svg
                    class="h-6 w-6"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M4 4h7a4 4 0 0 1 4 4v1H8a1 1 0 0 0-1 1v10H4V4z" />
                    <path
                        d="M13 10h6a1 1 0 0 1 1 1v2a4 4 0 0 1-4 4h-3v-7z"
                        opacity="0.6"
                    />
                    <circle cx="16.5" cy="18.5" r="2.5" />
                </svg>
            </div>
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
