<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FilterPills from '@/Components/Projects/FilterPills.vue';
import ProjectCard from '@/Components/Projects/ProjectCard.vue';
import ProjectEmptyState from '@/Components/Projects/ProjectEmptyState.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';
import ProjectListRow from '@/Components/Projects/ProjectListRow.vue';
import ProjectsKpiPanel from '@/Components/Projects/ProjectsKpiPanel.vue';
import ProjectsToolbar from '@/Components/Projects/ProjectsToolbar.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type {
    DirectoryProject,
    ProjectCounts,
    ProjectKpis,
} from '@/types/project';
import { show, status as statusRoute } from '@/routes/projects';
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    projects: DirectoryProject[];
    counts: ProjectCounts;
    kpis: ProjectKpis;
}>();

type ProjectsFilter = 'all' | 'active' | 'completed' | 'archived';
type ProjectsSort = 'recent' | 'alpha' | 'progress';
type ProjectsView = 'grid' | 'list';

const filter = ref<ProjectsFilter>('all');
const search = ref('');
const sort = ref<ProjectsSort>('recent');
const view = ref<ProjectsView>(
    (localStorage.getItem('projects.view') as ProjectsView | null) ?? 'grid',
);

const toolbar = ref<InstanceType<typeof ProjectsToolbar>>();

const modalOpen = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const editingProject = ref<DirectoryProject | null>(null);

const visibleProjects = computed(() => {
    const term = search.value.trim().toLowerCase();

    let list = props.projects.filter((project) => {
        if (filter.value !== 'all' && project.status !== filter.value) {
            return false;
        }

        return term === '' || project.title.toLowerCase().includes(term);
    });

    if (sort.value === 'alpha') {
        list = [...list].sort((a, b) => a.title.localeCompare(b.title));
    } else if (sort.value === 'progress') {
        list = [...list].sort((a, b) => b.progress - a.progress);
    }

    return list;
});

function persistView(value: ProjectsView): void {
    view.value = value;
    localStorage.setItem('projects.view', value);
}

function openCreate(): void {
    modalMode.value = 'create';
    editingProject.value = null;
    modalOpen.value = true;
}

function openEdit(project: DirectoryProject): void {
    modalMode.value = 'edit';
    editingProject.value = project;
    modalOpen.value = true;
}

function visitProject(project: DirectoryProject): void {
    router.visit(show.url(project.id));
}

function transitionProject(
    project: DirectoryProject,
    status: 'active' | 'archived' | 'completed',
): void {
    router.put(
        statusRoute.url(project.id),
        { status },
        { preserveScroll: true },
    );
}

function clearFilters(): void {
    search.value = '';
    filter.value = 'all';
}

function isEditableTarget(target: EventTarget | null): boolean {
    return (
        target instanceof HTMLElement &&
        (target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable)
    );
}

function onGlobalKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'f') {
        event.preventDefault();
        toolbar.value?.focus();

        return;
    }

    if (isEditableTarget(event.target) || event.metaKey || event.ctrlKey) {
        return;
    }

    if (event.key.toLowerCase() === 'n') {
        event.preventDefault();
        openCreate();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onGlobalKeydown);

    const params = new URLSearchParams(window.location.search);

    if (params.get('new') === '1') {
        openCreate();
        window.history.replaceState({}, '', '/projects');
    }

    const editId = params.get('edit');
    if (editId !== null) {
        const project = props.projects.find(
            (item) => item.id === Number(editId),
        );

        if (project && project.isOwner) {
            openEdit(project);
        }

        window.history.replaceState({}, '', '/projects');
    }
});

onBeforeUnmount(() => document.removeEventListener('keydown', onGlobalKeydown));
</script>

<template>
    <Head title="Proyectos" />

    <AuthenticatedLayout>
        <div
            class="gap-space-lg py-space-md mx-auto flex w-full max-w-7xl flex-col"
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
                    Proyectos
                </span>
            </div>

            <div
                class="gap-space-lg flex flex-col justify-between md:flex-row md:items-end"
            >
                <div class="gap-space-xs flex flex-col">
                    <div class="gap-space-md flex items-center">
                        <h1
                            class="text-headline-xl font-headline-xl text-on-surface"
                        >
                            Proyectos
                        </h1>
                        <UiBadge
                            :label="`${counts.active} Activos`"
                            tone="primary"
                        />
                    </div>
                    <p
                        class="text-body-md font-body-md text-on-surface-variant"
                    >
                        Gestiona los flujos de entrega, dependencias de sprint y
                        estado operativo de tus iniciativas.
                    </p>
                </div>

                <ProjectsToolbar
                    ref="toolbar"
                    :sort="sort"
                    :view="view"
                    @update:sort="sort = $event"
                    @update:view="persistView"
                    @search="search = $event"
                    @create="openCreate"
                />
            </div>

            <FilterPills
                :counts="counts"
                :model-value="filter"
                @update:model-value="filter = $event"
            />

            <ProjectEmptyState
                v-if="props.projects.length === 0"
                @create="openCreate"
            />

            <div
                v-else-if="visibleProjects.length === 0"
                class="gap-space-md py-space-xl flex flex-col items-center text-center"
            >
                <p class="text-body-md font-body-md text-on-surface-variant">
                    Sin coincidencias para "{{ search }}".
                </p>
                <button
                    type="button"
                    class="text-label-sm font-label-sm text-primary hover:bg-surface-container rounded-lg px-3 py-1.5 transition-colors"
                    @click="clearFilters"
                >
                    Limpiar filtros
                </button>
            </div>

            <div
                v-else-if="view === 'grid'"
                class="gap-space-lg grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3"
            >
                <ProjectCard
                    v-for="project in visibleProjects"
                    :key="project.id"
                    :project="project"
                    @open="visitProject"
                    @view="visitProject"
                    @edit="openEdit"
                    @transition="transitionProject"
                />
                <button
                    type="button"
                    class="hover:border-primary/40 group gap-space-sm border-outline-variant p-space-lg flex min-h-[220px] flex-col items-center justify-center rounded-xl border-2 border-dashed text-center transition-colors"
                    @click="openCreate"
                >
                    <div
                        class="bg-surface-container text-on-surface-variant group-hover:bg-primary group-hover:text-on-primary flex h-12 w-12 items-center justify-center rounded-full transition-colors"
                    >
                        <AppIcon name="add" :size="24" />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span
                            class="text-headline-sm font-headline-sm text-on-surface group-hover:text-primary transition-colors"
                        >
                            Iniciar un nuevo proyecto
                        </span>
                        <span
                            class="text-body-sm font-body-sm text-on-surface-variant"
                        >
                            Pulsa
                            <kbd
                                class="bg-surface-container font-label-xs rounded px-1.5 py-0.5"
                                >N</kbd
                            >
                            para empezar
                        </span>
                    </div>
                </button>
            </div>

            <div v-else class="gap-space-sm flex flex-col">
                <ProjectListRow
                    v-for="project in visibleProjects"
                    :key="project.id"
                    :project="project"
                    @open="visitProject"
                    @view="visitProject"
                    @edit="openEdit"
                    @transition="transitionProject"
                />
            </div>

            <ProjectsKpiPanel v-if="props.projects.length > 0" :kpis="kpis" />
        </div>

        <ProjectFormModal
            :open="modalOpen"
            :mode="modalMode"
            :project="editingProject ?? undefined"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
