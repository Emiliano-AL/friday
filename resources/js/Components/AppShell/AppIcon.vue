<script setup lang="ts">
withDefaults(
    defineProps<{
        name: string;
        size?: number;
        filled?: boolean;
    }>(),
    {
        size: 18,
        filled: false,
    },
);
</script>

<template>
    <span
        class="material-symbols-outlined"
        :style="{
            fontSize: `${size}px`,
            fontVariationSettings: filled
                ? `'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24`
                : undefined,
        }"
        aria-hidden="true"
        >{{ name }}</span
    >
</template>
