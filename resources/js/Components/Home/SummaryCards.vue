<script setup lang="ts">
import { computed } from 'vue';
import type { SummaryPayload, WeeklyInsight } from '@/types/dashboard';

const props = defineProps<{
    summary: SummaryPayload;
    insight: WeeklyInsight | null;
}>();

const cycleDays = computed(() =>
    props.insight
        ? new Intl.NumberFormat('es-MX', {
              maximumFractionDigits: 1,
          }).format(props.insight.avgCycleDays)
        : '',
);
</script>

<template>
    <section
        aria-label="Resumen de actividad"
        class="bg-surface-container-lowest shadow-card divide-surface-container grid grid-cols-1 divide-y rounded-xl sm:grid-cols-3 sm:divide-x sm:divide-y-0"
    >
        <div class="px-space-lg py-space-md flex flex-col gap-0.5">
            <p class="text-on-surface flex items-baseline gap-2">
                <span
                    class="text-headline-lg font-headline-lg tabular-nums"
                    >{{ summary.tasks.active }}</span
                >
                <span class="text-body-md font-body-md">tareas activas</span>
            </p>
            <p class="text-label-sm font-label-sm text-on-surface-variant">
                <span
                    :class="summary.tasks.urgent > 0 ? 'text-error' : ''"
                    class="tabular-nums"
                    >{{ summary.tasks.urgent }}</span
                >
                urgentes o de alta prioridad
            </p>
        </div>

        <div class="px-space-lg py-space-md flex flex-col gap-0.5">
            <p class="text-on-surface flex items-baseline gap-2">
                <span
                    class="text-headline-lg font-headline-lg tabular-nums"
                    >{{ insight?.completed ?? 0 }}</span
                >
                <span class="text-body-md font-body-md"
                    >resueltas esta semana</span
                >
            </p>
            <p class="text-label-sm font-label-sm text-on-surface-variant">
                <template v-if="insight">
                    Ciclo medio de {{ cycleDays }}
                    {{ insight.avgCycleDays === 1 ? 'día' : 'días' }}
                </template>
                <template v-else>Aún sin tareas resueltas</template>
            </p>
        </div>

        <div class="px-space-lg py-space-md flex flex-col gap-0.5">
            <p class="text-on-surface flex items-baseline gap-2">
                <span
                    class="text-headline-lg font-headline-lg tabular-nums"
                    >{{ summary.projects.active }}</span
                >
                <span class="text-body-md font-body-md"
                    >{{
                        summary.projects.active === 1
                            ? 'proyecto en progreso'
                            : 'proyectos en progreso'
                    }}</span
                >
            </p>
        </div>
    </section>
</template>
