<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import TaskBoard from '@/Components/Tasks/Board/TaskBoard.vue';
import TaskFilters from '@/Components/Tasks/Board/TaskFilters.vue';
import TaskFormModal from '@/Components/Tasks/Board/TaskFormModal.vue';
import TaskGroupSection from '@/Components/Tasks/Board/TaskGroupSection.vue';
import TaskRow from '@/Components/Tasks/Board/TaskRow.vue';
import TaskCommentsModal from '@/Components/Tasks/TaskCommentsModal.vue';
import type {
    BoardTask,
    TaskProjectOption,
    TaskStatusValue,
    TasksTab,
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

const search = ref('');
const tab = ref<TasksTab>('all');
const projectId = ref<number | 'all' | 'none'>('all');
const assigneeId = ref<number | 'all'>('all');
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

const assignees = computed(() => {
    const map = new Map<
        number,
        { id: number; name: string; avatar: string | null }
    >();

    map.set(currentUser.value.id, currentUser.value);

    props.tasks.forEach((task) => {
        if (task.assignee !== null && !map.has(task.assignee.id)) {
            map.set(task.assignee.id, task.assignee);
        }
    });

    return [...map.values()];
});

const baseTasks = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.tasks.filter((task) => {
        if (projectId.value === 'none' && task.project !== null) {
            return false;
        }

        if (
            typeof projectId.value === 'number' &&
            task.project?.id !== projectId.value
        ) {
            return false;
        }

        if (
            assigneeId.value !== 'all' &&
            task.assignee?.id !== assigneeId.value
        ) {
            return false;
        }

        return term === '' || task.title.toLowerCase().includes(term);
    });
});

const isUrgent = (task: BoardTask) =>
    task.priority === 'high' || task.priority === 'urgent';
const isInProgress = (task: BoardTask) =>
    task.status === 'in_progress' || task.status === 'in_review';

const nonDone = computed(() =>
    baseTasks.value.filter((t) => t.status !== 'done'),
);
const doneTasks = computed(() =>
    baseTasks.value.filter((t) => t.status === 'done'),
);

const counts = computed(() => ({
    all: nonDone.value.length,
    mine: nonDone.value.filter((t) => t.assignee?.id === currentUser.value.id)
        .length,
    standalone: nonDone.value.filter((t) => t.project === null).length,
    urgent: nonDone.value.filter(isUrgent).length,
    done: doneTasks.value.length,
}));

const visibleTasks = computed(() => {
    if (tab.value === 'done') {
        return doneTasks.value;
    }

    let list = nonDone.value;

    if (tab.value === 'mine') {
        list = list.filter((t) => t.assignee?.id === currentUser.value.id);
    } else if (tab.value === 'standalone') {
        list = list.filter((t) => t.project === null);
    } else if (tab.value === 'urgent') {
        list = list.filter(isUrgent);
    }

    return list;
});

interface TaskGroup {
    key: string;
    title: string;
    icon: string;
    tasks: BoardTask[];
}

const groups = computed<TaskGroup[]>(() => {
    const list = visibleTasks.value;
    const urgent = list.filter(isUrgent);
    const progress = list.filter((t) => !isUrgent(t) && isInProgress(t));
    const standalone = list.filter(
        (t) => !isUrgent(t) && !isInProgress(t) && t.project === null,
    );
    const upcoming = list.filter(
        (t) => !isUrgent(t) && !isInProgress(t) && t.project !== null,
    );

    const set =
        tab.value === 'done'
            ? [{ key: 'done', title: 'Completadas', icon: 'check' }]
            : [
                  {
                      key: 'urgent',
                      title: 'Alta Prioridad & Urgentes',
                      icon: 'emergency',
                  },
                  { key: 'progress', title: 'En Curso', icon: 'sync' },
                  {
                      key: 'standalone',
                      title: 'Sin Proyecto Asignado',
                      icon: 'inbox',
                  },
                  {
                      key: 'upcoming',
                      title: 'Próximas & Backlog',
                      icon: 'view_list',
                  },
              ];

    const byKey: Record<string, BoardTask[]> = {
        urgent,
        progress,
        standalone,
        upcoming,
        done: list,
    };

    return set.map((group) => ({ ...group, tasks: byKey[group.key] }));
});

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

function clearFilters(): void {
    search.value = '';
    projectId.value = 'all';
    assigneeId.value = 'all';
    tab.value = 'all';
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
        filters.value?.focus();

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

const filters = ref<InstanceType<typeof TaskFilters> | null>(null);

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

            <TaskFilters
                ref="filters"
                :search="search"
                :tab="tab"
                :project-id="projectId"
                :assignee-id="assigneeId"
                :counts="counts"
                :view="view"
                :projects="projects"
                :assignees="assignees"
                @update:search="search = $event"
                @update:tab="tab = $event"
                @update:project-id="projectId = $event"
                @update:assignee-id="assigneeId = $event"
                @update:view="persistView"
                @create="openCreate()"
            />

            <div
                v-if="props.tasks.length === 0"
                class="gap-space-md py-space-xl flex flex-col items-center text-center"
            >
                <div
                    class="bg-surface-container text-primary flex h-14 w-14 items-center justify-center rounded-full"
                >
                    <AppIcon name="check_circle" :size="28" />
                </div>
                <h2 class="text-headline-sm font-headline-sm text-on-surface">
                    No tienes tareas todavía
                </h2>
                <p class="text-body-sm font-body-sm text-on-surface-variant">
                    Crea tu primera tarea para empezar a organizar tu trabajo.
                </p>
                <button
                    type="button"
                    class="bg-primary text-on-primary text-label-md font-label-md gap-space-xs shadow-card hover:bg-primary-container flex items-center rounded-lg px-4 py-2 transition-colors"
                    @click="openCreate()"
                >
                    <AppIcon name="add" :size="16" />
                    Crear tarea
                </button>
            </div>

            <div
                v-else-if="visibleTasks.length === 0"
                class="gap-space-md py-space-xl flex flex-col items-center text-center"
            >
                <p class="text-body-md font-body-md text-on-surface-variant">
                    Sin coincidencias para los filtros actuales.
                </p>
                <button
                    type="button"
                    class="text-primary text-label-sm font-label-sm hover:bg-surface-container rounded-lg px-3 py-1.5 transition-colors"
                    @click="clearFilters"
                >
                    Limpiar filtros
                </button>
            </div>

            <template v-else-if="view === 'list'">
                <TaskGroupSection
                    v-for="group in groups"
                    :key="group.key"
                    :title="group.title"
                    :icon="group.icon"
                    :count="group.tasks.length"
                >
                    <TaskRow
                        v-for="task in group.tasks"
                        :key="task.id"
                        :task="task"
                        :can-write="task.canUpdate"
                        @open="openEdit"
                        @edit="openEdit"
                        @status="changeStatus"
                        @comment="openComments"
                        @remove="openEdit"
                    />
                </TaskGroupSection>
            </template>

            <TaskBoard
                v-else
                :tasks="visibleTasks"
                :can-write="true"
                @moved="onMoved"
                @add="openCreateFromColumn"
                @open="openEdit"
                @edit="openEdit"
                @comment="openComments"
            />

            <p
                v-if="props.tasks.length > 0 && visibleTasks.length > 0"
                class="text-label-xs font-label-xs text-on-surface-variant gap-space-xs px-space-md py-space-sm flex flex-wrap items-center"
            >
                <span class="text-on-surface font-medium">
                    Mostrando {{ visibleTasks.length }} de
                    {{ props.tasks.length }} tareas
                </span>
                <span>•</span>
                <span>{{ counts.done }} completadas</span>
                <span class="hidden sm:inline">•</span>
                <span class="hidden sm:inline">
                    Presiona
                    <kbd
                        class="bg-surface-container text-on-surface rounded px-1.5 py-0.5 font-semibold"
                        >C</kbd
                    >
                    para crear
                </span>
            </p>
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
