<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import TaskBoard from '@/Components/Tasks/Board/TaskBoard.vue';
import TaskListPanel from '@/Components/Tasks/Board/TaskListPanel.vue';
import TaskFormModal from '@/Components/Tasks/Board/TaskFormModal.vue';
import TaskCommentsModal from '@/Components/Tasks/TaskCommentsModal.vue';
import type {
    BoardTask,
    TaskProjectOption,
    TaskStatusValue,
    TasksView,
} from '@/types/task';
import { show as taskShow, update } from '@/routes/tasks';
import { store as storeTaskComment } from '@/routes/tasks/comments';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    tasks: BoardTask[];
    projects: TaskProjectOption[];
}>();

const page = usePage();

const currentUser = computed(() => {
    const user = page.props.auth.user;

    return { id: user.id, name: user.name, avatar: user.avatar ?? null };
});

const view = ref<TasksView>(
    (localStorage.getItem('tasks.view') as TasksView | null) ?? 'list',
);

const modalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const editingTask = ref<BoardTask | null>(null);
const initialStatus = ref<
    Exclude<TaskStatusValue, 'backlog' | 'done'> | undefined
>(undefined);
const commentingTask = ref<BoardTask | null>(null);

function persistView(value: TasksView): void {
    view.value = value;
    localStorage.setItem('tasks.view', value);
}

function openCreate(
    status?: Exclude<TaskStatusValue, 'backlog' | 'done'>,
): void {
    modalMode.value = 'create';
    editingTask.value = null;
    initialStatus.value = status;
    modalOpen.value = true;
}

function openEdit(task: BoardTask): void {
    modalMode.value = 'edit';
    editingTask.value = task;
    initialStatus.value = undefined;
    modalOpen.value = true;
}

function changeStatus(task: BoardTask, status: TaskStatusValue): void {
    router.put(update.url(task.id), { status }, { preserveScroll: true });
}

function onMoved(task: BoardTask, status: TaskStatusValue): void {
    changeStatus(task, status);
}

function openCreateFromColumn(status: TaskStatusValue): void {
    if (
        status === 'todo' ||
        status === 'in_progress' ||
        status === 'in_review'
    ) {
        openCreate(status);
    }
}

function openComments(task: BoardTask): void {
    commentingTask.value = task;
}

function isEditableTarget(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLElement &&
        (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.tagName === 'SELECT' ||
            target.isContentEditable)
    );
}

function onCaptureKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'f') {
        event.preventDefault();
        event.stopPropagation();
        panel.value?.focus();

        return;
    }

    if (isEditableTarget(event.target) || event.metaKey || event.ctrlKey) {
        return;
    }

    if (
        event.key.toLowerCase() === 'c' &&
        document.querySelector('dialog[open]') === null
    ) {
        event.preventDefault();
        event.stopPropagation();
        openCreate();
    }
}

const panel = ref<InstanceType<typeof TaskListPanel> | null>(null);

onMounted(() => {
    document.addEventListener('keydown', onCaptureKeydown, true);

    const params = new URLSearchParams(window.location.search);

    if (params.get('new') === '1') {
        openCreate();
        window.history.replaceState({}, '', '/tasks');
    }
});

onBeforeUnmount(() =>
    document.removeEventListener('keydown', onCaptureKeydown, true),
);
</script>

<template>
    <Head title="Mis Tareas" />

    <AuthenticatedLayout>
        <div
            class="gap-space-md py-space-md mx-auto flex w-full max-w-7xl flex-col"
        >
            <div class="gap-space-sm flex items-center">
                <span
                    class="text-label-sm font-label-sm text-on-surface-variant"
                >
                    Workspace
                </span>
                <span class="text-on-surface-variant/40 text-label-sm">/</span>
                <span
                    class="text-label-sm font-label-sm text-on-surface font-semibold"
                >
                    Mis Tareas
                </span>
            </div>

            <div
                class="gap-space-md flex flex-col justify-between md:flex-row md:items-end"
            >
                <div class="gap-space-xs flex flex-col">
                    <div class="gap-space-md flex items-center">
                        <h1
                            class="text-headline-xl font-headline-xl text-on-surface"
                        >
                            Mis Tareas
                        </h1>
                    </div>
                    <p
                        class="text-body-md font-body-md text-on-surface-variant"
                    >
                        Todo tu trabajo en un solo lugar: tareas de tus
                        proyectos e independientes.
                    </p>
                </div>
            </div>

            <TaskListPanel
                ref="panel"
                :tasks="props.tasks"
                :current-user="currentUser"
                :projects="projects"
                :view="view"
                @update:view="persistView"
                @create="openCreate()"
                @edit="openEdit"
                @status="changeStatus"
                @comment="openComments"
                @remove="openEdit"
            >
                <template #board="{ tasks: visibleTasks }">
                    <TaskBoard
                        :tasks="visibleTasks"
                        :can-write="true"
                        @moved="onMoved"
                        @add="openCreateFromColumn"
                        @open="openEdit"
                        @edit="openEdit"
                        @comment="openComments"
                    />
                </template>
            </TaskListPanel>
        </div>

        <TaskFormModal
            :open="modalOpen"
            :mode="modalMode"
            :task="editingTask ?? undefined"
            :projects="projects"
            :current-user="currentUser"
            :initial-status="initialStatus"
            @close="modalOpen = false"
        />

        <TaskCommentsModal
            :show="commentingTask !== null"
            :task="commentingTask ? { ...commentingTask, comments: [] } : null"
            :can-comment="commentingTask?.canUpdate ?? false"
            use-global
            :endpoints="
                commentingTask
                    ? {
                          show: taskShow.url(commentingTask.id),
                          store: storeTaskComment.url(commentingTask.id),
                      }
                    : { show: '', store: '' }
            "
            @close="commentingTask = null"
        />
    </AuthenticatedLayout>
</template>
