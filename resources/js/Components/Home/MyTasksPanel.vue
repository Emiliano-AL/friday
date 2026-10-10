<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { index as tasksIndex, show as showTask } from '@/routes/tasks';
import type { MyTasksPayload, MyTaskItem } from '@/types/dashboard';
import type { TaskPriorityValue } from '@/types/task';

defineProps<{
    myTasks: MyTasksPayload;
}>();

function statusDotClasses(status: MyTaskItem['status']): string {
    switch (status) {
        case 'todo':
            return 'bg-secondary-container';
        case 'in_progress':
            return 'bg-primary';
        case 'in_review':
            return 'bg-tertiary';
        default:
            return 'bg-outline-variant';
    }
}

function priorityChip(priority: TaskPriorityValue): {
    icon: string;
    classes: string;
} {
    switch (priority) {
        case 'low':
            return { icon: 'south', classes: 'text-on-surface-variant' };
        case 'medium':
            return { icon: 'drag_handle', classes: 'text-primary' };
        case 'high':
            return {
                icon: 'priority_high',
                classes:
                    'bg-tertiary-fixed text-on-tertiary-fixed font-semibold',
            };
        default:
            return {
                icon: 'emergency',
                classes:
                    'bg-error-container text-on-error-container font-semibold',
            };
    }
}
</script>

<template>
    <section
        aria-label="Mis tareas para hoy y prioritarias"
        class="bg-surface-container-lowest shadow-card flex flex-col rounded-xl"
    >
        <header
            class="px-space-lg pt-space-lg pb-space-md gap-space-md flex items-center justify-between"
        >
            <h2 class="text-headline-sm font-headline-sm text-on-surface">
                Mis tareas para hoy
            </h2>
            <span
                class="bg-surface-container text-label-xs font-label-xs text-on-surface-variant rounded-full px-2 py-0.5"
            >
                {{ myTasks.total }}
            </span>
        </header>

        <ul v-if="myTasks.items.length > 0" class="flex flex-col">
            <li
                v-for="task in myTasks.items"
                :key="task.id"
                class="border-surface-container-low hover:bg-surface-container-low gap-space-md px-space-lg py-space-sm flex items-center border-b transition-colors"
            >
                <span
                    :class="statusDotClasses(task.status)"
                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                    :title="task.statusLabel"
                />
                <span
                    class="text-body-md font-body-md text-on-surface min-w-0 flex-1 truncate font-medium"
                >
                    <Link
                        :href="showTask.url(task.id)"
                        class="hover:text-primary transition-colors"
                    >
                        {{ task.title }}
                    </Link>
                </span>
                <span
                    class="bg-surface-container-low text-label-xs font-label-xs text-on-surface-variant hidden shrink-0 rounded px-2 py-1 md:inline"
                >
                    {{ task.statusLabel }}
                </span>
                <span
                    v-if="task.project"
                    class="text-label-xs font-label-xs text-on-surface-variant hidden max-w-[10rem] truncate xl:inline"
                >
                    {{ task.project.title }}
                </span>
                <span
                    v-else
                    class="text-label-xs font-label-xs text-outline-variant hidden italic xl:inline"
                >
                    Independiente
                </span>
                <span
                    v-if="task.commentsCount > 0"
                    class="text-on-surface-variant flex shrink-0 items-center gap-1"
                >
                    <AppIcon name="chat_bubble_outline" :size="13" />
                    <span class="text-label-xs font-label-xs">{{
                        task.commentsCount
                    }}</span>
                </span>
                <span
                    :class="priorityChip(task.priority).classes"
                    class="text-label-xs font-label-xs flex shrink-0 items-center gap-1 rounded px-2 py-1"
                >
                    <AppIcon
                        :name="priorityChip(task.priority).icon"
                        :size="13"
                    />
                    {{ task.priorityLabel }}
                </span>
            </li>
        </ul>
        <p
            v-else
            class="text-body-sm font-body-sm text-on-surface-variant px-space-lg pb-space-lg italic"
        >
            No tienes tareas pendientes asignadas.
        </p>

        <footer class="px-space-lg py-space-md">
            <Link
                :href="tasksIndex.url()"
                class="text-label-md font-label-md text-primary hover:text-primary-container flex items-center gap-1 transition-colors"
            >
                Ver todas mis tareas ({{ myTasks.total }})
                <AppIcon name="arrow_forward" :size="14" />
            </Link>
        </footer>
    </section>
</template>
