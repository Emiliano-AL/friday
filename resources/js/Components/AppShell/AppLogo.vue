<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        size?: 'sm' | 'lg';
    }>(),
    {
        size: 'sm',
    },
);

const sizes = {
    sm: { box: 'h-7 w-7 rounded-lg', icon: 'h-4 w-4' },
    lg: { box: 'h-12 w-12 rounded-xl', icon: 'h-7 w-7' },
};
</script>

<template>
    <span
        class="bg-primary-container text-on-primary flex shrink-0 items-center justify-center"
        :class="sizes[props.size].box"
    >
        <svg
            :class="sizes[props.size].icon"
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
    </span>
</template>
