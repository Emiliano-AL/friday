<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import BacklogFilters from '@/Components/Backlog/BacklogFilters.vue';
import BacklogSectionList from '@/Components/Backlog/BacklogSectionList.vue';
import NextSprintBanner from '@/Components/Backlog/NextSprintBanner.vue';
import QuickCapture from '@/Components/Backlog/QuickCapture.vue';
import TaskImportModal from '@/Components/Tasks/TaskImportModal.vue';
import TaskFormModal from '@/Components/Tasks/Board/TaskFormModal.vue';
import type {
    BacklogFilter,
    BacklogNextSprint,
    BacklogSort,
    BacklogSummary,
    BacklogTask,
} from '@/types/backlog';
import type { TaskProjectOption } from '@/types/task';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    tasks: BacklogTask[];
    summary: BacklogSummary;
    nextSprint: BacklogNextSprint | null;
    projects: TaskProjectOption[];
}>();

const page = usePage();

const currentUser = computed(() => {
    const user = page.props.auth.user;

    return { id: user.id, name: user.name, avatar: user.avatar ?? null };
});

const filter = ref<BacklogFilter>('all');
const sort = ref<BacklogSort>('priority');

const modalOpen = ref(false);
const importOpen = ref(false);
const editingTask = ref<BacklogTask | null>(null);

function openTask(task: BacklogTask): void {
    if (!task.canUpdate) {
        return;
    }

    editingTask.value = task;
    modalOpen.value = true;
}

const filteredTasks = computed(() => {
    switch (filter.value) {
        case 'without_project':
            return props.tasks.filter((task) => task.project === null);
        case 'with_project':
            return props.tasks.filter((task) => task.project !== null);
        default:
            return props.tasks;
    }
});

const emptyFilterMessage = computed(() => {
    switch (filter.value) {
        case 'without_project':
            return 'No hay pendientes sin proyecto';
        case 'with_project':
            return 'No hay pendientes con proyecto';
        default:
            return 'No hay pendientes que coincidan con el filtro';
    }
});
</script>

<template>
    <Head title="Backlog" />

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
                    Backlog general
                </span>
            </div>

            <header
                class="gap-space-md flex flex-wrap items-end justify-between"
            >
                <div class="gap-space-xs flex min-w-0 flex-col">
                    <h1
                        class="text-headline-xl font-headline-xl text-on-surface"
                    >
                        Backlog general
                    </h1>
                    <p
                        class="text-body-md font-body-md text-on-surface-variant"
                    >
                        {{ summary.total }}
                        {{ summary.total === 1 ? 'pendiente' : 'pendientes' }}
                        <template v-if="summary.totalPoints > 0">
                            · {{ summary.totalPoints }} pts</template
                        >
                        · captúralos al vuelo y triéndolos después.
                    </p>
                </div>
                <div class="gap-space-sm flex flex-wrap items-center">
                    <button
                        type="button"
                        class="bg-surface-container-lowest text-on-surface hover:bg-surface-container-high shadow-card text-label-md font-label-md flex items-center gap-1.5 rounded-lg px-3 py-2 transition-colors"
                        @click="importOpen = true"
                    >
                        <AppIcon name="upload_file" :size="16" />
                        Importar
                    </button>
                    <NextSprintBanner :next-sprint="nextSprint" />
                </div>
            </header>

            <QuickCapture :projects="projects" />

            <div
                class="gap-space-sm flex flex-wrap items-center justify-between"
            >
                <BacklogFilters
                    :filter="filter"
                    :summary="summary"
                    @update:filter="filter = $event"
                />

                <div
                    class="bg-surface-container-low flex items-center rounded-lg p-0.5"
                >
                    <button
                        type="button"
                        :aria-pressed="sort === 'priority'"
                        class="text-label-xs font-label-xs flex h-7 items-center gap-1 rounded-md px-2.5 transition-colors"
                        :class="
                            sort === 'priority'
                                ? 'bg-surface-container-lowest text-primary shadow-card'
                                : 'text-on-surface-variant hover:text-on-surface'
                        "
                        @click="sort = 'priority'"
                    >
                        <AppIcon name="flag" :size="14" />
                        Prioridad
                    </button>
                    <button
                        type="button"
                        :aria-pressed="sort === 'recent'"
                        class="text-label-xs font-label-xs flex h-7 items-center gap-1 rounded-md px-2.5 transition-colors"
                        :class="
                            sort === 'recent'
                                ? 'bg-surface-container-lowest text-primary shadow-card'
                                : 'text-on-surface-variant hover:text-on-surface'
                        "
                        @click="sort = 'recent'"
                    >
                        <AppIcon name="schedule" :size="14" />
                        Recientes
                    </button>
                </div>
            </div>

            <BacklogSectionList
                v-if="filteredTasks.length > 0"
                :tasks="filteredTasks"
                :sort="sort"
                @select="openTask"
            />

            <div
                v-else-if="tasks.length > 0"
                class="border-outline-variant/30 bg-surface-container-low/40 gap-space-sm px-space-md py-space-xl flex flex-col items-center justify-center rounded-xl border border-dashed text-center"
            >
                <AppIcon
                    name="filter_alt_off"
                    :size="28"
                    class="text-on-surface-variant/60"
                />
                <p
                    class="text-body-md font-body-md text-on-surface font-medium"
                >
                    {{ emptyFilterMessage }}
                </p>
                <p class="text-body-sm font-body-sm text-on-surface-variant">
                    Prueba con otro filtro o captura un pendiente nuevo arriba.
                </p>
            </div>

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
                    Captura tu primer pendiente
                </p>
                <p class="text-body-sm font-body-sm text-on-surface-variant">
                    Escribe arriba qué hay que hacer y pulsa Enter; lo ordenamos
                    luego.
                </p>
            </div>
        </div>

        <TaskFormModal
            :open="modalOpen"
            mode="edit"
            :task="editingTask ?? undefined"
            :projects="projects"
            :current-user="currentUser"
            @close="modalOpen = false"
        />
        <TaskImportModal
            :open="importOpen"
            :project-id="null"
            destination-label="Backlog general"
            @close="importOpen = false"
        />
    </AuthenticatedLayout>
</template>
