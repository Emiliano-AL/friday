<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { ProjectKpis } from '@/types/project';

const props = defineProps<{
    kpis: ProjectKpis;
}>();

const kpiItems: {
    icon: string;
    valueKey: keyof ProjectKpis;
    label: string;
}[] = [
    {
        icon: 'donut_large',
        valueKey: 'activeProjects',
        label: 'Proyectos en marcha',
    },
    {
        icon: 'task_alt',
        valueKey: 'completedSprintsThisQuarter',
        label: 'Sprints completados este trimestre',
    },
];

const upcomingItems = [
    { icon: 'percent', label: 'Entregas a tiempo' },
    { icon: 'bolt', label: 'Velocidad de equipo' },
];
</script>

<template>
    <section
        class="bg-surface-container-lowest shadow-card mt-space-md gap-space-lg p-space-lg flex flex-col items-center justify-between rounded-xl lg:flex-row"
    >
        <div class="gap-space-xl flex flex-wrap items-center">
            <template v-for="(kpi, index) in kpiItems" :key="kpi.icon">
                <div
                    v-if="index > 0"
                    class="bg-surface-container hidden h-8 w-px sm:block"
                />
                <div class="gap-space-sm flex items-center">
                    <div
                        class="bg-surface-container text-primary flex h-10 w-10 items-center justify-center rounded-lg"
                    >
                        <AppIcon :name="kpi.icon" :size="20" />
                    </div>
                    <div>
                        <p
                            class="font-headline-sm text-headline-sm text-on-surface leading-none"
                        >
                            {{ props.kpis[kpi.valueKey] }}
                        </p>
                        <p
                            class="font-label-xs text-label-xs text-on-surface-variant mt-1"
                        >
                            {{ kpi.label }}
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <div class="gap-space-sm flex w-full flex-col sm:flex-row lg:w-auto">
            <div
                v-for="item in upcomingItems"
                :key="item.icon"
                title="Disponible próximamente"
                class="bg-surface-container-low gap-space-sm p-space-md flex w-full items-center rounded-lg opacity-80 lg:w-auto"
            >
                <div
                    class="border-outline-variant text-on-surface-variant flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border"
                >
                    <AppIcon :name="item.icon" :size="20" />
                </div>
                <div>
                    <p
                        class="font-label-xs text-label-xs text-on-surface-variant leading-none"
                    >
                        Próximamente
                    </p>
                    <p class="font-label-xs text-label-xs text-on-surface mt-1">
                        {{ item.label }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
