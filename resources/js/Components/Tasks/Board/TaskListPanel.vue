<script setup lang="ts">
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import TaskFilters from '@/Components/Tasks/Board/TaskFilters.vue';
import TaskGroupSection from '@/Components/Tasks/Board/TaskGroupSection.vue';
import TaskRow from '@/Components/Tasks/Board/TaskRow.vue';
import type {
    BoardTask,
    TaskOption,
    TasksTab,
    TasksView,
    TaskStatusValue,
} from '@/types/task';

const props = withDefaults(
    defineProps<{
        tasks: BoardTask[];
        currentUser: { id: number; name: string; avatar: string | null };
        projects?: TaskOption[];
        view?: TasksView | null;
        showProjectFilter?: boolean;
        showStandaloneTab?: boolean;
    }>(),
    {
        projects: () => [],
        view: 'list',
        showProjectFilter: true,
        showStandaloneTab: true,
    },
);

const emit = defineEmits<{
    'update:view': [value: TasksView];
    create: [];
    edit: [task: BoardTask];
    status: [task: BoardTask, status: TaskStatusValue];
    comment: [task: BoardTask];
    remove: [task: BoardTask];
}>();

const search = ref('');
const tab = ref<TasksTab>('all');
const projectId = ref<number | 'all' | 'none'>('all');
const assigneeId = ref<number | 'all'>('all');

const assignees = computed(() => {
    const map = new Map<
        number,
        { id: number; name: string; avatar: string | null }
    >();

    map.set(props.currentUser.id, props.currentUser);

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
        if (props.showProjectFilter) {
            if (projectId.value === 'none' && task.project !== null) {
                return false;
            }

            if (
                typeof projectId.value === 'number' &&
                task.project?.id !== projectId.value
            ) {
                return false;
            }
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
    mine: nonDone.value.filter((t) => t.assignee?.id === props.currentUser.id)
        .length,
    standalone: nonDone.value.filter((t) => t.project === null).length,
    urgent: nonDone.value.filter(isUrgent).length,
    done: doneTasks.value.length,
}));

const visibleTasks = computed(() => {
    if (tab.value === 'done') {
        return doneTasks.value;
    }

    // En vista kanban el tablero filtra por columna (incluida "Terminado"),
    // así que debe recibir también las tareas completadas.
    let list = props.view === 'kanban' ? baseTasks.value : nonDone.value;

    if (tab.value === 'mine') {
        list = list.filter((t) => t.assignee?.id === props.currentUser.id);
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
                  ...(props.showStandaloneTab
                      ? [
                            {
                                key: 'standalone',
                                title: 'Sin Proyecto Asignado',
                                icon: 'inbox',
                            },
                        ]
                      : []),
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

function clearFilters(): void {
    search.value = '';
    projectId.value = 'all';
    assigneeId.value = 'all';
    tab.value = 'all';
}

const filters = ref<InstanceType<typeof TaskFilters> | null>(null);

function focus(): void {
    filters.value?.focus();
}

defineExpose({ focus });
</script>

<template>
    <div class="gap-space-md flex flex-col">
        <TaskFilters
            ref="filters"
            :search="search"
            :tab="tab"
            :project-id="projectId"
            :assignee-id="assigneeId"
            :counts="counts"
            :view="view ?? 'list'"
            :projects="projects"
            :assignees="assignees"
            :show-project-filter="showProjectFilter"
            :show-standalone-tab="showStandaloneTab"
            :show-view-toggle="view !== null"
            @update:search="search = $event"
            @update:tab="tab = $event"
            @update:project-id="projectId = $event"
            @update:assignee-id="assigneeId = $event"
            @update:view="emit('update:view', $event)"
            @create="emit('create')"
        />

        <div
            v-if="tasks.length === 0"
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
                @click="emit('create')"
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

        <template v-else-if="view === null || view === 'list'">
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
                    @open="emit('edit', $event)"
                    @edit="emit('edit', $event)"
                    @status="(task, status) => emit('status', task, status)"
                    @comment="emit('comment', $event)"
                    @remove="emit('remove', $event)"
                />
            </TaskGroupSection>
        </template>

        <slot v-else name="board" :tasks="visibleTasks" />

        <p
            v-if="tasks.length > 0 && visibleTasks.length > 0"
            class="text-label-xs font-label-xs text-on-surface-variant gap-space-xs px-space-md py-space-sm flex flex-wrap items-center"
        >
            <span class="text-on-surface font-medium">
                Mostrando {{ visibleTasks.length }} de {{ tasks.length }}
                tareas
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
</template>
