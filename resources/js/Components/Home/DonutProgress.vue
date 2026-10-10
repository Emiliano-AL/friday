<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        percent: number;
        size?: number;
    }>(),
    {
        size: 64,
    },
);

const STROKE_WIDTH = 8;

const clampedPercent = computed(() =>
    Math.min(Math.max(Math.round(props.percent), 0), 100),
);

const radius = computed(() => (props.size - STROKE_WIDTH) / 2);

const circumference = computed(() => 2 * Math.PI * radius.value);

const dashOffset = computed(
    () => circumference.value * (1 - clampedPercent.value / 100),
);
</script>

<template>
    <div
        class="relative inline-flex shrink-0 items-center justify-center"
        :style="{ width: `${size}px`, height: `${size}px` }"
        role="progressbar"
        :aria-valuenow="clampedPercent"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-label="`Avance: ${clampedPercent}%`"
    >
        <svg :width="size" :height="size" class="-rotate-90">
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke-width="8"
                class="stroke-surface-container"
            />
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke-width="8"
                stroke-linecap="round"
                class="stroke-primary transition-all duration-500"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
            />
        </svg>
        <span
            class="text-label-md font-label-md text-on-surface absolute font-semibold"
            >{{ clampedPercent }}%</span
        >
    </div>
</template>
