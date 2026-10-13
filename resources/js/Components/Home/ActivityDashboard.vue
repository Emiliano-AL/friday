<script setup lang="ts">
import { computed, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import SummaryCards from '@/Components/Home/SummaryCards.vue';
import MyTasksPanel from '@/Components/Home/MyTasksPanel.vue';
import SprintPanel from '@/Components/Home/SprintPanel.vue';
import ProjectsPanel from '@/Components/Home/ProjectsPanel.vue';
import ActivityFeed from '@/Components/Home/ActivityFeed.vue';
import type { Dashboard } from '@/types/dashboard';

const props = defineProps<{
    dashboard: Dashboard;
}>();

const page = usePage();

const openTaskForm = inject<() => void>('friday:open-task-form', () => {});

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

    const tasks = `Tienes ${context.priorityTasks} tareas prioritarias para hoy`;

    return context.sprint
        ? `${tasks}; el sprint ${context.sprint.name} va al ${context.sprint.coverage}%.`
        : `${tasks}.`;
});
</script>

<template>
    <div class="gap-space-xl mx-auto flex w-full max-w-7xl flex-col">
        <div class="gap-space-lg flex flex-col">
            <header
                class="gap-space-md flex flex-wrap items-end justify-between"
            >
                <div class="gap-space-xs flex min-w-0 flex-col">
                    <h1
                        class="text-headline-lg font-headline-lg text-on-surface"
                    >
                        {{ greeting }}
                    </h1>
                    <p
                        class="text-body-md font-body-md text-on-surface-variant"
                    >
                        {{ subtitle }}
                    </p>
                </div>
                <button
                    type="button"
                    class="bg-primary-container text-on-primary shadow-card hover:bg-primary gap-space-xs px-space-md text-label-md font-label-md flex items-center rounded-lg py-2 transition-colors"
                    @click="openTaskForm"
                >
                    <AppIcon name="add" :size="16" />
                    Nueva Tarea
                </button>
            </header>

            <SummaryCards
                :summary="dashboard.summary"
                :insight="dashboard.weeklyInsight"
            />
        </div>

        <div
            class="gap-space-lg grid grid-cols-1 items-start xl:grid-cols-[minmax(0,1fr)_360px]"
        >
            <div class="gap-space-lg flex min-w-0 flex-col">
                <MyTasksPanel :my-tasks="dashboard.myTasks" />
                <ActivityFeed :feed="dashboard.activity" />
            </div>
            <aside
                aria-label="Sprint y proyectos"
                class="gap-space-lg flex min-w-0 flex-col"
            >
                <SprintPanel
                    v-if="dashboard.sprintPanel"
                    :sprint="dashboard.sprintPanel"
                />
                <ProjectsPanel :projects="dashboard.projects" />
            </aside>
        </div>
    </div>
</template>
