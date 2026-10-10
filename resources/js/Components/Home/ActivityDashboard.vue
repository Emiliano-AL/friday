<script setup lang="ts">
import { computed, inject } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import SummaryCards from '@/Components/Home/SummaryCards.vue';
import MyTasksPanel from '@/Components/Home/MyTasksPanel.vue';
import SprintPanel from '@/Components/Home/SprintPanel.vue';
import ProjectsPanel from '@/Components/Home/ProjectsPanel.vue';
import WeeklyInsightCard from '@/Components/Home/WeeklyInsightCard.vue';
import ActivityFeed from '@/Components/Home/ActivityFeed.vue';
import { show as showSprint } from '@/routes/projects/sprints';
import type { Dashboard } from '@/types/dashboard';

const props = defineProps<{
    dashboard: Dashboard;
}>();

const page = usePage();

const openPalette = inject<() => void>('friday:open-palette', () => {});

const greeting = computed(() => {
    const hour = new Date().getHours();
    const saludo =
        hour < 12
            ? 'Buenos días'
            : hour < 20
              ? 'Buenas tardes'
              : 'Buenas noches';

    const firstName = page.props.auth.user.name.trim().split(/\s+/)[0] ?? '';
    const capitalized = firstName.charAt(0).toUpperCase() + firstName.slice(1);

    return `${saludo}, ${capitalized}`;
});

const subtitle = computed(() => {
    const context = props.dashboard.context;

    let text = `Tienes ${context.priorityTasks} tareas prioritarias para hoy`;

    text += context.sprint
        ? ` y el ${context.sprint.name} al ${context.sprint.coverage}% de avance.`
        : '.';

    return text;
});
</script>

<template>
    <div class="gap-space-lg mx-auto flex w-full max-w-7xl flex-col">
        <header class="gap-space-md flex flex-wrap items-start justify-between">
            <div class="gap-space-xs flex min-w-0 flex-col">
                <h1 class="text-headline-lg font-headline-lg text-on-surface">
                    {{ greeting }}
                </h1>
                <p class="text-body-md font-body-md text-on-surface-variant">
                    {{ subtitle }}
                </p>
            </div>
            <div class="gap-space-sm flex items-center">
                <Link
                    v-if="dashboard.sprintPanel"
                    :href="
                        showSprint.url([
                            dashboard.sprintPanel.project.id,
                            dashboard.sprintPanel.id,
                        ])
                    "
                    class="bg-surface-container-lowest text-on-surface-variant shadow-card hover:bg-surface-container-high hover:text-on-surface gap-space-xs px-space-md text-label-md font-label-md flex items-center rounded-lg py-1.5 transition-colors"
                >
                    <AppIcon name="sprint" :size="16" class="text-primary" />
                    Ir al Sprint
                </Link>
                <button
                    type="button"
                    class="bg-primary-container text-on-primary shadow-card hover:bg-primary gap-space-xs px-space-md text-label-md font-label-md flex items-center rounded-lg py-1.5 transition-colors"
                    @click="openPalette"
                >
                    <AppIcon name="add" :size="16" />
                    Nueva Tarea
                </button>
            </div>
        </header>

        <SummaryCards :summary="dashboard.summary" />

        <div class="gap-space-lg grid grid-cols-1 xl:grid-cols-[1fr_380px]">
            <div class="gap-space-lg flex flex-col">
                <MyTasksPanel :my-tasks="dashboard.myTasks" />
                <WeeklyInsightCard :insight="dashboard.weeklyInsight" />
            </div>
            <div class="gap-space-lg flex flex-col">
                <SprintPanel
                    v-if="dashboard.sprintPanel"
                    :sprint="dashboard.sprintPanel"
                />
                <ActivityFeed :feed="dashboard.activity" />
                <ProjectsPanel :projects="dashboard.projects" />
            </div>
        </div>
    </div>
</template>
