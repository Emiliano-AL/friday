<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import Modal from '@/Components/Modal.vue';
import type RichTextEditorComponent from '@/Components/RichText/RichTextEditor.vue';
import type {
    TaskPriorityValue,
    TaskProjectOption,
    TaskStatusValue,
    TaskTypeValue,
} from '@/types/task';
import { destroy, store, update } from '@/routes/tasks';
import { useForm } from '@inertiajs/vue3';
import { computed, defineAsyncComponent, nextTick, ref, watch } from 'vue';

const RichTextEditor = defineAsyncComponent(
    () => import('@/Components/RichText/RichTextEditor.vue'),
);

const props = withDefaults(
    defineProps<{
        open: boolean;
        mode: 'create' | 'edit';
        task?: BoardTaskShape;
        projects: TaskProjectOption[];
        currentUser: { id: number; name: string; avatar: string | null };
        initialStatus?: Exclude<TaskStatusValue, 'done'>;
        initialProject?: { id: number; title: string } | null;
        initialSprint?: { id: number; name: string } | null;
        destroyUrl?: string;
    }>(),
    {
        initialProject: null,
        initialSprint: null,
        destroyUrl: undefined,
    },
);

const emit = defineEmits<{
    close: [];
}>();

interface BoardTaskShape {
    id: number;
    title: string;
    description: string | null;
    type: TaskTypeValue;
    priority: TaskPriorityValue;
    status: TaskStatusValue;
    points?: number | null;
    project: { id: number; title: string } | null;
    sprint: { id: number; name: string } | null;
    assignee: { id: number; name: string } | null;
}

const titleInput = ref<HTMLInputElement | null>(null);
const descriptionEditor = ref<InstanceType<typeof RichTextEditorComponent> | null>(null);
const createAnother = ref(false);

const form = useForm({
    title: '',
    description: '',
    type: 'other' as TaskTypeValue,
    priority: 'medium' as TaskPriorityValue,
    status: 'todo' as TaskStatusValue,
    points: null as number | null,
    project_id: null as number | null,
    sprint_id: null as number | null,
    assignee_id: null as number | null,
});

const selectedProject = computed(
    () =>
        props.projects.find((project) => project.id === form.project_id) ??
        null,
);

const assigneeOptions = computed(() => {
    if (selectedProject.value === null) {
        return [props.currentUser];
    }

    const members = selectedProject.value.members;
    const meIncluded = members.some(
        (member) => member.id === props.currentUser.id,
    );

    return meIncluded ? members : [props.currentUser, ...members];
});

function resetForm(): void {
    form.title = props.task?.title ?? '';
    form.description = props.task?.description ?? '';
    form.type = props.task?.type ?? 'other';
    form.priority = props.task?.priority ?? 'medium';
    form.status =
        props.mode === 'edit'
            ? (props.task?.status ?? 'todo')
            : (props.initialStatus ?? 'todo');
    form.points = props.task?.points ?? null;
    form.project_id =
        props.task?.project?.id ?? props.initialProject?.id ?? null;
    form.sprint_id = props.task?.sprint?.id ?? props.initialSprint?.id ?? null;
    form.assignee_id =
        props.mode === 'edit'
            ? (props.task?.assignee?.id ?? null)
            : form.project_id === null
              ? props.currentUser.id
              : null;
    form.clearErrors();
}

function onPointsInput(event: Event): void {
    const value = (event.target as HTMLInputElement).value;

    form.points = value === '' ? null : Number(value);
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            resetForm();
            await nextTick();
            titleInput.value?.focus();
        }
    },
    { flush: 'sync' },
);

function onProjectChange(): void {
    form.sprint_id = null;

    if (form.project_id === null && form.assignee_id !== props.currentUser.id) {
        form.assignee_id = props.currentUser.id;
    } else if (
        form.project_id !== null &&
        form.assignee_id !== null &&
        selectedProject.value !== null &&
        !selectedProject.value.members.some(
            (member) => member.id === form.assignee_id,
        )
    ) {
        form.assignee_id = null;
    }
}

async function submit(): Promise<void> {
    form.description =
        (await descriptionEditor.value?.save()) ?? form.description ?? '';
    form.clearErrors('description');

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            if (props.mode === 'create' && createAnother.value) {
                form.reset('title', 'description');
                form.clearErrors();
                titleInput.value?.focus();

                return;
            }

            emit('close');
        },
    };

    if (props.mode === 'create') {
        form.post(store.url(), options);

        return;
    }

    if (props.task) {
        form.put(update.url(props.task.id), options);
    }
}

function removeTask(): void {
    if (
        !props.task ||
        !window.confirm('¿Eliminar esta tarea definitivamente?')
    ) {
        return;
    }

    form.delete(props.destroyUrl ?? destroy.url(props.task.id), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}

function onFormKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        submit();
    }
}

const typeOptions: { value: TaskTypeValue; label: string; icon: string }[] = [
    { value: 'other', label: 'Tarea', icon: 'task_alt' },
    { value: 'feature', label: 'Feature', icon: 'new_releases' },
    { value: 'bug', label: 'Bug', icon: 'bug_report' },
    { value: 'test', label: 'Test', icon: 'science' },
];

const typeAccent: Record<TaskTypeValue, string> = {
    other: 'text-on-surface',
    feature: 'text-primary',
    bug: 'text-error',
    test: 'text-tertiary',
};

const selectedTypeLabel = computed(
    () => typeOptions.find((option) => option.value === form.type)?.label,
);

const statusOptions: {
    value: TaskStatusValue;
    label: string;
}[] = [
    { value: 'backlog', label: 'Backlog' },
    { value: 'todo', label: 'Por Hacer' },
    { value: 'in_progress', label: 'En Curso' },
    { value: 'in_review', label: 'En Revisión' },
    { value: 'done', label: 'Hecho' },
];

const visibleStatusOptions = computed(() => {
    if (props.mode === 'edit') {
        return statusOptions;
    }

    return statusOptions.filter((option) =>
        props.initialStatus === 'backlog'
            ? option.value !== 'done'
            : option.value !== 'backlog' && option.value !== 'done',
    );
});

const showPoints = computed(
    () => props.mode === 'edit' || props.initialStatus === 'backlog',
);

const priorityOptions: {
    value: TaskPriorityValue;
    label: string;
}[] = [
    { value: 'low', label: 'Baja' },
    { value: 'medium', label: 'Media' },
    { value: 'high', label: 'Alta' },
    { value: 'urgent', label: 'Urgente' },
];
</script>

<template>
    <Modal :show="open" max-width="2xl" @close="emit('close')">
        <div class="flex max-h-[85vh] flex-col">
            <div
                class="px-space-lg pt-space-lg pb-space-xs flex items-center justify-between"
            >
                <h3 class="text-headline-sm font-headline-sm text-on-surface">
                    {{
                        mode === 'create' ? 'Crear nueva tarea' : 'Editar tarea'
                    }}
                </h3>
                <button
                    type="button"
                    aria-label="Cerrar"
                    class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container -mr-1 flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                    @click="emit('close')"
                >
                    <AppIcon name="close" :size="20" />
                </button>
            </div>

            <form
                class="flex min-h-0 flex-1 flex-col"
                @submit.prevent="submit"
                @keydown="onFormKeydown"
            >
                <div
                    class="gap-space-lg px-space-lg py-space-md flex min-h-0 flex-1 flex-col overflow-y-auto"
                >
                    <div class="flex flex-col gap-1">
                        <label class="sr-only" for="task-title"
                            >Título de la tarea</label
                        >
                        <input
                            id="task-title"
                            ref="titleInput"
                            v-model="form.title"
                            type="text"
                            required
                            maxlength="255"
                            placeholder="¿Qué hay que hacer?"
                            class="text-headline-md! font-headline-md text-on-surface placeholder:text-on-surface-variant/50 border-outline-variant/60 focus:border-primary focus:ring-primary/20 w-full rounded-lg border px-3 py-2 transition-colors focus:ring-2 focus:outline-none"
                            :class="
                                form.errors.title
                                    ? 'border-error ring-error/20 ring-2'
                                    : ''
                            "
                        />
                        <p
                            v-if="form.errors.title"
                            class="text-label-xs font-label-xs text-error"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="gap-x-space-md gap-y-space-md grid grid-cols-2">
                        <div
                            class="col-span-2 flex flex-col gap-1.5 sm:col-span-1"
                        >
                            <label
                                for="task-project"
                                class="text-label-sm font-label-sm text-on-surface"
                            >
                                Proyecto
                            </label>
                            <select
                                id="task-project"
                                v-model="form.project_id"
                                :disabled="initialProject !== null"
                                class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface focus:border-primary focus:ring-primary/20 h-9 w-full cursor-pointer rounded-lg border px-3 pr-8 transition-colors focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                @change="onProjectChange"
                            >
                                <option :value="null">
                                    Sin proyecto (independiente)
                                </option>
                                <option
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="project.id"
                                >
                                    {{ project.title }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.project_id"
                                class="text-label-xs font-label-xs text-error"
                            >
                                {{ form.errors.project_id }}
                            </p>
                        </div>

                        <div
                            class="col-span-2 flex flex-col gap-1.5 sm:col-span-1"
                        >
                            <label
                                for="task-sprint"
                                class="text-label-sm font-label-sm text-on-surface"
                            >
                                Sprint
                            </label>
                            <select
                                id="task-sprint"
                                v-model="form.sprint_id"
                                :disabled="selectedProject === null"
                                :title="
                                    selectedProject === null
                                        ? 'Elige un proyecto para asignar sprint'
                                        : undefined
                                "
                                class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface focus:border-primary focus:ring-primary/20 h-9 w-full cursor-pointer rounded-lg border px-3 pr-8 transition-colors focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option :value="null">Sin sprint</option>
                                <option
                                    v-for="sprint in selectedProject?.sprints ??
                                    []"
                                    :key="sprint.id"
                                    :value="sprint.id"
                                >
                                    {{ sprint.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.sprint_id"
                                class="text-label-xs font-label-xs text-error"
                            >
                                {{ form.errors.sprint_id }}
                            </p>
                        </div>

                        <div
                            class="gap-space-md col-span-2 grid items-end"
                            :class="
                                showPoints
                                    ? 'grid-cols-[auto_minmax(0,1fr)_5rem]'
                                    : 'grid-cols-[auto_minmax(0,1fr)]'
                            "
                        >
                            <div class="flex flex-col gap-1.5">
                                <span
                                    id="task-type-label"
                                    class="text-label-sm font-label-sm text-on-surface"
                                >
                                    Tipo
                                    <span
                                        class="text-on-surface-variant font-normal"
                                        >· {{ selectedTypeLabel }}</span
                                    >
                                </span>
                                <div
                                    class="bg-surface-container-low flex h-9 items-center gap-0.5 rounded-lg p-0.5"
                                    role="radiogroup"
                                    aria-labelledby="task-type-label"
                                >
                                    <button
                                        v-for="option in typeOptions"
                                        :key="option.value"
                                        type="button"
                                        role="radio"
                                        :aria-checked="
                                            form.type === option.value
                                        "
                                        :aria-label="option.label"
                                        :title="option.label"
                                        class="flex h-full w-9 items-center justify-center rounded-md transition-colors"
                                        :class="
                                            form.type === option.value
                                                ? [
                                                      'bg-surface-container-lowest shadow-card',
                                                      typeAccent[option.value],
                                                  ]
                                                : 'text-on-surface-variant hover:text-on-surface'
                                        "
                                        @click="form.type = option.value"
                                    >
                                        <AppIcon
                                            :name="option.icon"
                                            :size="18"
                                        />
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label
                                    for="task-status"
                                    class="text-label-sm font-label-sm text-on-surface"
                                >
                                    {{
                                        mode === 'create'
                                            ? 'Estado inicial'
                                            : 'Estado'
                                    }}
                                </label>
                                <select
                                    id="task-status"
                                    v-model="form.status"
                                    class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface focus:border-primary focus:ring-primary/20 h-9 w-full cursor-pointer rounded-lg border px-3 pr-8 transition-colors focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <option
                                        v-for="option in visibleStatusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <div
                                v-if="showPoints"
                                class="flex flex-col gap-1.5"
                            >
                                <label
                                    for="task-points"
                                    class="text-label-sm font-label-sm text-on-surface"
                                >
                                    Puntos
                                </label>
                                <input
                                    id="task-points"
                                    :value="form.points ?? ''"
                                    type="number"
                                    min="0"
                                    max="99"
                                    placeholder="—"
                                    title="Puntos de estimación (0–99)"
                                    class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface focus:border-primary focus:ring-primary/20 h-9 w-full rounded-lg border px-3 transition-colors focus:ring-2 focus:outline-none"
                                    :class="
                                        form.errors.points ? 'border-error' : ''
                                    "
                                    @input="onPointsInput"
                                />
                                <p
                                    v-if="form.errors.points"
                                    class="text-label-xs font-label-xs text-error"
                                >
                                    {{ form.errors.points }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="col-span-2 flex flex-col gap-1.5 sm:col-span-1"
                        >
                            <span
                                id="task-priority-label"
                                class="text-label-sm font-label-sm text-on-surface"
                            >
                                Prioridad
                            </span>
                            <div
                                class="bg-surface-container-low grid h-9 grid-cols-4 rounded-lg p-0.5"
                                role="radiogroup"
                                aria-labelledby="task-priority-label"
                            >
                                <button
                                    v-for="option in priorityOptions"
                                    :key="option.value"
                                    type="button"
                                    role="radio"
                                    :aria-checked="
                                        form.priority === option.value
                                    "
                                    class="text-label-sm font-label-sm rounded-md transition-colors"
                                    :class="
                                        form.priority === option.value
                                            ? [
                                                  'bg-surface-container-lowest shadow-card font-semibold',
                                                  option.value === 'urgent'
                                                      ? 'text-error'
                                                      : option.value === 'high'
                                                        ? 'text-tertiary'
                                                        : 'text-on-surface',
                                              ]
                                            : 'text-on-surface-variant hover:text-on-surface'
                                    "
                                    @click="form.priority = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <div class="col-span-2 flex flex-col gap-1.5 sm:col-span-1">
                            <label
                                for="task-assignee"
                                class="text-label-sm font-label-sm text-on-surface"
                            >
                                Responsable
                            </label>
                            <select
                                id="task-assignee"
                                v-model="form.assignee_id"
                                class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface focus:border-primary focus:ring-primary/20 h-9 w-full cursor-pointer rounded-lg border px-3 pr-8 transition-colors focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option :value="null">Sin responsable</option>
                                <option
                                    v-for="member in assigneeOptions"
                                    :key="member.id"
                                    :value="member.id"
                                >
                                    {{ member.name }}
                                    {{
                                        member.id === currentUser.id
                                            ? ' (Tú)'
                                            : ''
                                    }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label
                            for="task-description"
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Descripción
                        </label>
                        <RichTextEditor
                            id="task-description"
                            ref="descriptionEditor"
                            :model-value="form.description"
                            :min-height="200"
                            max-height-class="max-h-[420px]"
                            placeholder="Criterios de aceptación, contexto o pruebas..."
                            :error="form.errors.description"
                        />
                    </div>
                </div>

                <div
                    class="border-surface-container gap-space-sm px-space-lg py-space-md flex shrink-0 flex-wrap items-center border-t"
                >
                    <label
                        v-if="mode === 'create'"
                        class="text-label-sm font-label-sm text-on-surface-variant flex cursor-pointer items-center gap-2 select-none"
                    >
                        <input
                            v-model="createAnother"
                            type="checkbox"
                            class="border-outline-variant text-primary h-4 w-4 rounded"
                        />
                        Crear otra al guardar
                    </label>
                    <button
                        v-else
                        type="button"
                        class="text-error hover:bg-error-container/50 text-label-sm font-label-sm -ml-3 flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                        @click="removeTask"
                    >
                        <AppIcon name="delete" :size="16" />
                        Eliminar
                    </button>
                    <div class="gap-space-sm ml-auto flex items-center">
                        <button
                            type="button"
                            class="text-label-md font-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg px-4 py-2 transition-colors"
                            @click="emit('close')"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-primary text-on-primary text-label-md font-label-md hover:bg-primary-container shadow-card flex items-center gap-2 rounded-lg px-4 py-2 transition-colors disabled:opacity-50"
                        >
                            <AppIcon
                                v-if="form.processing"
                                name="progress_activity"
                                :size="16"
                            />
                            {{
                                form.processing
                                    ? 'Guardando...'
                                    : mode === 'create'
                                      ? 'Crear Tarea'
                                      : 'Guardar cambios'
                            }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>
