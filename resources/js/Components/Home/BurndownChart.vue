<script setup lang="ts">
import { computed } from 'vue';
import type { BurndownPoint } from '@/types/dashboard';

const props = defineProps<{
    points: BurndownPoint[];
}>();

const WIDTH = 320;
const HEIGHT = 140;
const PAD_X = 12;
const PAD_TOP = 12;
const PAD_BOTTOM = 16;

const maxValue = computed(() => {
    let max = 0;

    for (const point of props.points) {
        max = Math.max(max, point.ideal, point.done);
    }

    return max;
});

function toPolyline(values: number[]): string {
    if (values.length === 0) {
        return '';
    }

    const max = maxValue.value || 1;
    const innerWidth = WIDTH - PAD_X * 2;
    const innerHeight = HEIGHT - PAD_TOP - PAD_BOTTOM;

    return values
        .map((value, index) => {
            const x =
                PAD_X +
                (values.length === 1
                    ? innerWidth / 2
                    : (index / (values.length - 1)) * innerWidth);
            const y =
                PAD_TOP +
                innerHeight -
                (Math.min(value, max) / max) * innerHeight;

            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
}

const idealLine = computed(() => toPolyline(props.points.map((p) => p.ideal)));
const actualLine = computed(() => toPolyline(props.points.map((p) => p.done)));

const baselineY = HEIGHT - PAD_BOTTOM;

const startLabel = computed(() => formatDate(props.points[0]?.date));
const endLabel = computed(() =>
    formatDate(props.points[props.points.length - 1]?.date),
);

function formatDate(date: string | undefined): string {
    if (!date) {
        return '';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
    }).format(new Date(`${date}T00:00:00`));
}
</script>

<template>
    <figure class="gap-space-xs flex flex-col">
        <svg
            :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
            class="h-auto w-full"
            role="img"
            aria-label="Gráfico de avance del sprint: línea ideal frente a progreso real"
        >
            <line
                :x1="PAD_X"
                :y1="baselineY"
                :x2="WIDTH - PAD_X"
                :y2="baselineY"
                stroke-width="1"
                class="stroke-surface-container"
            />
            <polyline
                v-if="idealLine"
                :points="idealLine"
                fill="none"
                stroke-width="2"
                stroke-dasharray="5 4"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="stroke-outline-variant"
            />
            <polyline
                v-if="actualLine"
                :points="actualLine"
                fill="none"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="stroke-primary"
            />
        </svg>
        <figcaption
            class="text-label-xs font-label-xs text-on-surface-variant flex items-center justify-between"
        >
            <span>{{ startLabel }}</span>
            <span>{{ endLabel }}</span>
        </figcaption>
        <p
            v-if="points.length === 0"
            class="text-body-sm font-body-sm text-on-surface-variant italic"
        >
            Aún no hay datos de avance para este sprint.
        </p>
    </figure>
</template>
