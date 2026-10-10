<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { WeeklyInsight } from '@/types/dashboard';

const props = defineProps<{
    insight: WeeklyInsight | null;
}>();

const formattedCycleDays = computed(() => {
    if (!props.insight) {
        return '';
    }

    return new Intl.NumberFormat('es-MX', {
        maximumFractionDigits: 1,
    }).format(props.insight.avgCycleDays);
});
</script>

<template>
    <section
        aria-label="Insight semanal"
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex items-center rounded-xl"
    >
        <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
            :class="
                insight
                    ? 'bg-tertiary-fixed text-on-tertiary-fixed'
                    : 'bg-surface-container text-on-surface-variant'
            "
        >
            <AppIcon name="insights" :size="20" />
        </div>
        <p
            v-if="insight"
            class="text-body-md font-body-md text-on-surface min-w-0"
        >
            Has resuelto {{ insight.completed }}
            {{ insight.completed === 1 ? 'tarea' : 'tareas' }} esta semana con
            un tiempo medio de ciclo de {{ formattedCycleDays }}
            {{ insight.avgCycleDays === 1 ? 'día' : 'días' }}.
        </p>
        <p
            v-else
            class="text-body-md font-body-md text-on-surface-variant italic"
        >
            Aún sin tareas resueltas esta semana.
        </p>
    </section>
</template>
