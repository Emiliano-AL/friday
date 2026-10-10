<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import CollapsibleSection from '@/Components/CollapsibleSection.vue';
import Modal from '@/Components/Modal.vue';
import RichTextEditor from '@/Components/RichText/RichTextEditor.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type {
    TaskPriorityValue,
    TaskProjectOption,
    TaskStatusValue,
    TaskTypeValue,
} from '@/types/task';
import { destroy, store, update } from '@/routes/tasks';
import { useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

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
const descriptionEditor = ref<InstanceType<typeof RichTextEditor> | null>(null);
const createAnother = ref(false);
const linkageOpen = ref(false);
const estimationOpen = ref(false);

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

    linkageOpen.value = form.project_id !== null || form.sprint_id !== null;
    estimationOpen.value = false;
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

watch(
    () => [form.errors.project_id, form.errors.sprint_id],
    ([projectError, sprintError]) => {
        if (projectError || sprintError) {
            linkageOpen.value = true;
        }
    },
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
    form.description = (await descriptionEditor.value?.save()) ?? '';
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
                class="px-space-lg pt-space-lg pb-space-sm flex items-start justify-between"
            >
                <div class="gap-space-sm flex items-center">
                    <div
                        class="bg-surface-container text-primary flex h-8 w-8 items-center justify-center rounded-lg"
                    >
                        <AppIcon name="add_task" :size="18" />
                    </div>
                    <h3
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        {{
                            mode === 'create'
                                ? 'Crear nueva tarea'
                                : 'Editar tarea'
                        }}
                    </h3>
                </div>
                <button
                    type="button"
                    aria-label="Cerrar"
                    class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
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
                    class="gap-space-md px-space-lg py-space-md flex min-h-0 flex-1 flex-col overflow-y-auto"
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
                            placeholder="Título de la tarea o acción..."
                            class="text-headline-md font-headline-md placeholder:text-outline/60 focus:ring-primary/20 bg-surface-container-low/60 hover:bg-surface-container-low focus:bg-surface-container-lowest px-space-md focus:border-primary/40 w-full rounded-xl border border-transparent py-2.5 transition-all focus:ring-2 focus:outline-none"
                            :class="form.errors.title ? 'border-error' : ''"
                        />
                        <p
                            v-if="form.errors.title"
                            class="text-label-xs font-label-xs text-error px-space-xs"
                        >
                            {{ form.errors.title }}
                        </p>
                        <p
                            v-else
                            class="text-label-xs font-label-xs text-on-surface-variant px-space-xs flex items-center gap-1.5 pt-0.5"
                        >
                            <AppIcon
                                name="keyboard_return"
                                :size="14"
                                class="text-primary"
                            />
                            Usa una descripción imperativa y clara para el
                            backlog del equipo.
                        </p>
                    </div>

                    <div
                        class="border-outline-variant/30 bg-surface-container-low/80 gap-space-sm p-space-md grid grid-cols-1 rounded-xl border sm:grid-cols-2"
                        :class="
                            showPoints ? 'lg:grid-cols-5' : 'lg:grid-cols-4'
                        "
                    >
                        <div class="flex flex-col gap-1.5">
                            <label
                                for="task-type"
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                            >
                                <AppIcon name="category" :size="15" />
                                Tipo
                            </label>
                            <select
                                id="task-type"
                                v-model="form.type"
                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full cursor-pointer appearance-none rounded-lg border px-3 pr-8 shadow-xs focus:ring-2 focus:outline-none"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label
                                for="task-status"
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                            >
                                <AppIcon name="swap_horiz" :size="15" />
                                {{
                                    mode === 'create'
                                        ? 'Estado inicial'
                                        : 'Estado'
                                }}
                            </label>
                            <select
                                id="task-status"
                                v-model="form.status"
                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full cursor-pointer appearance-none rounded-lg border px-3 pr-8 shadow-xs focus:ring-2 focus:outline-none"
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

                        <div class="flex flex-col gap-1.5">
                            <span
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                            >
                                <AppIcon name="flag" :size="15" />
                                Prioridad
                            </span>
                            <div
                                class="border-outline-variant/40 bg-surface-container-lowest flex h-9 items-center rounded-lg border p-0.5 shadow-xs"
                            >
                                <button
                                    v-for="option in priorityOptions"
                                    :key="option.value"
                                    type="button"
                                    class="text-label-xs font-label-xs h-full flex-1 rounded transition-colors"
                                    :class="
                                        form.priority === option.value
                                            ? option.value === 'urgent'
                                                ? 'bg-error-container text-on-error-container font-semibold'
                                                : option.value === 'high'
                                                  ? 'bg-tertiary-fixed text-on-tertiary-fixed font-semibold'
                                                  : 'bg-primary-fixed text-on-primary-fixed font-semibold'
                                            : 'text-on-secondary-container hover:text-on-surface'
                                    "
                                    @click="form.priority = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </div>

                        <div v-if="showPoints" class="flex flex-col gap-1.5">
                            <label
                                for="task-points"
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                            >
                                <AppIcon name="speed" :size="15" />
                                Puntos
                            </label>
                            <input
                                id="task-points"
                                :value="form.points ?? ''"
                                type="number"
                                min="0"
                                max="99"
                                placeholder="0-99"
                                title="Puntos de estimación (0-99)"
                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
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

                        <div class="flex flex-col gap-1.5">
                            <label
                                for="task-assignee"
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                            >
                                <AppIcon name="person" :size="15" />
                                Responsable
                            </label>
                            <select
                                id="task-assignee"
                                v-model="form.assignee_id"
                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full cursor-pointer appearance-none rounded-lg border px-2 shadow-xs focus:ring-2 focus:outline-none"
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

                    <CollapsibleSection
                        v-model:open="linkageOpen"
                        title="Vinculación y alcance"
                        icon="account_tree"
                    >
                        <template #badge>
                            <UiBadge label="Proyecto opcional" tone="neutral" />
                        </template>
                        <div
                            class="gap-space-md grid grid-cols-1 pt-1 md:grid-cols-2"
                        >
                            <div class="flex flex-col gap-1">
                                <label
                                    for="task-project"
                                    class="text-label-sm font-label-sm text-on-surface font-medium"
                                >
                                    Proyecto
                                </label>
                                <select
                                    id="task-project"
                                    v-model="form.project_id"
                                    :disabled="initialProject !== null"
                                    class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full cursor-pointer appearance-none rounded-lg border px-3 pr-8 shadow-xs focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                    @change="onProjectChange"
                                >
                                    <option :value="null">
                                        Sin Proyecto (Tarea independiente)
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
                                    class="text-label-xs font-label-xs text-on-surface-variant mt-0.5 flex items-center gap-1.5"
                                >
                                    <span
                                        class="bg-primary inline-block h-1.5 w-1.5 shrink-0 rounded-full"
                                    />
                                    Las tareas no requieren pertenecer a un
                                    proyecto.
                                </p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label
                                    for="task-sprint"
                                    class="text-label-sm font-label-sm text-on-surface font-medium"
                                >
                                    Sprint
                                </label>
                                <select
                                    id="task-sprint"
                                    v-model="form.sprint_id"
                                    :disabled="selectedProject === null"
                                    class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full cursor-pointer appearance-none rounded-lg border px-3 pr-8 shadow-xs focus:ring-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    <option :value="null">Sin Sprint</option>
                                    <option
                                        v-for="sprint in selectedProject?.sprints ??
                                        []"
                                        :key="sprint.id"
                                        :value="sprint.id"
                                    >
                                        {{ sprint.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </CollapsibleSection>

                    <CollapsibleSection
                        v-model:open="estimationOpen"
                        title="Fechas"
                        icon="event"
                    >
                        <template #badge>
                            <UiBadge label="Próximamente" tone="outline" />
                        </template>
                        <div class="gap-space-xs flex flex-col">
                            <div class="gap-space-md grid grid-cols-1">
                                <div class="flex flex-col gap-1">
                                    <label
                                        class="text-label-sm font-label-sm text-on-surface-variant"
                                    >
                                        Fecha de Vencimiento
                                    </label>
                                    <input
                                        type="date"
                                        disabled
                                        class="border-outline-variant/40 bg-surface-container-lowest text-body-sm font-body-sm text-on-surface-variant h-9 w-full cursor-not-allowed rounded-lg border px-3 opacity-60 shadow-xs"
                                    />
                                </div>
                            </div>
                            <p
                                class="text-label-xs font-label-xs text-on-surface-variant"
                            >
                                Más tipos de tarea (seguridad, mejora,
                                documentación) llegarán próximamente.
                            </p>
                        </div>
                    </CollapsibleSection>

                    <div class="flex flex-col gap-1.5">
                        <label
                            for="task-description"
                            class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                        >
                            <AppIcon name="notes" :size="15" />
                            Descripción
                        </label>
                        <RichTextEditor
                            id="task-description"
                            ref="descriptionEditor"
                            :model-value="form.description"
                            :min-height="240"
                            max-height-class="max-h-[420px]"
                            placeholder="Describe criterios de aceptación, contexto o pruebas..."
                            :error="form.errors.description"
                        />
                    </div>
                </div>

                <div
                    class="border-outline-variant/30 bg-surface-container-low/90 gap-space-sm px-space-lg py-space-md flex shrink-0 flex-col border-t sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center">
                        <label
                            v-if="mode === 'create'"
                            class="text-label-sm font-label-sm text-on-surface flex cursor-pointer items-center gap-2 select-none"
                        >
                            <input
                                v-model="createAnother"
                                type="checkbox"
                                class="border-outline-variant text-primary h-4 w-4 rounded"
                            />
                            Crear otra tarea al guardar
                        </label>
                        <button
                            v-else
                            type="button"
                            class="text-error hover:bg-error-container/50 text-label-sm font-label-sm flex items-center gap-1.5 rounded-lg px-3 py-2 transition-colors"
                            @click="removeTask"
                        >
                            <AppIcon name="delete" :size="16" />
                            Eliminar tarea
                        </button>
                    </div>
                    <div class="gap-space-sm flex items-center justify-end">
                        <button
                            type="button"
                            class="text-label-md font-label-md border-outline-variant/40 bg-surface-container-lowest text-on-surface hover:bg-surface-container px-space-md flex items-center gap-1.5 rounded-lg border py-2 transition-colors"
                            @click="emit('close')"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-primary text-on-primary text-label-md font-label-md hover:bg-primary-container px-space-lg flex items-center gap-2 rounded-lg py-2 shadow-md transition-all disabled:opacity-50"
                        >
                            <AppIcon
                                v-if="form.processing"
                                name="progress_activity"
                                :size="16"
                            />
                            <AppIcon v-else name="add_task" :size="18" />
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
