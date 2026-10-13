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
            return { icon: 'drag_handle', classes: 'text-on-surface-variant' };
        case 'high':
            return { icon: 'priority_high', classes: 'text-tertiary' };
        default:
            return { icon: 'emergency', classes: 'text-error' };
    }
}
</script>

<template>
    <section
        aria-label="Mis tareas para hoy y prioritarias"
        class="bg-surface-container-lowest shadow-card flex flex-col rounded-xl"
    >
        <header class="px-space-lg pt-space-lg pb-space-sm">
            <h2 class="text-headline-sm font-headline-sm text-on-surface">
                Mis tareas para hoy
            </h2>
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
                <span class="gap-space-sm flex min-w-0 flex-1 items-center">
                    <Link
                        :href="showTask.url(task.id)"
                        class="text-body-md font-body-md text-on-surface hover:text-primary truncate font-medium transition-colors"
                    >
                        {{ task.title }}
                    </Link>
                    <span
                        v-if="task.commentsCount > 0"
                        class="text-on-surface-variant flex shrink-0 items-center gap-1"
                        :aria-label="`${task.commentsCount} comentarios`"
                    >
                        <AppIcon name="chat_bubble_outline" :size="13" />
                        <span class="text-label-xs font-label-xs">{{
                            task.commentsCount
                        }}</span>
                    </span>
                </span>
                <span
                    v-if="task.project"
                    class="text-label-sm font-label-sm text-on-surface-variant hidden w-44 shrink-0 truncate xl:inline"
                >
                    {{ task.project.title }}
                </span>
                <span
                    v-else
                    class="text-label-sm font-label-sm text-on-surface-variant hidden w-44 shrink-0 italic xl:inline"
                >
                    Independiente
                </span>
                <span class="hidden w-24 shrink-0 items-center md:flex">
                    <span
                        class="bg-surface-container-low text-label-xs font-label-xs text-on-surface-variant rounded px-2 py-1"
                    >
                        {{ task.statusLabel }}
                    </span>
                </span>
                <span
                    :class="priorityChip(task.priority).classes"
                    class="text-label-xs font-label-xs flex w-20 shrink-0 items-center gap-1"
                >
                    <AppIcon
                        :name="priorityChip(task.priority).icon"
                        :size="14"
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
