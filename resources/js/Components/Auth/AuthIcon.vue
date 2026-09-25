<script setup lang="ts">
export type AuthIconName =
    | 'mail'
    | 'lock'
    | 'user'
    | 'eye'
    | 'eye-off'
    | 'arrow-right'
    | 'check'
    | 'globe';

withDefaults(
    defineProps<{
        name: AuthIconName;
        size?: number | string;
        strokeWidth?: number;
    }>(),
    {
        size: 24,
        strokeWidth: 2,
    },
);
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="name === 'mail'">
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m3 7 9 6 9-6" />
        </template>
        <template v-else-if="name === 'lock'">
            <rect x="4" y="11" width="16" height="9" rx="2" />
            <path d="M8 11V7a4 4 0 1 1 8 0v4" />
        </template>
        <template v-else-if="name === 'user'">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c0-4 3.6-6 8-6s8 2 8 6" />
        </template>
        <template v-else-if="name === 'eye'">
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
            <circle cx="12" cy="12" r="3" />
        </template>
        <template v-else-if="name === 'eye-off'">
            <path d="M2 12s3.5-7 10-7c2 0 3.7.6 5.2 1.5M22 12s-3.5 7-10 7c-2 0-3.7-.6-5.2-1.5" />
            <path d="m3 3 18 18" />
        </template>
        <template v-else-if="name === 'arrow-right'">
            <path d="M5 12h14" />
            <path d="m13 6 6 6-6 6" />
        </template>
        <template v-else-if="name === 'check'">
            <path d="m5 12 5 5 9-10" />
        </template>
        <template v-else>
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18" />
            <path
                d="M12 3c3 3.5 3 14.5 0 18-3-3.5-3-14.5 0-18Z"
            />
        </template>
    </svg>
</template>
