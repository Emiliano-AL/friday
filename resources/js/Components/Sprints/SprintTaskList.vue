<script setup lang="ts">
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import TaskRow from '@/Components/Tasks/Board/TaskRow.vue';
import type {
    BoardTask,
    TaskPriorityValue,
    TaskStatusValue,
    TaskTypeValue,
} from '@/types/task';

const props = defineProps<{
    tasks: BoardTask[];
    canWrite: boolean;
}>();

const emit = defineEmits<{
    open: [task: BoardTask];
    edit: [task: BoardTask];
    status: [task: BoardTask, status: TaskStatusValue];
    comment: [task: BoardTask];
    remove: [task: BoardTask];
}>();

const search = ref('');
const assigneeId = ref<number | 'all'>('all');
const typeValue = ref<TaskTypeValue | 'all'>('all');
const groupBy = ref<'status' | 'priority'>('status');

const searchInput = ref<HTMLInputElement | null>(null);

function focus(): void {
    searchInput.value?.focus();
}

defineExpose({ focus });

const assigneeOptions = computed(() => {
    const map = new Map<number, { id: number; name: string }>();

    props.tasks.forEach((task) => {
        if (task.assignee !== null && !map.has(task.assignee.id)) {
            map.set(task.assignee.id, {
                id: task.assignee.id,
                name: task.assignee.name,
            });
        }
    });

    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
});

const typeOptions = computed(() => {
    const map = new Map<TaskTypeValue, string>();

    props.tasks.forEach((task) => {
        if (!map.has(task.type)) {
            map.set(task.type, task.typeLabel);
        }
    });

    return [...map.entries()];
});

const statusOrder: TaskStatusValue[] = [
    'todo',
    'in_progress',
    'in_review',
    'done',
];

const priorityOrder: TaskPriorityValue[] = ['urgent', 'high', 'medium', 'low'];

const statusLabels: Record<TaskStatusValue, string> = {
    backlog: 'Backlog',
    todo: 'Por Hacer',
    in_progress: 'En Curso',
    in_review: 'En Revisión',
    done: 'Terminado',
};

const priorityLabels: Record<TaskPriorityValue, string> = {
    low: 'Baja',
    medium: 'Media',
    high: 'Alta',
    urgent: 'Urgente',
};

const filteredTasks = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.tasks.filter((task) => {
        const matchesSearch =
            term === '' || task.title.toLowerCase().includes(term);
        const matchesAssignee =
            assigneeId.value === 'all' ||
            task.assignee?.id === assigneeId.value;
        const matchesType =
            typeValue.value === 'all' || task.type === typeValue.value;

        return matchesSearch && matchesAssignee && matchesType;
    });
});

const groups = computed(() => {
    const keyOf = (task: BoardTask) =>
        groupBy.value === 'status' ? task.status : task.priority;
    const order: string[] =
        groupBy.value === 'status' ? statusOrder : priorityOrder;
    const labels: Record<string, string> =
        groupBy.value === 'status' ? statusLabels : priorityLabels;

    const byKey = new Map<string, BoardTask[]>();

    filteredTasks.value.forEach((task) => {
        const key = keyOf(task);
        const list = byKey.get(key) ?? [];
        list.push(task);
        byKey.set(key, list);
    });

    return order
        .filter((key) => byKey.has(key))
        .map((key) => ({
            key,
            label: labels[key],
            tasks: byKey.get(key) ?? [],
        }));
});

const hasFilters = computed(
    () =>
        search.value.trim() !== '' ||
        assigneeId.value !== 'all' ||
        typeValue.value !== 'all',
);

function clearFilters(): void {
    search.value = '';
    assigneeId.value = 'all';
    typeValue.value = 'all';
}
</script>

<template>
    <section
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex flex-col rounded-xl"
        aria-label="Tareas del sprint"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div
                class="bg-surface-container-low focus-within:bg-surface-container-lowest relative min-w-56 flex-1 rounded-lg"
            >
                <AppIcon
                    name="search"
                    :size="16"
                    class="text-on-surface-variant absolute top-1/2 left-3 -translate-y-1/2"
                />
                <input
                    ref="searchInput"
                    v-model="search"
                    type="search"
                    placeholder="Buscar tareas del sprint..."
                    class="text-body-sm font-body-sm text-on-surface placeholder:text-on-surface-variant/60 focus:ring-primary/20 w-full rounded-lg py-2 pr-12 pl-9 focus:ring-2 focus:outline-none"
                />
                <kbd
                    class="text-label-xs font-label-xs text-on-surface-variant bg-surface-container pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 rounded px-1.5 py-0.5"
                    >⌘F</kbd
                >
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <label class="sr-only" for="sprint-filter-assignee"
                    >Responsable</label
                >
                <select
                    id="sprint-filter-assignee"
                    v-model="assigneeId"
                    class="text-label-sm font-label-sm border-outline-variant/40 bg-surface-container-low text-on-surface h-8 cursor-pointer appearance-none rounded-lg border px-2.5 pr-7 focus:outline-none"
                >
                    <option value="all">Responsable: Todos</option>
                    <option
                        v-for="assignee in assigneeOptions"
                        :key="assignee.id"
                        :value="assignee.id"
                    >
                        {{ assignee.name }}
                    </option>
                </select>

                <label class="sr-only" for="sprint-filter-type">Tipo</label>
                <select
                    id="sprint-filter-type"
                    v-model="typeValue"
                    class="text-label-sm font-label-sm border-outline-variant/40 bg-surface-container-low text-on-surface h-8 cursor-pointer appearance-none rounded-lg border px-2.5 pr-7 focus:outline-none"
                >
                    <option value="all">Tipo: Todos</option>
                    <option
                        v-for="[value, label] in typeOptions"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>

                <div
                    class="bg-surface-container-low flex rounded-lg p-0.5"
                    role="group"
                    aria-label="Agrupar por"
                >
                    <button
                        type="button"
                        :class="
                            groupBy === 'status'
                                ? 'bg-surface-container-lowest text-on-surface shadow-sm'
                                : 'text-on-surface-variant'
                        "
                        class="text-label-sm font-label-sm rounded-md px-2.5 py-1 transition-colors"
                        @click="groupBy = 'status'"
                    >
                        Por Estado
                    </button>
                    <button
                        type="button"
                        :class="
                            groupBy === 'priority'
                                ? 'bg-surface-container-lowest text-on-surface shadow-sm'
                                : 'text-on-surface-variant'
                        "
                        class="text-label-sm font-label-sm rounded-md px-2.5 py-1 transition-colors"
                        @click="groupBy = 'priority'"
                    >
                        Por Prioridad
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="tasks.length === 0"
            class="py-space-xl flex flex-col items-center gap-2 text-center"
        >
            <AppIcon
                name="checklist"
                :size="28"
                class="text-on-surface-variant"
            />
            <p class="text-body-sm font-body-sm text-on-surface-variant">
                Este sprint aún no tiene tareas.
            </p>
            <p
                v-if="canWrite"
                class="text-label-sm font-label-sm text-on-surface-variant"
            >
                Pulsa "Añadir Tarea" para incorporar la primera.
            </p>
        </div>

        <div
            v-else-if="filteredTasks.length === 0"
            class="py-space-xl flex flex-col items-center gap-2 text-center"
        >
            <AppIcon
                name="search_off"
                :size="28"
                class="text-on-surface-variant"
            />
            <p class="text-body-sm font-body-sm text-on-surface-variant">
                Ninguna tarea coincide con los filtros.
            </p>
            <button
                v-if="hasFilters"
                type="button"
                class="text-label-sm font-label-sm text-primary hover:underline"
                @click="clearFilters"
            >
                Limpiar filtros
            </button>
        </div>

        <div v-else class="gap-space-lg flex flex-col">
            <div
                v-for="group in groups"
                :key="group.key"
                class="gap-space-2xs flex flex-col"
            >
                <div
                    class="gap-space-sm px-space-xs flex items-center justify-between py-1"
                >
                    <span
                        class="text-label-sm font-label-sm text-on-surface flex items-center gap-2 font-semibold"
                    >
                        {{ group.label }}
                        <span
                            class="bg-surface-container text-label-xs font-label-xs text-on-surface-variant rounded-full px-1.5 py-0.5"
                            >{{ group.tasks.length }}</span
                        >
                    </span>
                </div>
                <div
                    class="border-surface-container-low divide-surface-container-low divide-y overflow-hidden rounded-lg border"
                >
                    <TaskRow
                        v-for="task in group.tasks"
                        :key="task.id"
                        :task="task"
                        :can-write="canWrite && task.canUpdate"
                        @open="emit('open', $event)"
                        @edit="emit('edit', $event)"
                        @status="(task, status) => emit('status', task, status)"
                        @comment="emit('comment', $event)"
                        @remove="emit('remove', $event)"
                    />
                </div>
            </div>
        </div>
    </section>
</template>
