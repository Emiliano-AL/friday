<script setup lang="ts">
import { computed, ref } from 'vue';
import TaskBoardCard from '@/Components/Tasks/Board/TaskBoardCard.vue';
import TaskBoardColumn from '@/Components/Tasks/Board/TaskBoardColumn.vue';
import type { BoardTask, TaskStatusValue } from '@/types/task';

const props = defineProps<{
    tasks: BoardTask[];
    canWrite: boolean;
}>();

const emit = defineEmits<{
    moved: [task: BoardTask, status: TaskStatusValue];
    add: [status: TaskStatusValue];
    open: [task: BoardTask];
    edit: [task: BoardTask];
    comment: [task: BoardTask];
}>();

interface BoardColumn {
    key: string;
    title: string;
    status: TaskStatusValue | 'blocked';
    dotClass: string;
    disabled?: boolean;
    quickAdd?: boolean;
    wipLimit?: boolean;
}

const columns: BoardColumn[] = [
    {
        key: 'todo',
        title: 'Por Hacer',
        status: 'todo',
        dotClass: 'bg-outline-variant',
    },
    {
        key: 'in_progress',
        title: 'En Curso',
        status: 'in_progress',
        dotClass: 'bg-primary',
        wipLimit: true,
    },
    {
        key: 'in_review',
        title: 'En Revisión',
        status: 'in_review',
        dotClass: 'bg-tertiary',
    },
    {
        key: 'done',
        title: 'Terminado',
        status: 'done',
        dotClass: 'bg-outline',
        quickAdd: false,
    },
    {
        key: 'blocked',
        title: 'Bloqueado',
        status: 'blocked',
        dotClass: 'bg-error',
        disabled: true,
    },
];

const draggingId = ref<number | null>(null);
const overColumn = ref<TaskStatusValue | null>(null);

function tasksByStatus(status: TaskStatusValue): BoardTask[] {
    return props.tasks.filter((task) => task.status === status);
}

function onDragStart(event: DragEvent, task: BoardTask): void {
    if (!props.canWrite || !event.dataTransfer) {
        return;
    }

    draggingId.value = task.id;
    event.dataTransfer.setData('text/plain', String(task.id));
    event.dataTransfer.effectAllowed = 'move';
}

function onDragEnter(status: TaskStatusValue): void {
    if (!draggingId.value) {
        return;
    }

    overColumn.value = status;
}

function onDragLeave(status: TaskStatusValue): void {
    if (overColumn.value === status) {
        overColumn.value = null;
    }
}

function onDrop(status: TaskStatusValue): void {
    const id = draggingId.value;

    overColumn.value = null;
    draggingId.value = null;

    if (!props.canWrite || id === null) {
        return;
    }

    const task = props.tasks.find((item) => item.id === id);

    if (!task || task.status === status) {
        return;
    }

    emit('moved', task, status);
}

const quickAddStatuses = computed<Set<TaskStatusValue>>(() => {
    return new Set(
        columns
            .filter((column) => !column.disabled && column.quickAdd !== false)
            .map((column) => column.status as TaskStatusValue),
    );
});
</script>

<template>
    <div
        class="gap-space-lg pb-space-lg flex overflow-x-auto scroll-smooth select-none"
    >
        <TaskBoardColumn
            v-for="column in columns"
            :key="column.key"
            :title="column.title"
            :status="column.status"
            :count="
                column.disabled
                    ? 0
                    : tasksByStatus(column.status as TaskStatusValue).length
            "
            :dot-class="column.dotClass"
            :disabled="column.disabled"
            :wip-limit="column.wipLimit"
            :drag-over="overColumn === column.status && !column.disabled"
            :quick-add="
                column.disabled
                    ? false
                    : quickAddStatuses.has(column.status as TaskStatusValue)
            "
            @drop="onDrop"
            @dragenter="onDragEnter"
            @dragleave="onDragLeave"
            @add="emit('add', $event)"
        >
            <TaskBoardCard
                v-for="task in tasksByStatus(column.status as TaskStatusValue)"
                :key="task.id"
                :task="task"
                :draggable="canWrite && !column.disabled"
                @dragstart="onDragStart"
                @open="emit('open', $event)"
                @edit="emit('edit', $event)"
                @comment="emit('comment', $event)"
            />
        </TaskBoardColumn>
    </div>
</template>
