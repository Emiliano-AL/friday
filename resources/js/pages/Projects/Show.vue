<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import AvatarStack from '@/Components/Projects/AvatarStack.vue';
import MembersModal from '@/Components/Projects/MembersModal.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';
import ProjectAttachments from '@/Components/Projects/ProjectAttachments.vue';
import ProjectTabs from '@/Components/Projects/ProjectTabs.vue';
import type { ProjectTabValue } from '@/Components/Projects/ProjectTabs.vue';
import RichTextContent from '@/Components/RichText/RichTextContent.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import { index as projectsIndex, status } from '@/routes/projects';
import {
    complete as completeSprint,
    destroy as destroySprint,
} from '@/routes/projects/sprints';
import { destroy as destroyTask } from '@/routes/projects/tasks';
import { show as taskShow, update as updateTaskGlobal } from '@/routes/tasks';
import { store as storeGlobalTaskComment } from '@/routes/tasks/comments';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BacklogSectionList from '@/Components/Backlog/BacklogSectionList.vue';
import ProjectSprintsSection from '@/Components/Sprints/ProjectSprintsSection.vue';
import SprintFormModal from '@/Components/Sprints/SprintFormModal.vue';
import TaskBoard from '@/Components/Tasks/Board/TaskBoard.vue';
import TaskCommentsModal from '@/Components/Tasks/TaskCommentsModal.vue';
import TaskFormModal from '@/Components/Tasks/Board/TaskFormModal.vue';
import TaskImportModal from '@/Components/Tasks/TaskImportModal.vue';
import TaskListPanel from '@/Components/Tasks/Board/TaskListPanel.vue';
import type {
    BoardTask,
    TaskProjectOption,
    TaskStatusValue,
} from '@/types/task';
import type { BacklogSort, BacklogTask } from '@/types/backlog';
import type {
    ProjectAttachments as ProjectAttachmentsPayload,
    ProjectDetail,
    SprintSummary,
} from '@/types/project';

const props = defineProps<{
    project: ProjectDetail;
    attachments?: ProjectAttachmentsPayload;
}>();

const page = usePage();

const canWriteSprints = computed(
    () => props.project.isOwner && props.project.status === 'active',
);

const canWriteTasks = computed(() => props.project.status === 'active');

const importOpen = ref(false);

const activeTab = ref<ProjectTabValue>('general');

watch(activeTab, (tab) => {
    if (tab === 'files' && props.attachments === undefined) {
        router.reload({ only: ['attachments'] });
    }
});

const editModalOpen = ref(false);

const membersModalOpen = ref(false);

const taskView = ref<'lista' | 'backlog' | 'kanban'>('lista');

const sprintFilter = ref<'none' | 'all' | number>('none');

const backlogSort = ref<BacklogSort>('priority');

const taskModalOpen = ref(false);
const taskModalMode = ref<'create' | 'edit'>('create');
const editingBoardTask = ref<BoardTask | BacklogTask | null>(null);
const taskInitialStatus = ref<Exclude<TaskStatusValue, 'done'> | undefined>(
    undefined,
);

const currentUser = computed(() => {
    const user = page.props.auth.user;

    return { id: user.id, name: user.name, avatar: user.avatar ?? null };
});

const projectOption = computed<TaskProjectOption>(() => ({
    id: props.project.id,
    title: props.project.title,
    members: [
        {
            id: props.project.owner.id,
            name: props.project.owner.name,
            avatar: props.project.owner.avatar,
        },
        ...props.project.members.map((member) => ({
            id: member.id,
            name: member.name,
            avatar: member.avatar,
        })),
    ],
    sprints: props.project.sprints.map((sprint) => ({
        id: sprint.id,
        name: sprint.name,
    })),
}));

const projectOptions = computed(() => [projectOption.value]);

const memberOptions = computed(() => [
    props.project.owner,
    ...props.project.members,
]);

const openTaskCreate = (status?: Exclude<TaskStatusValue, 'done'>) => {
    taskModalMode.value = 'create';
    editingBoardTask.value = null;
    taskInitialStatus.value = status;
    taskModalOpen.value = true;
};

const openTaskCreateFromColumn = (status: TaskStatusValue) => {
    if (
        status === 'todo' ||
        status === 'in_progress' ||
        status === 'in_review'
    ) {
        openTaskCreate(status);
    }
};

const closeTaskModal = () => {
    taskModalOpen.value = false;
};

const openTaskEdit = (task: BoardTask | BacklogTask) => {
    taskModalMode.value = 'edit';
    editingBoardTask.value = task;
    taskInitialStatus.value = undefined;
    taskModalOpen.value = true;
};

const commentingTask = ref<BoardTask | null>(null);

const openTaskComments = (task: BoardTask) => {
    commentingTask.value = task;
};

const closeTaskComments = () => {
    commentingTask.value = null;
};

const reloadProjectTasks = () => {
    router.reload();
};

const changeTaskStatus = (task: BoardTask, status: TaskStatusValue) => {
    router.put(
        updateTaskGlobal.url(task.id),
        { status },
        { preserveScroll: true },
    );
};

const sprintModalOpen = ref(false);
const sprintModalSprint = ref<SprintSummary | null>(null);

const openSprintCreate = () => {
    sprintModalSprint.value = null;
    sprintModalOpen.value = true;
};

const openSprintEdit = (sprint: SprintSummary) => {
    sprintModalSprint.value = sprint;
    sprintModalOpen.value = true;
};

const transitionLabels: Record<string, string> = {
    active: 'Reactivar',
    archived: 'Archivar',
    completed: 'Completar',
};

const transitionIcons: Record<string, string> = {
    active: 'undo',
    archived: 'archive',
    completed: 'task_alt',
};

const canEditProject = computed(
    () => props.project.isOwner && props.project.status === 'active',
);

const hasProjectMenu = computed(
    () =>
        canEditProject.value ||
        (props.project.isOwner && props.project.allowedTransitions.length > 0),
);

const projectMenuOpen = ref(false);
const projectMenuRoot = ref<HTMLElement | null>(null);

function onProjectMenuDocumentClick(event: MouseEvent): void {
    if (!projectMenuRoot.value?.contains(event.target as Node)) {
        projectMenuOpen.value = false;
    }
}

function onProjectMenuKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        projectMenuOpen.value = false;
    }
}

watch(projectMenuOpen, (isOpen) => {
    if (isOpen) {
        document.addEventListener('click', onProjectMenuDocumentClick, true);
        document.addEventListener('keydown', onProjectMenuKeydown);
    } else {
        document.removeEventListener('click', onProjectMenuDocumentClick, true);
        document.removeEventListener('keydown', onProjectMenuKeydown);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onProjectMenuDocumentClick, true);
    document.removeEventListener('keydown', onProjectMenuKeydown);
});

function transitionProject(target: string): void {
    projectMenuOpen.value = false;
    router.put(status.url(props.project.id), { status: target });
}

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

const completeActiveSprint = () => {
    const sprint = props.project.activeSprint;

    if (!sprint) {
        return;
    }

    if (
        window.confirm(
            `¿Completar el sprint "${sprint.name}"? Esta acción no se puede deshacer.`,
        )
    ) {
        router.post(completeSprint.url([props.project.id, sprint.id]), {
            preserveScroll: true,
        });
    }
};

const filteredTasks = computed(() => {
    if (sprintFilter.value === 'all') {
        return props.project.tasks;
    }

    if (sprintFilter.value === 'none') {
        return props.project.tasks.filter((task) => task.sprint === null);
    }

    return props.project.tasks.filter(
        (task) => task.sprint?.id === sprintFilter.value,
    );
});

const backlogTasks = computed(() =>
    filteredTasks.value.filter((task) => task.status !== 'done'),
);

const boardTaskStatuses: TaskStatusValue[] = [
    'todo',
    'in_progress',
    'in_review',
    'done',
];

const boardTasks = computed(() =>
    filteredTasks.value.filter((task) =>
        boardTaskStatuses.includes(task.status),
    ),
);

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

const targetDateValue = computed(() =>
    props.project.targetDate
        ? new Date(`${props.project.targetDate}T00:00:00`)
        : null,
);

const formattedTargetDate = computed(() =>
    targetDateValue.value
        ? targetDateValue.value.toLocaleDateString('es-MX', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : null,
);

const isTargetDateOverdue = computed(() => {
    if (!targetDateValue.value) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return targetDateValue.value < today;
});

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
            class="project-accent-scope gap-space-lg py-space-md mx-auto flex w-full max-w-7xl flex-col"
            :style="
                project.color
                    ? { '--project-accent': project.color }
                    : undefined
            "
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

            <div class="gap-space-md flex flex-col">
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
                    <span class="text-on-surface min-w-0 truncate">
                        {{ project.title }}
                    </span>
                </nav>

                <header class="gap-space-md flex flex-wrap items-start">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                        :class="
                            project.color ? '' : 'bg-primary text-on-primary'
                        "
                        :style="
                            project.color
                                ? {
                                      backgroundColor: `${project.color}1A`,
                                      color: project.color,
                                  }
                                : undefined
                        "
                    >
                        <AppIcon :name="project.icon ?? 'folder'" :size="26" />
                    </div>
                    <div class="min-w-0 flex-1 basis-80">
                        <div class="gap-space-sm flex flex-wrap items-center">
                            <h1
                                class="text-headline-xl font-headline-xl text-on-surface"
                            >
                                {{ project.title }}
                            </h1>
                            <span
                                v-if="project.key"
                                class="bg-surface-container-low text-on-surface-variant text-label-sm font-label-sm rounded px-1.5 py-0.5 font-medium tracking-wide"
                            >
                                {{ project.key }}
                            </span>
                            <UiBadge
                                :label="project.statusLabel"
                                :tone="statusTone"
                                dot
                            />
                        </div>
                        <RichTextContent
                            v-if="project.description"
                            :content="project.description"
                            class="text-on-surface-variant mt-1 max-w-3xl"
                        />
                    </div>

                    <div class="gap-space-sm flex items-center">
                        <button
                            v-if="canWriteSprints"
                            type="button"
                            class="bg-surface-container-lowest text-on-surface hover:bg-surface-container-high shadow-card text-label-md font-label-md gap-space-xs flex items-center rounded-lg px-3 py-2 transition-colors"
                            @click="
                                goToSprintsTab();
                                openSprintCreate();
                            "
                        >
                            <AppIcon name="pace" :size="16" />
                            <span>Nuevo Sprint</span>
                        </button>
                        <button
                            v-if="canWriteTasks"
                            type="button"
                            class="bg-primary hover:bg-primary-container text-on-primary shadow-card text-label-md font-label-md gap-space-xs flex items-center rounded-lg px-3.5 py-2 transition-colors"
                            @click="openTaskCreate()"
                        >
                            <AppIcon name="add" :size="16" />
                            <span>Nueva Tarea</span>
                        </button>
                        <div
                            v-if="hasProjectMenu"
                            ref="projectMenuRoot"
                            class="relative"
                        >
                            <button
                                type="button"
                                aria-label="Más acciones del proyecto"
                                aria-haspopup="menu"
                                :aria-expanded="projectMenuOpen"
                                class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-9 w-9 items-center justify-center rounded-lg transition-colors"
                                @click="projectMenuOpen = !projectMenuOpen"
                            >
                                <AppIcon name="more_horiz" :size="20" />
                            </button>
                            <div
                                v-if="projectMenuOpen"
                                role="menu"
                                class="bg-surface-container-lowest shadow-popover absolute top-10 right-0 z-30 w-48 rounded-lg py-1"
                            >
                                <button
                                    v-if="canEditProject"
                                    type="button"
                                    role="menuitem"
                                    class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                                    @click="
                                        projectMenuOpen = false;
                                        editModalOpen = true;
                                    "
                                >
                                    <AppIcon name="edit" :size="16" />
                                    Editar proyecto
                                </button>
                                <template v-if="project.isOwner">
                                    <div
                                        v-if="
                                            canEditProject &&
                                            project.allowedTransitions.length >
                                                0
                                        "
                                        class="border-surface-container my-1 border-t"
                                    />
                                    <button
                                        v-for="target in project.allowedTransitions"
                                        :key="target"
                                        type="button"
                                        role="menuitem"
                                        class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                                        @click="transitionProject(target)"
                                    >
                                        <AppIcon
                                            :name="
                                                transitionIcons[target] ??
                                                'swap_horiz'
                                            "
                                            :size="16"
                                        />
                                        {{ transitionLabels[target] ?? target }}
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </header>
            </div>

            <section
                aria-label="Resumen del proyecto"
                class="bg-surface-container-lowest shadow-card gap-space-md p-space-md grid grid-cols-2 rounded-xl border-t-[3px] border-t-(--project-accent) md:grid-cols-4"
            >
                <div class="gap-space-sm flex items-center">
                    <Avatar
                        :name="project.owner.name"
                        :src="project.owner.avatar"
                        :size="36"
                    />
                    <div class="min-w-0">
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant"
                        >
                            Líder del proyecto
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
                            class="text-label-xs font-label-xs text-on-surface-variant"
                        >
                            Equipo
                        </p>
                        <p
                            class="text-label-md font-label-md text-on-surface font-semibold"
                        >
                            {{ 1 + project.members.length }}
                            {{
                                project.members.length === 0
                                    ? 'persona'
                                    : 'personas'
                            }}
                        </p>
                    </div>
                </div>

                <div class="gap-space-xs flex flex-col justify-center">
                    <div
                        class="gap-space-sm flex items-baseline justify-between"
                    >
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant"
                        >
                            Progreso
                        </p>
                        <p
                            class="text-label-md font-label-md font-semibold text-(--project-accent-ink)"
                        >
                            {{ project.progress }}%
                        </p>
                    </div>
                    <div
                        class="bg-surface-container-high h-2 w-full rounded-full"
                    >
                        <div
                            class="h-full rounded-full bg-(--project-accent)"
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
                            class="text-label-xs font-label-xs text-on-surface-variant"
                        >
                            Fecha objetivo
                        </p>
                        <div
                            v-if="formattedTargetDate"
                            class="gap-space-2xs flex items-center"
                        >
                            <p
                                class="text-label-md font-label-md text-on-surface font-semibold"
                            >
                                {{ formattedTargetDate }}
                            </p>
                            <UiBadge
                                v-if="isTargetDateOverdue"
                                label="Vencida"
                                tone="error"
                            />
                        </div>
                        <p
                            v-else
                            class="text-label-md font-label-md text-on-surface-variant"
                        >
                            Sin fecha
                        </p>
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
                                    class="text-label-md font-label-md font-semibold text-(--project-accent-ink)"
                                >
                                    {{ activeSprintProgress }}%
                                </span>
                            </div>
                            <div
                                class="bg-surface-container-high mt-1 h-2 w-full rounded-full"
                            >
                                <div
                                    class="h-full rounded-full bg-(--project-accent)"
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
                    <div class="gap-space-sm flex items-center justify-between">
                        <h2
                            class="text-headline-sm font-headline-sm text-on-surface"
                        >
                            Equipo
                        </h2>
                        <button
                            v-if="project.isOwner"
                            type="button"
                            class="bg-surface-container-lowest text-on-surface hover:bg-surface-container-high shadow-card text-label-sm font-label-sm gap-space-xs flex items-center rounded-lg px-3 py-1.5 transition-colors"
                            @click="membersModalOpen = true"
                        >
                            <AppIcon name="group" :size="16" />
                            <span>Gestionar colaboradores</span>
                        </button>
                    </div>

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
                        </li>
                        <li
                            v-if="project.members.length === 0"
                            class="text-body-sm font-body-sm text-on-surface-variant"
                        >
                            Sin colaboradores todavía.
                        </li>
                    </ul>
                </section>
            </div>

            <div
                v-else-if="activeTab === 'files'"
                role="tabpanel"
                aria-label="Documentos"
            >
                <ProjectAttachments
                    :project-id="project.id"
                    :attachments="attachments"
                />
            </div>

            <div
                v-else
                role="tabpanel"
                aria-label="Sprints y tareas"
                class="gap-space-lg flex flex-col"
            >
                <ProjectSprintsSection
                    :project="project"
                    :tasks="project.tasks"
                    :can-write-sprints="canWriteSprints"
                    @create="openSprintCreate"
                    @edit="openSprintEdit"
                    @destroy="
                        (sprint) => confirmSprintDestroy(project.id, sprint)
                    "
                    @complete="completeActiveSprint"
                />

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
                        <button
                            v-if="canWriteTasks"
                            type="button"
                            class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container hover:text-on-surface ml-auto flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                            @click="importOpen = true"
                        >
                            <AppIcon name="upload_file" :size="16" />
                            Importar
                        </button>
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

                    <div
                        v-if="taskView !== 'lista'"
                        class="gap-space-sm flex flex-wrap items-center justify-between"
                    >
                        <div class="gap-space-xs flex items-center">
                            <label
                                for="tasks-sprint-filter"
                                class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1 font-semibold tracking-wider uppercase"
                            >
                                <AppIcon name="pace" :size="14" />
                                Sprint
                            </label>
                            <select
                                id="tasks-sprint-filter"
                                v-model="sprintFilter"
                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 cursor-pointer appearance-none rounded-lg border px-3 pr-8 shadow-xs focus:ring-2 focus:outline-none"
                            >
                                <option value="none">Sin sprint</option>
                                <option
                                    v-for="sprint in project.sprints"
                                    :key="sprint.id"
                                    :value="sprint.id"
                                >
                                    {{ sprint.name }} · {{ sprint.startDate }} —
                                    {{ sprint.endDate }}
                                </option>
                                <option value="all">Todos</option>
                            </select>
                        </div>

                        <div
                            v-if="taskView === 'backlog'"
                            class="bg-surface-container-low flex items-center rounded-lg p-0.5"
                        >
                            <button
                                type="button"
                                :aria-pressed="backlogSort === 'priority'"
                                class="text-label-xs font-label-xs flex h-7 items-center gap-1 rounded-md px-2.5 transition-colors"
                                :class="
                                    backlogSort === 'priority'
                                        ? 'bg-surface-container-lowest text-primary shadow-card'
                                        : 'text-on-surface-variant hover:text-on-surface'
                                "
                                @click="backlogSort = 'priority'"
                            >
                                <AppIcon name="flag" :size="14" />
                                Prioridad
                            </button>
                            <button
                                type="button"
                                :aria-pressed="backlogSort === 'recent'"
                                class="text-label-xs font-label-xs flex h-7 items-center gap-1 rounded-md px-2.5 transition-colors"
                                :class="
                                    backlogSort === 'recent'
                                        ? 'bg-surface-container-lowest text-primary shadow-card'
                                        : 'text-on-surface-variant hover:text-on-surface'
                                "
                                @click="backlogSort = 'recent'"
                            >
                                <AppIcon name="schedule" :size="14" />
                                Recientes
                            </button>
                        </div>
                    </div>

                    <template v-if="taskView === 'lista'">
                        <TaskListPanel
                            :tasks="project.tasks"
                            :current-user="currentUser"
                            :view="null"
                            :show-project-filter="false"
                            :show-standalone-tab="false"
                            @create="openTaskCreate()"
                            @edit="openTaskEdit"
                            @status="changeTaskStatus"
                            @comment="openTaskComments"
                            @remove="openTaskEdit"
                        />
                    </template>
                    <template v-else-if="taskView === 'backlog'">
                        <BacklogSectionList
                            v-if="backlogTasks.length > 0"
                            :tasks="backlogTasks"
                            :sort="backlogSort"
                            hide-project-chip
                            @select="openTaskEdit"
                        />

                        <div
                            v-else
                            class="border-outline-variant/30 bg-surface-container-low/40 gap-space-sm px-space-md py-space-xl flex flex-col items-center justify-center rounded-xl border border-dashed text-center"
                        >
                            <AppIcon
                                name="playlist_add"
                                :size="28"
                                class="text-on-surface-variant/60"
                            />
                            <p
                                class="text-body-md font-body-md text-on-surface font-medium"
                            >
                                No hay tareas en el backlog de este proyecto
                            </p>
                            <p
                                class="text-body-sm font-body-sm text-on-surface-variant"
                            >
                                Crea una nueva tarea con el botón «Nueva Tarea»
                                para empezar.
                            </p>
                        </div>
                    </template>
                    <TaskBoard
                        v-else-if="taskView === 'kanban'"
                        :tasks="boardTasks"
                        :can-write="canWriteTasks"
                        @moved="changeTaskStatus"
                        @open="openTaskEdit"
                        @edit="openTaskEdit"
                        @comment="openTaskComments"
                        @add="openTaskCreateFromColumn"
                    />
                </section>
            </div>

            <SprintFormModal
                :open="sprintModalOpen"
                :project="{ id: project.id, title: project.title }"
                :sprint="sprintModalSprint ?? undefined"
                @close="sprintModalOpen = false"
            />

            <TaskFormModal
                :open="taskModalOpen"
                :mode="taskModalMode"
                :task="editingBoardTask ?? undefined"
                :projects="projectOptions"
                :current-user="currentUser"
                :initial-project="{ id: project.id, title: project.title }"
                :initial-status="taskInitialStatus"
                :destroy-url="
                    taskModalMode === 'edit' && editingBoardTask
                        ? destroyTask.url([project.id, editingBoardTask.id])
                        : undefined
                "
                @close="closeTaskModal"
            />

            <TaskCommentsModal
                :show="commentingTask !== null"
                :task="
                    commentingTask ? { ...commentingTask, comments: [] } : null
                "
                :can-comment="commentingTask?.canUpdate ?? false"
                use-global
                :endpoints="
                    commentingTask
                        ? {
                              show: taskShow.url(commentingTask.id),
                              store: storeGlobalTaskComment.url(
                                  commentingTask.id,
                              ),
                          }
                        : { show: '', store: '' }
                "
                @close="closeTaskComments"
                @submitted="reloadProjectTasks"
            />

            <ProjectFormModal
                :open="editModalOpen"
                mode="edit"
                :project="{
                    id: project.id,
                    title: project.title,
                    description: project.description,
                    key: project.key,
                    color: project.color,
                    icon: project.icon,
                    targetDate: project.targetDate,
                }"
                @close="editModalOpen = false"
            />

            <MembersModal
                :open="membersModalOpen"
                :project="project"
                @close="membersModalOpen = false"
            />
        </div>
        <TaskImportModal
            :open="importOpen"
            :project-id="project.id"
            :destination-label="project.title"
            @close="importOpen = false"
        />
    </AuthenticatedLayout>
</template>
