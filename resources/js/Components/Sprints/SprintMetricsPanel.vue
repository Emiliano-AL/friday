<script setup lang="ts">
import type { SprintMetrics } from '@/types/sprint';

const props = defineProps<{
    metrics: SprintMetrics;
}>();
</script>

<template>
    <section
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex flex-col rounded-xl"
        aria-label="Métricas del sprint"
    >
        <div class="flex items-center justify-between">
            <span
                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1.5 font-semibold tracking-wider uppercase"
            >
                Cobertura de Tareas
            </span>
            <span class="text-label-xs font-label-xs text-on-surface-variant"
                >{{ metrics.total }} totales</span
            >
        </div>

        <div class="flex items-end justify-between gap-4">
            <div class="flex flex-col">
                <span class="text-headline-xl font-headline-xl text-on-surface"
                    >{{ metrics.coverage }}%</span
                >
                <span class="text-body-sm font-body-sm text-on-surface-variant"
                    >{{ metrics.done }} de {{ metrics.total }} tareas
                    resueltas</span
                >
            </div>
            <div
                v-if="metrics.total > 0"
                class="bg-surface-container-low h-2 flex-1 overflow-hidden rounded-full"
                role="progressbar"
                :aria-valuenow="metrics.coverage"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <div
                    class="bg-primary-container h-full rounded-full transition-all"
                    :style="{ width: `${metrics.coverage}%` }"
                />
            </div>
        </div>

        <ul
            v-if="metrics.breakdown.length > 0"
            class="gap-space-xs flex flex-col"
        >
            <li
                v-for="row in metrics.breakdown"
                :key="row.status"
                class="gap-space-sm flex items-center justify-between"
            >
                <span
                    class="text-body-sm font-body-sm text-on-surface flex items-center gap-2"
                >
                    <span
                        class="bg-surface-container-low text-label-xs font-label-xs text-on-surface-variant rounded px-1.5 py-0.5 font-semibold"
                        >{{ row.count }}</span
                    >
                    {{ row.label }}
                </span>
                <span
                    class="text-label-xs font-label-xs text-on-surface-variant"
                    >{{ row.percent }}%</span
                >
            </li>
        </ul>
        <p
            v-else
            class="text-body-sm font-body-sm text-on-surface-variant italic"
        >
            Aún no hay tareas en este sprint.
        </p>
    </section>
</template>
