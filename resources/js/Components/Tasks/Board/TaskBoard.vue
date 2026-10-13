<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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
];

const draggingId = ref<number | null>(null);
const sourceStatus = ref<TaskStatusValue | null>(null);
const overColumn = ref<TaskStatusValue | null>(null);
const landedId = ref<number | null>(null);

const DONE_COLLAPSED_KEY = 'friday:kanban-done-collapsed';

/**
 * The finished column starts collapsed; the viewer's choice is remembered in this browser.
 */
function readDoneCollapsed(): boolean {
    try {
        return window.localStorage.getItem(DONE_COLLAPSED_KEY) !== 'false';
    } catch {
        return true;
    }
}

const doneCollapsed = ref(readDoneCollapsed());

function toggleDone(): void {
    doneCollapsed.value = !doneCollapsed.value;

    try {
        window.localStorage.setItem(
            DONE_COLLAPSED_KEY,
            String(doneCollapsed.value),
        );
    } catch {
        // Storage unavailable (private mode): keep the in-memory choice only.
    }
}

function landedInColumn(status: TaskStatusValue): boolean {
    return (
        landedId.value !== null &&
        pendingStatus.value[landedId.value] === status
    );
}

/**
 * Optimistic status per task id, shown until the server props catch up.
 */
const pendingStatus = ref<Record<number, TaskStatusValue>>({});

watch(
    () => props.tasks,
    () => {
        pendingStatus.value = {};
    },
);

/**
 * Tasks of a column; just-moved tasks go first, where the server's
 * most-recently-updated order will place them.
 */
function tasksByStatus(status: TaskStatusValue): BoardTask[] {
    const inColumn = props.tasks.filter(
        (task) => (pendingStatus.value[task.id] ?? task.status) === status,
    );

    return [
        ...inColumn.filter((task) => task.id in pendingStatus.value),
        ...inColumn.filter((task) => !(task.id in pendingStatus.value)),
    ];
}

function isDropTarget(status: TaskStatusValue): boolean {
    return draggingId.value !== null && status !== sourceStatus.value;
}

function resetDrag(): void {
    draggingId.value = null;
    sourceStatus.value = null;
    overColumn.value = null;
}

function onDragStart(event: DragEvent, task: BoardTask): void {
    if (!props.canWrite || !event.dataTransfer) {
        return;
    }

    event.dataTransfer.setData('text/plain', String(task.id));
    event.dataTransfer.effectAllowed = 'move';

    // Defer so the browser captures the drag image before the card dims.
    requestAnimationFrame(() => {
        draggingId.value = task.id;
        sourceStatus.value = pendingStatus.value[task.id] ?? task.status;
    });
}

function onDragEnter(status: TaskStatusValue): void {
    if (!isDropTarget(status)) {
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
    const from = sourceStatus.value;

    resetDrag();

    if (!props.canWrite || id === null || from === status) {
        return;
    }

    const task = props.tasks.find((item) => item.id === id);

    if (!task) {
        return;
    }

    pendingStatus.value = { ...pendingStatus.value, [id]: status };
    landedId.value = id;
    window.setTimeout(() => {
        if (landedId.value === id) {
            landedId.value = null;
        }
    }, 900);

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
        class="gap-space-md pb-space-lg flex overflow-x-auto scroll-smooth select-none"
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
            :drop-target="isDropTarget(column.status as TaskStatusValue)"
            :collapsible="column.status === 'done'"
            :collapsed="column.status === 'done' && doneCollapsed"
            :landed="
                column.status === 'done' &&
                doneCollapsed &&
                landedInColumn(column.status as TaskStatusValue)
            "
            @toggle="toggleDone"
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
                :dragging="draggingId === task.id"
                :landed="landedId === task.id"
                @dragstart="onDragStart"
                @dragend="resetDrag"
                @open="emit('open', $event)"
                @edit="emit('edit', $event)"
                @comment="emit('comment', $event)"
            />
        </TaskBoardColumn>
    </div>
</template>
