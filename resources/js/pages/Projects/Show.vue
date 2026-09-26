<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import AvatarStack from '@/Components/Projects/AvatarStack.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';
import ProjectTabs from '@/Components/Projects/ProjectTabs.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import { index as projectsIndex, status } from '@/routes/projects';
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
import { computed, onMounted, ref } from 'vue';
import BacklogView from '@/Components/Tasks/BacklogView.vue';
import KanbanBoard from '@/Components/Tasks/KanbanBoard.vue';
import TaskCommentsModal from '@/Components/Tasks/TaskCommentsModal.vue';
import TaskList from '@/Components/Tasks/TaskList.vue';
import TaskModal from '@/Components/Tasks/TaskModal.vue';
import type { TaskItem } from '@/Components/Tasks/types';
import type { ProjectDetail, SprintSummary } from '@/types/project';

const props = defineProps<{
    project: ProjectDetail;
}>();

const page = usePage();

const canWriteSprints = computed(
    () => props.project.isOwner && props.project.status === 'active',
);

const canWriteTasks = computed(() => props.project.status === 'active');

const activeTab = ref<'general' | 'sprints'>('general');

const editModalOpen = ref(false);

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

const editingSprint = ref<SprintSummary | null>(null);

const sprintEditForm = useForm({
    name: '',
    start_date: '',
    end_date: '',
});

const openSprintEdit = (sprint: SprintSummary) => {
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

const statusTone = computed<'primary' | 'tertiary' | 'outline'>(() => {
    switch (props.project.status) {
        case 'completed':
            return 'tertiary';
        case 'archived':
            return 'outline';
        default:
            return 'primary';
    }
});

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

const confirmSprintDestroy = (projectId: number, sprint: SprintSummary) => {
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

const goToSprintsTab = () => {
    activeTab.value = 'sprints';
};

const activeSprintTasks = computed(() => {
    if (!props.project.activeSprint) {
        return [];
    }

    return props.project.tasks.filter(
        (task) => task.sprint?.id === props.project.activeSprint?.id,
    );
});

const activeSprintDoneCount = computed(
    () =>
        activeSprintTasks.value.filter((task) => task.status === 'done').length,
);

const activeSprintProgress = computed(() => {
    if (activeSprintTasks.value.length === 0) {
        return 0;
    }

    return Math.round(
        (activeSprintDoneCount.value / activeSprintTasks.value.length) * 100,
    );
});

const nextSprint = computed<SprintSummary | null>(() => {
    const sprints = props.project.sprints.filter(
        (sprint) => sprint.id !== props.project.activeSprint?.id,
    );

    if (sprints.length === 0) {
        return null;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const upcoming = sprints.find(
        (sprint) => new Date(`${sprint.startDate}T00:00:00`) > today,
    );

    return upcoming ?? sprints[sprints.length - 1];
});

const nextSprintTasks = computed(() => {
    if (!nextSprint.value) {
        return [];
    }

    return props.project.tasks.filter(
        (task) => task.sprint?.id === nextSprint.value?.id,
    );
});

const backlogTaskCount = computed(
    () =>
        props.project.tasks.filter((task) => task.status === 'backlog').length,
);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('edit') === '1' && props.project.isOwner) {
        editModalOpen.value = true;
    }

    if (params.get('edit') !== null) {
        window.history.replaceState({}, '', window.location.pathname);
    }
});
</script>

<template>
    <Head :title="project.title" />

    <AuthenticatedLayout>
        <div
            class="gap-space-lg py-space-md mx-auto flex w-full max-w-7xl flex-col"
        >
            <div
                v-if="project.status !== 'active'"
                class="bg-surface-container-low text-on-surface-variant gap-space-sm p-space-md text-body-sm font-body-sm flex items-center rounded-xl"
                role="status"
            >
                <AppIcon name="lock" :size="16" />
                <span>
                    Este proyecto está
                    {{ project.statusLabel.toLowerCase() }} y es de solo
                    lectura.
                </span>
            </div>

            <div
                v-if="page.props.errors.project"
                class="bg-error-container text-on-error-container p-space-md text-body-sm font-body-sm rounded-xl"
                role="alert"
            >
                {{ page.props.errors.project }}
            </div>

            <div
                class="gap-space-md flex flex-wrap items-center justify-between"
            >
                <nav
                    aria-label="Migas de pan"
                    class="text-body-sm font-body-sm gap-space-xs flex min-w-0 items-center"
                >
                    <Link
                        :href="projectsIndex.url()"
                        class="text-on-surface-variant hover:text-on-surface flex items-center gap-1 transition-colors"
                    >
                        <AppIcon name="folder" :size="16" />
                        <span>Proyectos</span>
                    </Link>
                    <span aria-hidden="true" class="text-on-surface-variant/40">
                        /
                    </span>
                    <span
                        class="text-on-surface min-w-0 truncate font-semibold"
                    >
                        {{ project.title }}
                    </span>
                </nav>

                <div class="gap-space-xs flex flex-wrap items-center">
                    <button
                        type="button"
                        disabled
                        title="Disponible próximamente"
                        class="bg-surface-container-lowest text-on-surface-variant shadow-card text-label-sm font-label-sm gap-space-xs flex cursor-not-allowed items-center rounded-lg px-3 py-1.5 opacity-60"
                    >
                        <AppIcon name="share" :size="16" />
                        <span>Compartir</span>
                        <UiBadge label="Próximamente" tone="outline" />
                    </button>

                    <button
                        v-if="project.isOwner && project.status === 'active'"
                        type="button"
                        class="bg-surface-container-lowest text-on-surface hover:bg-surface-container-high shadow-card text-label-sm font-label-sm gap-space-xs flex items-center rounded-lg px-3 py-1.5 transition-colors"
                        @click="editModalOpen = true"
                    >
                        <AppIcon name="settings" :size="16" />
                        <span>Ajustes</span>
                    </button>

                    <button
                        type="button"
                        class="bg-surface-container-lowest text-on-surface hover:bg-surface-container-high shadow-card text-label-sm font-label-sm gap-space-xs flex items-center rounded-lg px-3 py-1.5 transition-colors"
                        @click="goToSprintsTab"
                    >
                        <AppIcon name="pace" :size="16" />
                        <span>Nuevo Sprint</span>
                    </button>

                    <template v-if="project.isOwner">
                        <button
                            v-for="target in project.allowedTransitions"
                            :key="target"
                            type="button"
                            class="bg-surface-container-lowest text-on-surface hover:bg-surface-container-high shadow-card text-label-sm font-label-sm gap-space-xs flex items-center rounded-lg px-3 py-1.5 transition-colors"
                            @click="
                                router.put(status.url(project.id), {
                                    status: target,
                                })
                            "
                        >
                            {{ transitionLabels[target] ?? target }}
                        </button>
                    </template>

                    <button
                        type="button"
                        class="bg-primary hover:bg-primary-container text-on-primary shadow-card text-label-md font-label-md gap-space-xs flex items-center rounded-lg px-3.5 py-1.5 transition-colors"
                        @click="goToSprintsTab"
                    >
                        <AppIcon name="add" :size="16" />
                        <span>Nueva Tarea</span>
                    </button>
                </div>
            </div>

            <div class="gap-space-md flex flex-wrap items-center">
                <div
                    class="bg-primary text-on-primary flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                >
                    <AppIcon name="folder" :size="26" />
                </div>
                <div class="min-w-0 flex-1">
                    <h1
                        class="text-headline-xl font-headline-xl text-on-surface"
                    >
                        {{ project.title }}
                    </h1>
                    <p
                        v-if="project.description"
                        class="text-body-sm font-body-sm text-on-surface-variant mt-1"
                    >
                        {{ project.description }}
                    </p>
                </div>
                <div class="gap-space-xs flex flex-wrap items-center">
                    <UiBadge
                        :label="project.statusLabel"
                        :tone="statusTone"
                        dot
                    />
                    <UiBadge
                        v-if="project.activeSprint"
                        :label="`${project.statusLabel} • ${project.activeSprint.name}`"
                        tone="neutral"
                    />
                </div>
            </div>

            <section
                aria-label="Resumen del proyecto"
                class="bg-surface-container-lowest shadow-card gap-space-md p-space-md grid grid-cols-2 rounded-xl md:grid-cols-4"
            >
                <div class="gap-space-sm flex items-center">
                    <Avatar
                        :name="project.owner.name"
                        :src="project.owner.avatar"
                        :size="36"
                    />
                    <div class="min-w-0">
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant uppercase"
                        >
                            Líder de Proyecto
                        </p>
                        <p
                            class="text-label-md font-label-md text-on-surface truncate font-semibold"
                        >
                            {{ project.owner.name }}
                        </p>
                    </div>
                </div>

                <div class="gap-space-sm flex items-center">
                    <AvatarStack :people="memberOptions" :max="3" />
                    <div class="min-w-0">
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant uppercase"
                        >
                            Equipo Asignado
                        </p>
                        <p
                            class="text-label-md font-label-md text-on-surface font-semibold"
                        >
                            {{ 1 + project.members.length }} Especialistas
                        </p>
                    </div>
                </div>

                <div class="gap-space-xs flex flex-col justify-center">
                    <div
                        class="gap-space-sm flex items-baseline justify-between"
                    >
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant uppercase"
                        >
                            Progreso Global
                        </p>
                        <p
                            class="text-label-md font-label-md text-primary font-semibold"
                        >
                            {{ project.progress }}%
                        </p>
                    </div>
                    <div
                        class="bg-surface-container-high h-2 w-full rounded-full"
                    >
                        <div
                            class="bg-primary h-full rounded-full"
                            :style="{ width: `${project.progress}%` }"
                        />
                    </div>
                    <p
                        class="text-label-xs font-label-xs text-on-surface-variant"
                    >
                        {{ project.taskDoneCount }} de
                        {{ project.taskTotalCount }} tareas concluidas
                    </p>
                </div>

                <div class="gap-space-sm flex items-center">
                    <div
                        class="bg-surface-container text-on-surface-variant flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                    >
                        <AppIcon name="calendar_today" :size="18" />
                    </div>
                    <div>
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant uppercase"
                        >
                            Fecha Objetivo
                        </p>
                        <UiBadge label="Próximamente" tone="outline" />
                    </div>
                </div>
            </section>

            <ProjectTabs
                v-model="activeTab"
                :task-count="project.tasks.length"
            />

            <div
                v-if="activeTab === 'general'"
                role="tabpanel"
                aria-label="General y resumen"
                class="gap-space-lg grid grid-cols-1 lg:grid-cols-2"
            >
                <section
                    aria-label="Sprint actual"
                    class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl"
                >
                    <div class="gap-space-sm flex items-center justify-between">
                        <h2
                            class="text-headline-sm font-headline-sm text-on-surface"
                        >
                            Sprint Actual
                        </h2>
                        <UiBadge
                            v-if="project.activeSprint"
                            label="En curso"
                            tone="primary"
                            dot
                        />
                    </div>

                    <template v-if="project.activeSprint">
                        <div>
                            <p
                                class="text-body-md font-body-md text-on-surface font-medium"
                            >
                                {{ project.activeSprint.name }}
                            </p>
                            <p
                                class="text-body-sm font-body-sm text-on-surface-variant mt-0.5 flex items-center gap-1"
                            >
                                <AppIcon name="calendar_today" :size="14" />
                                <span>
                                    {{ project.activeSprint.startDate }} —
                                    {{ project.activeSprint.endDate }}
                                </span>
                            </p>
                        </div>

                        <div class="mt-auto">
                            <div
                                class="gap-space-sm flex items-baseline justify-between"
                            >
                                <span
                                    class="text-label-xs font-label-xs text-on-surface-variant"
                                >
                                    {{ activeSprintTasks.length }} tareas en
                                    este sprint
                                </span>
                                <span
                                    class="text-label-md font-label-md text-primary font-semibold"
                                >
                                    {{ activeSprintProgress }}%
                                </span>
                            </div>
                            <div
                                class="bg-surface-container-high mt-1 h-2 w-full rounded-full"
                            >
                                <div
                                    class="bg-primary h-full rounded-full"
                                    :style="{
                                        width: `${activeSprintProgress}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </template>

                    <div v-else class="py-space-md flex flex-col items-center">
                        <p
                            class="text-body-sm font-body-sm text-on-surface-variant"
                        >
                            Sin sprint activo.
                        </p>
                        <button
                            type="button"
                            class="text-label-sm font-label-sm text-primary hover:bg-surface-container mt-2 rounded-lg px-3 py-1.5 transition-colors"
                            @click="goToSprintsTab"
                        >
                            Planificar un sprint
                        </button>
                    </div>
                </section>

                <section
                    v-if="nextSprint"
                    aria-label="Próximo sprint"
                    class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl"
                >
                    <div class="gap-space-sm flex items-center justify-between">
                        <h2
                            class="text-headline-sm font-headline-sm text-on-surface"
                        >
                            Próximo Sprint
                        </h2>
                        <UiBadge label="Planificado" tone="neutral" />
                    </div>

                    <div>
                        <p
                            class="text-body-md font-body-md text-on-surface font-medium"
                        >
                            {{ nextSprint.name }}
                        </p>
                        <p
                            class="text-body-sm font-body-sm text-on-surface-variant mt-0.5 flex items-center gap-1"
                        >
                            <AppIcon name="calendar_today" :size="14" />
                            <span>
                                {{ nextSprint.startDate }} —
                                {{ nextSprint.endDate }}
                            </span>
                        </p>
                    </div>

                    <p
                        class="text-label-xs font-label-xs text-on-surface-variant mt-auto"
                    >
                        {{ nextSprintTasks.length }} tareas planeadas
                    </p>
                </section>

                <section
                    aria-label="Backlog"
                    class="bg-surface-container-low gap-space-md p-space-md flex items-center justify-between rounded-xl lg:col-span-2"
                >
                    <p class="text-body-sm font-body-sm text-on-surface">
                        <strong class="font-semibold">{{
                            backlogTaskCount
                        }}</strong>
                        tareas pendientes en el Backlog
                    </p>
                    <button
                        type="button"
                        class="text-label-sm font-label-sm text-primary hover:bg-surface-container-lowest flex items-center gap-1 rounded-lg px-3 py-1.5 transition-colors"
                        @click="goToSprintsTab"
                    >
                        Ver Backlog
                        <AppIcon name="arrow_forward" :size="14" />
                    </button>
                </section>

                <section
                    aria-label="Equipo del proyecto"
                    class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl lg:col-span-2"
                >
                    <h2
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        Equipo
                    </h2>

                    <ul class="gap-space-sm flex flex-col">
                        <li class="gap-space-sm flex items-center">
                            <Avatar
                                :name="project.owner.name"
                                :src="project.owner.avatar"
                                :size="28"
                            />
                            <span
                                class="text-body-sm font-body-sm text-on-surface"
                            >
                                {{ project.owner.name }}
                            </span>
                            <UiBadge label="Propietario" tone="neutral" />
                        </li>
                        <li
                            v-for="member in project.members"
                            :key="member.id"
                            class="gap-space-sm flex items-center justify-between"
                        >
                            <span class="gap-space-sm flex items-center">
                                <Avatar
                                    :name="member.name"
                                    :src="member.avatar"
                                    :size="28"
                                />
                                <span
                                    class="text-body-sm font-body-sm text-on-surface"
                                >
                                    {{ member.name }}
                                </span>
                            </span>
                            <button
                                v-if="project.isOwner"
                                type="button"
                                class="text-label-xs font-label-xs text-error hover:underline"
                                @click="removeMember(project.id, member.id)"
                            >
                                Retirar
                            </button>
                        </li>
                        <li
                            v-if="project.members.length === 0"
                            class="text-body-sm font-body-sm text-on-surface-variant"
                        >
                            Sin colaboradores todavía.
                        </li>
                    </ul>

                    <form
                        v-if="project.isOwner"
                        class="gap-space-sm mt-2 flex max-w-md items-start"
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
                </section>
            </div>

            <div
                v-else
                role="tabpanel"
                aria-label="Sprints y tareas"
                class="gap-space-lg flex flex-col"
            >
                <section
                    aria-label="Sprints"
                    class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl"
                >
                    <h2
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        Sprints
                    </h2>

                    <ul class="gap-space-sm flex flex-col">
                        <li
                            v-for="sprint in project.sprints"
                            :key="sprint.id"
                            class="gap-space-sm flex items-center justify-between"
                        >
                            <span
                                class="text-body-sm font-body-sm text-on-surface"
                            >
                                {{ sprint.name }}
                                <span class="text-on-surface-variant">
                                    ({{ sprint.startDate }} —
                                    {{ sprint.endDate }})
                                </span>
                            </span>
                            <span v-if="canWriteSprints" class="flex gap-3">
                                <button
                                    type="button"
                                    class="text-label-xs font-label-xs text-on-surface-variant hover:text-on-surface hover:underline"
                                    @click="openSprintEdit(sprint)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="text-label-xs font-label-xs text-error hover:underline"
                                    @click="
                                        confirmSprintDestroy(project.id, sprint)
                                    "
                                >
                                    Eliminar
                                </button>
                            </span>
                        </li>
                        <li
                            v-if="project.sprints.length === 0"
                            class="text-body-sm font-body-sm text-on-surface-variant"
                        >
                            Sin sprints todavía.
                        </li>
                    </ul>

                    <form
                        v-if="canWriteSprints"
                        class="gap-space-sm mt-2 flex max-w-xl flex-wrap items-end"
                        @submit.prevent="submitSprint(project.id)"
                    >
                        <div class="min-w-40 flex-1">
                            <InputLabel for="sprint-name" value="Nombre" />
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
                            <InputLabel for="sprint-start" value="Inicio" />
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
                </section>

                <section
                    aria-label="Tareas"
                    class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl"
                >
                    <div
                        class="gap-space-sm flex flex-wrap items-center justify-between"
                    >
                        <h2
                            class="text-headline-sm font-headline-sm text-on-surface"
                        >
                            Tareas
                        </h2>
                        <div
                            class="bg-surface-container-low flex items-center rounded-lg p-0.5"
                        >
                            <button
                                type="button"
                                class="text-label-sm font-label-sm flex items-center gap-1 rounded-md px-2.5 py-1 transition-colors"
                                :class="
                                    taskView === 'lista'
                                        ? 'bg-surface-container-lowest text-primary shadow-card'
                                        : 'text-on-surface-variant hover:text-on-surface'
                                "
                                @click="taskView = 'lista'"
                            >
                                Lista
                            </button>
                            <button
                                type="button"
                                class="text-label-sm font-label-sm flex items-center gap-1 rounded-md px-2.5 py-1 transition-colors"
                                :class="
                                    taskView === 'backlog'
                                        ? 'bg-surface-container-lowest text-primary shadow-card'
                                        : 'text-on-surface-variant hover:text-on-surface'
                                "
                                @click="taskView = 'backlog'"
                            >
                                Backlog
                            </button>
                            <button
                                type="button"
                                class="text-label-sm font-label-sm flex items-center gap-1 rounded-md px-2.5 py-1 transition-colors"
                                :class="
                                    taskView === 'kanban'
                                        ? 'bg-surface-container-lowest text-primary shadow-card'
                                        : 'text-on-surface-variant hover:text-on-surface'
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
                            :deletable="project.isOwner && canWriteTasks"
                            @edit="openTaskEdit"
                            @comment="openTaskComments"
                            @remove="confirmTaskDestroy"
                        />

                        <form
                            v-if="canWriteTasks"
                            class="mt-2 max-w-2xl space-y-3"
                            @submit.prevent="submitTask(project.id)"
                        >
                            <div>
                                <InputLabel for="task-title" value="Título" />
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
                                    :message="taskForm.errors.description"
                                />
                            </div>
                            <div class="gap-space-sm flex flex-wrap items-end">
                                <div>
                                    <InputLabel for="task-type" value="Tipo" />
                                    <select
                                        id="task-type"
                                        v-model="taskForm.type"
                                        class="mt-1 block rounded-md border-gray-300 shadow-sm"
                                    >
                                        <option value="other">Otro</option>
                                        <option value="feature">Feature</option>
                                        <option value="bug">Bug</option>
                                        <option value="test">Test</option>
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
                                        <option value="medium">Media</option>
                                        <option value="low">Baja</option>
                                        <option value="high">Alta</option>
                                        <option value="urgent">Urgente</option>
                                    </select>
                                    <InputError
                                        class="mt-2"
                                        :message="taskForm.errors.priority"
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
                                        <option :value="project.owner.id">
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
                                        :message="taskForm.errors.assignee_id"
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
                                        <option value="">Sin sprint</option>
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
                                        :message="taskForm.errors.sprint_id"
                                    />
                                </div>
                                <PrimaryButton
                                    :class="{
                                        'opacity-25': taskForm.processing,
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
                </section>
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
                            <InputLabel for="edit-sprint-name" value="Nombre" />
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
                            <button
                                type="button"
                                class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg px-4 py-1.5 transition-colors"
                                @click="closeSprintEdit"
                            >
                                Cancelar
                            </button>
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

            <ProjectFormModal
                :open="editModalOpen"
                mode="edit"
                :project="{
                    id: project.id,
                    title: project.title,
                    description: project.description,
                }"
                @close="editModalOpen = false"
            />
        </div>
    </AuthenticatedLayout>
</template>
