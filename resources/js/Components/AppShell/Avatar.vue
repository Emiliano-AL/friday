<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        src?: string | null;
        size?: number;
    }>(),
    {
        size: 32,
    },
);

const initials = computed(() =>
    props.name
        .trim()
        .split(/\s+/)
        .map((word) => word[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <img
        v-if="src"
        :src="src"
        :alt="`Avatar de ${name}`"
        class="shrink-0 rounded-full object-cover"
        :style="{ width: `${size}px`, height: `${size}px` }"
    />
    <span
        v-else
        :aria-label="`Avatar de ${name}`"
        class="bg-primary-container text-on-primary flex shrink-0 items-center justify-center rounded-full font-semibold"
        :style="{
            width: `${size}px`,
            height: `${size}px`,
            fontSize: `${Math.round(size * 0.4)}px`,
        }"
        >{{ initials }}</span
    >
</template>
