<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SprintComingSoon from '@/Components/Sprints/SprintComingSoon.vue';
import SprintFormModal from '@/Components/Sprints/SprintFormModal.vue';
import SprintHeader from '@/Components/Sprints/SprintHeader.vue';
import SprintMetricsPanel from '@/Components/Sprints/SprintMetricsPanel.vue';
import SprintTaskList from '@/Components/Sprints/SprintTaskList.vue';
import TaskCommentsModal from '@/Components/Tasks/TaskCommentsModal.vue';
import TaskFormModal from '@/Components/Tasks/Board/TaskFormModal.vue';
import type {
    SprintDetail,
    SprintMetrics,
    SprintTaskContext,
} from '@/types/sprint';
import type {
    BoardTask,
    TaskProjectOption,
    TaskStatusValue,
} from '@/types/task';
import { complete, start } from '@/routes/projects/sprints';
import { show as taskShow, update } from '@/routes/tasks';
import { store as storeTaskComment } from '@/routes/tasks/comments';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    sprint: SprintDetail;
    metrics: SprintMetrics;
    tasks: BoardTask[];
    taskContext: SprintTaskContext;
    canWrite: boolean;
}>();

const page = usePage();

const currentUser = computed(() => {
    const user = page.props.auth.user;

    return { id: user.id, name: user.name, avatar: user.avatar ?? null };
});

const taskProjects = computed<TaskProjectOption[]>(() => [
    {
        id: props.taskContext.project.id,
        title: props.taskContext.project.title,
        members: props.taskContext.members,
        sprints: props.taskContext.sprints,
    },
]);

const sprintFormOpen = ref(false);
const taskModalOpen = ref(false);
const taskModalMode = ref<'create' | 'edit'>('create');
const editingTask = ref<BoardTask | null>(null);
const commentingTask = ref<BoardTask | null>(null);

const taskList = ref<InstanceType<typeof SprintTaskList> | null>(null);

function openSprintEdit(): void {
    sprintFormOpen.value = true;
}

function openTaskCreate(): void {
    taskModalMode.value = 'create';
    editingTask.value = null;
    taskModalOpen.value = true;
}

function openTaskEdit(task: BoardTask): void {
    taskModalMode.value = 'edit';
    editingTask.value = task;
    taskModalOpen.value = true;
}

function changeTaskStatus(task: BoardTask, status: TaskStatusValue): void {
    router.put(update.url(task.id), { status }, { preserveScroll: true });
}

function removeTask(task: BoardTask): void {
    if (
        window.confirm(
            `¿Eliminar la tarea "${task.title}"? Esta acción no se puede deshacer.`,
        )
    ) {
        router.delete(update.url(task.id), { preserveScroll: true });
    }
}

function openComments(task: BoardTask): void {
    commentingTask.value = task;
}

function onStart(): void {
    router.post(
        start.url([props.sprint.project.id, props.sprint.id]),
        {},
        {
            preserveScroll: true,
        },
    );
}

function onComplete(): void {
    if (
        window.confirm(
            `¿Completar el sprint "${props.sprint.name}"? Quedará en solo lectura.`,
        )
    ) {
        router.post(
            complete.url([props.sprint.project.id, props.sprint.id]),
            {},
            { preserveScroll: true },
        );
    }
}

function onCaptureKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'f') {
        event.preventDefault();
        event.stopPropagation();
        taskList.value?.focus();
    }
}

onMounted(() => document.addEventListener('keydown', onCaptureKeydown, true));

onBeforeUnmount(() =>
    document.removeEventListener('keydown', onCaptureKeydown, true),
);
</script>

<template>
    <Head :title="sprint.name" />

    <AuthenticatedLayout>
        <div
            class="gap-space-lg py-space-md mx-auto flex w-full max-w-7xl flex-col"
        >
            <SprintHeader
                :sprint="sprint"
                @edit="openSprintEdit"
                @add-task="openTaskCreate"
                @start="onStart"
                @complete="onComplete"
            />

            <div class="gap-space-lg flex flex-col xl:grid xl:grid-cols-3">
                <div class="gap-space-lg flex flex-col xl:col-span-2">
                    <SprintMetricsPanel :metrics="metrics" />
                    <SprintTaskList
                        ref="taskList"
                        :tasks="tasks"
                        :can-write="canWrite"
                        @open="openTaskEdit"
                        @edit="openTaskEdit"
                        @status="changeTaskStatus"
                        @comment="openComments"
                        @remove="removeTask"
                    />
                </div>

                <aside class="flex flex-col">
                    <SprintComingSoon />
                </aside>
            </div>
        </div>

        <SprintFormModal
            :open="sprintFormOpen"
            :project="sprint.project"
            :sprint="
                sprint.status !== 'completed'
                    ? {
                          id: sprint.id,
                          name: sprint.name,
                          startDate: sprint.startDate,
                          endDate: sprint.endDate,
                          status: sprint.status,
                          statusLabel: sprint.statusLabel,
                          goal: sprint.goal,
                      }
                    : undefined
            "
            @close="sprintFormOpen = false"
        />

        <TaskFormModal
            :open="taskModalOpen"
            :mode="taskModalMode"
            :task="editingTask ?? undefined"
            :projects="taskProjects"
            :current-user="currentUser"
            :initial-project="taskContext.project"
            :initial-sprint="{ id: sprint.id, name: sprint.name }"
            @close="taskModalOpen = false"
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
