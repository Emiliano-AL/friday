<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { destroy, edit, status, update } from '@/routes/projects';
import {
    store as storeMember,
    destroy as destroyMember,
} from '@/routes/projects/members';
import {
    store as storeSprint,
    update as updateSprint,
    destroy as destroySprint,
} from '@/routes/projects/sprints';
import {
    store as storeTask,
    update as updateTask,
    destroy as destroyTask,
} from '@/routes/projects/tasks';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BacklogView from '@/Components/Tasks/BacklogView.vue';
import KanbanBoard from '@/Components/Tasks/KanbanBoard.vue';
import TaskCommentsModal from '@/Components/Tasks/TaskCommentsModal.vue';
import TaskList from '@/Components/Tasks/TaskList.vue';
import TaskModal from '@/Components/Tasks/TaskModal.vue';
import type { TaskItem } from '@/Components/Tasks/types';

interface ProjectMember {
    id: number;
    name: string;
    email: string;
}

interface SprintItem {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
}

interface ProjectPayload {
    id: number;
    title: string;
    description: string | null;
    status: string;
    statusLabel: string;
    progress: number;
    owner: ProjectMember;
    members: ProjectMember[];
    sprints: SprintItem[];
    tasks: TaskItem[];
    allowedTransitions: string[];
    isOwner: boolean;
}

const props = defineProps<{
    project: ProjectPayload;
}>();

const page = usePage();

const canWriteSprints = computed(
    () => props.project.isOwner && props.project.status === 'active',
);

const canWriteTasks = computed(() => props.project.status === 'active');

const taskView = ref<'lista' | 'backlog' | 'kanban'>('lista');

const editingTask = ref<TaskItem | null>(null);

const memberOptions = computed(() => [
    props.project.owner,
    ...props.project.members,
]);

const openTaskEdit = (task: TaskItem) => {
    editingTask.value = task;
};

const closeTaskEdit = () => {
    editingTask.value = null;
};

const commentingTask = ref<TaskItem | null>(null);

const commentingTaskFresh = computed(() => {
    if (!commentingTask.value) {
        return null;
    }

    return (
        props.project.tasks.find(
            (task) => task.id === commentingTask.value?.id,
        ) ?? commentingTask.value
    );
});

const openTaskComments = (task: TaskItem) => {
    commentingTask.value = task;
};

const closeTaskComments = () => {
    commentingTask.value = null;
};

const confirmTaskDestroy = (task: TaskItem) => {
    if (
        window.confirm(
            `¿Eliminar la tarea "${task.title}"? Esta acción no se puede deshacer.`,
        )
    ) {
        router.delete(destroyTask.url([props.project.id, task.id]), {
            preserveScroll: true,
        });
    }
};

const moveTask = ({ task, status }: { task: TaskItem; status: string }) => {
    router.put(
        updateTask.url([props.project.id, task.id]),
        {
            title: task.title,
            description: task.description,
            type: task.type,
            priority: task.priority,
            status,
            assignee_id: task.assignee ? task.assignee.id : null,
            sprint_id: task.sprint ? task.sprint.id : null,
        },
        { preserveScroll: true },
    );
};

const taskForm = useForm({
    title: '',
    description: '',
    type: 'other',
    priority: 'medium',
    assignee_id: '',
    sprint_id: '',
});

const submitTask = (projectId: number) => {
    taskForm
        .transform((data) => ({
            ...data,
            assignee_id: data.assignee_id === '' ? null : data.assignee_id,
            sprint_id: data.sprint_id === '' ? null : data.sprint_id,
        }))
        .post(storeTask.url(projectId), {
            onSuccess: () => taskForm.reset('title', 'description'),
            preserveScroll: true,
        });
};

const memberForm = useForm({
    email: '',
});

const sprintForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
});

const editingSprint = ref<SprintItem | null>(null);

const sprintEditForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
});

const openSprintEdit = (sprint: SprintItem) => {
    sprintEditForm.name = sprint.name;
    sprintEditForm.start_date = sprint.startDate;
    sprintEditForm.end_date = sprint.endDate;
    editingSprint.value = sprint;
};

const closeSprintEdit = () => {
    editingSprint.value = null;
    sprintEditForm.reset();
    sprintEditForm.clearErrors();
};

const submitSprintEdit = (projectId: number) => {
    if (!editingSprint.value) {
        return;
    }

    sprintEditForm.put(updateSprint.url([projectId, editingSprint.value.id]), {
        onSuccess: closeSprintEdit,
        preserveScroll: true,
    });
};

const transitionLabels: Record<string, string> = {
    active: 'Reactivar',
    archived: 'Archivar',
    completed: 'Completar',
};

const submitMember = (projectId: number) => {
    memberForm.post(storeMember.url(projectId), {
        onSuccess: () => memberForm.reset('email'),
        preserveScroll: true,
    });
};

const submitSprint = (projectId: number) => {
    sprintForm.post(storeSprint.url(projectId), {
        onSuccess: () => sprintForm.reset('name', 'start_date', 'end_date'),
        preserveScroll: true,
    });
};

const removeMember = (projectId: number, userId: number) => {
    router.delete(destroyMember.url([projectId, userId]), {
        preserveScroll: true,
    });
};

const confirmDestroy = (project: ProjectPayload) => {
    if (
        window.confirm(
            `¿Eliminar el proyecto "${project.title}"? Esta acción no se puede deshacer.`,
        )
    ) {
        router.delete(destroy.url(project.id));
    }
};

const confirmSprintDestroy = (projectId: number, sprint: SprintItem) => {
    if (
        window.confirm(
            `¿Eliminar el sprint "${sprint.name}"? Esta acción no se puede deshacer.`,
        )
    ) {
        router.delete(destroySprint.url([projectId, sprint.id]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="project.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    {{ project.title }}
                </h2>
                <Link
                    v-if="project.isOwner && project.status === 'active'"
                    :href="edit.url(project.id)"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700"
                >
                    Editar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="project.status !== 'active'"
                    class="rounded-md bg-gray-100 px-4 py-3 text-sm text-gray-700"
                >
                    Este proyecto está {{ project.statusLabel.toLowerCase() }} y
                    es de solo lectura.
                </div>

                <div
                    v-if="page.props.errors.project"
                    class="rounded-md bg-red-100 px-4 py-3 text-sm text-red-700"
                >
                    {{ page.props.errors.project }}
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="space-y-4 p-6">
                        <div class="flex items-center justify-between">
                            <span
                                class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700"
                            >
                                {{ project.statusLabel }}
                            </span>
                            <div class="flex w-64 items-center">
                                <div class="h-2 w-full rounded bg-gray-200">
                                    <div
                                        class="h-2 rounded bg-gray-800"
                                        :style="{
                                            width: project.progress + '%',
                                        }"
                                    ></div>
                                </div>
                                <span class="ms-3 text-sm text-gray-600">
                                    {{ project.progress }}%
                                </span>
                            </div>
                        </div>

                        <p v-if="project.description" class="text-gray-700">
                            {{ project.description }}
                        </p>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">
                                Miembros
                            </h3>
                            <ul class="mt-2 space-y-1">
                                <li class="text-sm text-gray-900">
                                    {{ project.owner.name }}
                                    <span class="text-gray-500"
                                        >(propietario)</span
                                    >
                                </li>
                                <li
                                    v-for="member in project.members"
                                    :key="member.id"
                                    class="flex items-center justify-between text-sm text-gray-900"
                                >
                                    <span>{{ member.name }}</span>
                                    <button
                                        v-if="project.isOwner"
                                        type="button"
                                        class="text-xs text-red-600 hover:underline"
                                        @click="
                                            removeMember(project.id, member.id)
                                        "
                                    >
                                        Retirar
                                    </button>
                                </li>
                                <li
                                    v-if="project.members.length === 0"
                                    class="text-sm text-gray-500"
                                >
                                    Sin colaboradores todavía.
                                </li>
                            </ul>

                            <form
                                v-if="project.isOwner"
                                class="mt-4 flex max-w-md items-start gap-2"
                                @submit.prevent="submitMember(project.id)"
                            >
                                <div class="flex-1">
                                    <TextInput
                                        id="member-email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="memberForm.email"
                                        placeholder="correo@ejemplo.com"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="memberForm.errors.email"
                                    />
                                </div>
                                <PrimaryButton
                                    class="mt-1"
                                    :class="{
                                        'opacity-25': memberForm.processing,
                                    }"
                                    :disabled="memberForm.processing"
                                >
                                    Añadir
                                </PrimaryButton>
                            </form>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">
                                Sprints
                            </h3>
                            <ul class="mt-2 space-y-1">
                                <li
                                    v-for="sprint in project.sprints"
                                    :key="sprint.id"
                                    class="flex items-center justify-between text-sm text-gray-900"
                                >
                                    <span>
                                        {{ sprint.name }}
                                        <span class="text-gray-500"
                                            >({{ sprint.startDate }} —
                                            {{ sprint.endDate }})</span
                                        >
                                    </span>
                                    <span
                                        v-if="canWriteSprints"
                                        class="flex gap-3"
                                    >
                                        <button
                                            type="button"
                                            class="text-xs text-gray-600 hover:underline"
                                            @click="openSprintEdit(sprint)"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs text-red-600 hover:underline"
                                            @click="
                                                confirmSprintDestroy(
                                                    project.id,
                                                    sprint,
                                                )
                                            "
                                        >
                                            Eliminar
                                        </button>
                                    </span>
                                </li>
                                <li
                                    v-if="project.sprints.length === 0"
                                    class="text-sm text-gray-500"
                                >
                                    Sin sprints todavía.
                                </li>
                            </ul>

                            <form
                                v-if="canWriteSprints"
                                class="mt-4 flex max-w-xl items-end gap-2"
                                @submit.prevent="submitSprint(project.id)"
                            >
                                <div class="flex-1">
                                    <InputLabel
                                        for="sprint-name"
                                        value="Nombre"
                                    />
                                    <TextInput
                                        id="sprint-name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="sprintForm.name"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="sprintForm.errors.name"
                                    />
                                </div>
                                <div>
                                    <InputLabel
                                        for="sprint-start"
                                        value="Inicio"
                                    />
                                    <TextInput
                                        id="sprint-start"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="sprintForm.start_date"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="sprintForm.errors.start_date"
                                    />
                                </div>
                                <div>
                                    <InputLabel for="sprint-end" value="Fin" />
                                    <TextInput
                                        id="sprint-end"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="sprintForm.end_date"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="sprintForm.errors.end_date"
                                    />
                                </div>
                                <PrimaryButton
                                    :class="{
                                        'opacity-25': sprintForm.processing,
                                    }"
                                    :disabled="sprintForm.processing"
                                >
                                    Añadir
                                </PrimaryButton>
                            </form>
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-500">
                                    Tareas
                                </h3>
                                <div class="flex gap-1 text-xs">
                                    <button
                                        type="button"
                                        class="rounded px-2 py-1"
                                        :class="
                                            taskView === 'lista'
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-600 hover:bg-gray-100'
                                        "
                                        @click="taskView = 'lista'"
                                    >
                                        Lista
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded px-2 py-1"
                                        :class="
                                            taskView === 'backlog'
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-600 hover:bg-gray-100'
                                        "
                                        @click="taskView = 'backlog'"
                                    >
                                        Backlog
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded px-2 py-1"
                                        :class="
                                            taskView === 'kanban'
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-600 hover:bg-gray-100'
                                        "
                                        @click="taskView = 'kanban'"
                                    >
                                        Kanban
                                    </button>
                                </div>
                            </div>

                            <template v-if="taskView === 'lista'">
                                <TaskList
                                    :tasks="project.tasks"
                                    :editable="canWriteTasks"
                                    :deletable="
                                        project.isOwner && canWriteTasks
                                    "
                                    @edit="openTaskEdit"
                                    @comment="openTaskComments"
                                    @remove="confirmTaskDestroy"
                                />

                                <form
                                    v-if="canWriteTasks"
                                    class="mt-4 max-w-2xl space-y-3"
                                    @submit.prevent="submitTask(project.id)"
                                >
                                    <div>
                                        <InputLabel
                                            for="task-title"
                                            value="Título"
                                        />
                                        <TextInput
                                            id="task-title"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="taskForm.title"
                                            required
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="taskForm.errors.title"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            for="task-description"
                                            value="Descripción (opcional, markdown)"
                                        />
                                        <TextInput
                                            id="task-description"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="taskForm.description"
                                        />
                                        <InputError
                                            class="mt-2"
                                            :message="
                                                taskForm.errors.description
                                            "
                                        />
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <div>
                                            <InputLabel
                                                for="task-type"
                                                value="Tipo"
                                            />
                                            <select
                                                id="task-type"
                                                v-model="taskForm.type"
                                                class="mt-1 block rounded-md border-gray-300 shadow-sm"
                                            >
                                                <option value="other">
                                                    Otro
                                                </option>
                                                <option value="feature">
                                                    Feature
                                                </option>
                                                <option value="bug">Bug</option>
                                                <option value="test">
                                                    Test
                                                </option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="taskForm.errors.type"
                                            />
                                        </div>
                                        <div>
                                            <InputLabel
                                                for="task-priority"
                                                value="Prioridad"
                                            />
                                            <select
                                                id="task-priority"
                                                v-model="taskForm.priority"
                                                class="mt-1 block rounded-md border-gray-300 shadow-sm"
                                            >
                                                <option value="medium">
                                                    Media
                                                </option>
                                                <option value="low">
                                                    Baja
                                                </option>
                                                <option value="high">
                                                    Alta
                                                </option>
                                                <option value="urgent">
                                                    Urgente
                                                </option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    taskForm.errors.priority
                                                "
                                            />
                                        </div>
                                        <div>
                                            <InputLabel
                                                for="task-assignee"
                                                value="Responsable"
                                            />
                                            <select
                                                id="task-assignee"
                                                v-model="taskForm.assignee_id"
                                                class="mt-1 block rounded-md border-gray-300 shadow-sm"
                                            >
                                                <option value="">
                                                    Sin responsable
                                                </option>
                                                <option
                                                    :value="project.owner.id"
                                                >
                                                    {{ project.owner.name }}
                                                </option>
                                                <option
                                                    v-for="member in project.members"
                                                    :key="member.id"
                                                    :value="member.id"
                                                >
                                                    {{ member.name }}
                                                </option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    taskForm.errors.assignee_id
                                                "
                                            />
                                        </div>
                                        <div>
                                            <InputLabel
                                                for="task-sprint"
                                                value="Sprint"
                                            />
                                            <select
                                                id="task-sprint"
                                                v-model="taskForm.sprint_id"
                                                class="mt-1 block rounded-md border-gray-300 shadow-sm"
                                            >
                                                <option value="">
                                                    Sin sprint
                                                </option>
                                                <option
                                                    v-for="sprint in project.sprints"
                                                    :key="sprint.id"
                                                    :value="sprint.id"
                                                >
                                                    {{ sprint.name }}
                                                </option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="
                                                    taskForm.errors.sprint_id
                                                "
                                            />
                                        </div>
                                        <PrimaryButton
                                            :class="{
                                                'opacity-25':
                                                    taskForm.processing,
                                            }"
                                            :disabled="taskForm.processing"
                                        >
                                            Añadir
                                        </PrimaryButton>
                                    </div>
                                </form>
                            </template>
                            <BacklogView
                                v-else-if="taskView === 'backlog'"
                                :tasks="project.tasks"
                                :sprints="project.sprints"
                            />
                            <KanbanBoard
                                v-else-if="taskView === 'kanban'"
                                :tasks="project.tasks"
                                :project-id="project.id"
                                :can-write="canWriteTasks"
                                @moved="moveTask"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-if="project.isOwner"
                    class="flex items-center justify-between"
                >
                    <div class="flex gap-2">
                        <SecondaryButton
                            v-for="target in project.allowedTransitions"
                            :key="target"
                            @click="
                                router.put(status.url(project.id), {
                                    status: target,
                                })
                            "
                        >
                            {{ transitionLabels[target] ?? target }}
                        </SecondaryButton>
                    </div>
                    <DangerButton @click="confirmDestroy(project)">
                        Eliminar proyecto
                    </DangerButton>
                </div>

                <Modal :show="editingSprint !== null" @close="closeSprintEdit">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            Editar sprint
                        </h2>

                        <form
                            class="mt-4 space-y-4"
                            @submit.prevent="submitSprintEdit(project.id)"
                        >
                            <div>
                                <InputLabel
                                    for="edit-sprint-name"
                                    value="Nombre"
                                />
                                <TextInput
                                    id="edit-sprint-name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="sprintEditForm.name"
                                    required
                                />
                                <InputError
                                    class="mt-2"
                                    :message="sprintEditForm.errors.name"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    for="edit-sprint-start"
                                    value="Inicio"
                                />
                                <TextInput
                                    id="edit-sprint-start"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="sprintEditForm.start_date"
                                    required
                                />
                                <InputError
                                    class="mt-2"
                                    :message="sprintEditForm.errors.start_date"
                                />
                            </div>
                            <div>
                                <InputLabel for="edit-sprint-end" value="Fin" />
                                <TextInput
                                    id="edit-sprint-end"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="sprintEditForm.end_date"
                                    required
                                />
                                <InputError
                                    class="mt-2"
                                    :message="sprintEditForm.errors.end_date"
                                />
                            </div>

                            <div class="flex justify-end gap-2">
                                <SecondaryButton
                                    type="button"
                                    @click="closeSprintEdit"
                                >
                                    Cancelar
                                </SecondaryButton>
                                <PrimaryButton
                                    :class="{
                                        'opacity-25': sprintEditForm.processing,
                                    }"
                                    :disabled="sprintEditForm.processing"
                                >
                                    Guardar
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>

                <TaskModal
                    :show="editingTask !== null"
                    :task="editingTask"
                    :project-id="project.id"
                    :members="memberOptions"
                    :sprints="project.sprints"
                    @close="closeTaskEdit"
                />

                <TaskCommentsModal
                    :show="commentingTask !== null"
                    :task="commentingTaskFresh"
                    :project-id="project.id"
                    :can-comment="canWriteTasks"
                    @close="closeTaskComments"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
