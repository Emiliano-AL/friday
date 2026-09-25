<script setup lang="ts">
import { computed, ref } from 'vue';
import TaskCard from './TaskCard.vue';
import type { TaskItem } from './types';

const props = defineProps<{
    tasks: TaskItem[];
    projectId: number;
    canWrite: boolean;
}>();

const emit = defineEmits<{
    moved: [payload: { task: TaskItem; status: string }];
}>();

// Mirrors the cases and labels of the App\Enums\TaskStatus enum.
const columns = [
    { status: 'backlog', label: 'Backlog' },
    { status: 'todo', label: 'Por hacer' },
    { status: 'in_progress', label: 'En progreso' },
    { status: 'in_review', label: 'En revisión' },
    { status: 'done', label: 'Hecho' },
];

const tasksByStatus = computed(() => {
    const grouped = new Map<string, TaskItem[]>();

    for (const column of columns) {
        grouped.set(
            column.status,
            props.tasks.filter((task) => task.status === column.status),
        );
    }

    return grouped;
});

const dragOverStatus = ref<string | null>(null);

const onDragStart = (event: DragEvent, task: TaskItem) => {
    if (!props.canWrite || !event.dataTransfer) {
        return;
    }

    event.dataTransfer.setData('text/task-id', String(task.id));
    event.dataTransfer.effectAllowed = 'move';
};

const onDragOver = (event: DragEvent, status: string) => {
    if (!props.canWrite) {
        return;
    }

    event.preventDefault();

    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }

    dragOverStatus.value = status;
};

const onDragLeave = (status: string) => {
    if (dragOverStatus.value === status) {
        dragOverStatus.value = null;
    }
};

const onDrop = (event: DragEvent, status: string) => {
    dragOverStatus.value = null;

    if (!props.canWrite || !event.dataTransfer) {
        return;
    }

    event.preventDefault();

    const id = Number(event.dataTransfer.getData('text/task-id'));
    const task = props.tasks.find((item) => item.id === id);

    if (!task || task.status === status) {
        return;
    }

    emit('moved', { task, status });
};
</script>

<template>
    <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
        <div
            v-for="column in columns"
            :key="column.status"
            class="w-56 shrink-0 rounded-md bg-gray-50 p-2"
            :class="{
                'ring-2 ring-gray-400': dragOverStatus === column.status,
            }"
            :data-status="column.status"
            @dragover="onDragOver($event, column.status)"
            @dragleave="onDragLeave(column.status)"
            @drop="onDrop($event, column.status)"
        >
            <div class="flex items-center justify-between px-1 py-1">
                <span class="text-xs font-medium text-gray-700">{{
                    column.label
                }}</span>
                <span
                    class="rounded-full bg-gray-200 px-2 text-xs text-gray-700"
                >
                    {{ tasksByStatus.get(column.status)?.length ?? 0 }}
                </span>
            </div>

            <div class="mt-2 space-y-2">
                <TaskCard
                    v-for="task in tasksByStatus.get(column.status) ?? []"
                    :key="task.id"
                    :task="task"
                    :draggable="canWrite"
                    @dragstart="onDragStart($event, task)"
                />
                <p
                    v-if="(tasksByStatus.get(column.status) ?? []).length === 0"
                    class="px-1 py-2 text-center text-xs text-gray-400"
                >
                    Sin tareas
                </p>
            </div>
        </div>
    </div>
</template>
