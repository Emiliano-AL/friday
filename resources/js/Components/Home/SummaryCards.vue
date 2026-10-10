<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import DonutProgress from '@/Components/Home/DonutProgress.vue';
import type { SummaryPayload } from '@/types/dashboard';

defineProps<{
    summary: SummaryPayload;
}>();
</script>

<template>
    <section
        aria-label="Resumen de actividad"
        class="gap-space-lg grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3"
    >
        <article
            v-if="summary.sprint"
            class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex items-center rounded-xl"
        >
            <DonutProgress :percent="summary.sprint.coverage" :size="64" />
            <div class="gap-space-xs flex min-w-0 flex-col">
                <p
                    class="text-body-md font-body-md text-on-surface truncate font-medium"
                >
                    {{ summary.sprint.name }}
                </p>
                <p class="text-label-xs font-label-xs text-on-surface-variant">
                    {{ summary.sprint.coverage }}% de avance
                </p>
                <p class="text-label-xs font-label-xs text-on-surface-variant">
                    {{ summary.sprint.daysRemaining }}
                    {{
                        summary.sprint.daysRemaining === 1
                            ? 'día restante'
                            : 'días restantes'
                    }}
                </p>
            </div>
        </article>

        <article
            class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex items-center rounded-xl"
        >
            <div
                class="bg-surface-container text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
            >
                <AppIcon name="task_alt" :size="20" />
            </div>
            <div class="min-w-0">
                <p
                    class="font-headline-sm text-headline-sm text-on-surface leading-none"
                >
                    {{ summary.tasks.active }}
                </p>
                <p
                    class="font-label-xs text-label-xs text-on-surface-variant mt-1"
                >
                    tareas activas
                </p>
                <p
                    class="font-label-xs text-label-xs text-on-surface-variant mt-0.5"
                >
                    {{ summary.tasks.urgent }} urgentes / alta prioridad
                </p>
            </div>
        </article>

        <article
            class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex items-center rounded-xl"
        >
            <div
                class="bg-surface-container text-primary flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
            >
                <AppIcon name="folder" :size="20" />
            </div>
            <div class="min-w-0">
                <p
                    class="font-headline-sm text-headline-sm text-on-surface leading-none"
                >
                    {{ summary.projects.active }}
                </p>
                <p
                    class="font-label-xs text-label-xs text-on-surface-variant mt-1"
                >
                    proyectos en progreso
                </p>
            </div>
        </article>
    </section>
</template>
