<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import BurndownChart from '@/Components/Home/BurndownChart.vue';
import { show as showSprint } from '@/routes/projects/sprints';
import type { SprintPanelPayload } from '@/types/dashboard';

defineProps<{
    sprint: SprintPanelPayload;
}>();
</script>

<template>
    <section
        aria-label="Sprint activo"
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex flex-col rounded-xl"
    >
        <header class="gap-space-md flex items-start justify-between">
            <div class="gap-space-xs flex min-w-0 flex-col">
                <h2
                    class="text-headline-sm font-headline-sm text-on-surface line-clamp-2"
                >
                    {{ sprint.name }}
                </h2>
                <p
                    class="text-label-xs font-label-xs text-on-surface-variant truncate"
                >
                    {{ sprint.project.title }}
                </p>
            </div>
            <span
                class="bg-surface-container text-label-xs font-label-xs text-on-surface-variant shrink-0 rounded-full px-2.5 py-1"
            >
                {{ sprint.daysRemaining }}
                {{
                    sprint.daysRemaining === 1
                        ? 'día restante'
                        : 'días restantes'
                }}
            </span>
        </header>

        <p
            v-if="sprint.goal"
            class="text-body-sm font-body-sm text-on-surface-variant line-clamp-2"
        >
            {{ sprint.goal }}
        </p>

        <div class="flex flex-col">
            <span class="text-headline-sm font-headline-sm text-on-surface">
                {{ sprint.done }} de {{ sprint.total }} resueltas
            </span>
            <span
                class="text-label-xs font-label-xs text-on-surface-variant mt-0.5"
            >
                {{ sprint.coverage }}% de cobertura
            </span>
        </div>

        <div class="gap-space-xs flex flex-col">
            <div class="gap-space-md flex items-center justify-between">
                <span
                    class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1.5 font-semibold tracking-wider uppercase"
                >
                    Avance del sprint
                </span>
                <span
                    class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-3"
                >
                    <span class="flex items-center gap-1">
                        <svg width="14" height="2" aria-hidden="true">
                            <line
                                x1="0"
                                y1="1"
                                x2="14"
                                y2="1"
                                stroke-width="2"
                                stroke-dasharray="3 2"
                                class="stroke-outline-variant"
                            />
                        </svg>
                        Ideal
                    </span>
                    <span class="flex items-center gap-1">
                        <svg width="14" height="2" aria-hidden="true">
                            <line
                                x1="0"
                                y1="1"
                                x2="14"
                                y2="1"
                                stroke-width="2"
                                class="stroke-primary"
                            />
                        </svg>
                        Real
                    </span>
                </span>
            </div>
            <BurndownChart :points="sprint.burndown.points" />
        </div>

        <footer class="pt-space-xs">
            <Link
                :href="showSprint.url([sprint.project.id, sprint.id])"
                class="text-label-md font-label-md text-primary hover:text-primary-container flex items-center gap-1 transition-colors"
            >
                Abrir tablero del sprint
                <AppIcon name="arrow_forward" :size="14" />
            </Link>
        </footer>
    </section>
</template>
