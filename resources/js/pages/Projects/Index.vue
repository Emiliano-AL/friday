<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FilterPills from '@/Components/Projects/FilterPills.vue';
import ProjectCard from '@/Components/Projects/ProjectCard.vue';
import ProjectEmptyState from '@/Components/Projects/ProjectEmptyState.vue';
import ProjectFormModal from '@/Components/Projects/ProjectFormModal.vue';
import ProjectListRow from '@/Components/Projects/ProjectListRow.vue';
import ProjectsToolbar from '@/Components/Projects/ProjectsToolbar.vue';
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

    if (document.querySelector('dialog[open]') !== null) {
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

            <header
                class="gap-space-md flex flex-wrap items-end justify-between"
            >
                <div class="gap-space-xs flex min-w-0 flex-col">
                    <h1
                        class="text-headline-xl font-headline-xl text-on-surface"
                    >
                        Proyectos
                    </h1>
                    <p
                        class="text-body-md font-body-md text-on-surface-variant"
                    >
                        {{ kpis.activeProjects }}
                        {{
                            kpis.activeProjects === 1
                                ? 'proyecto en marcha'
                                : 'proyectos en marcha'
                        }}
                        ·
                        {{ kpis.completedSprintsThisQuarter }}
                        {{
                            kpis.completedSprintsThisQuarter === 1
                                ? 'sprint completado'
                                : 'sprints completados'
                        }}
                        este trimestre
                    </p>
                </div>
                <button
                    type="button"
                    class="bg-primary text-on-primary text-label-md font-label-md shadow-card hover:bg-primary-container gap-space-xs flex items-center rounded-lg px-3.5 py-2 transition-colors"
                    @click="openCreate"
                >
                    <AppIcon name="add" :size="16" />
                    <span>Nuevo Proyecto</span>
                    <kbd
                        class="font-label-xs ml-1 rounded bg-white/20 px-1.5 py-0.5 text-[10px] text-white"
                        >N</kbd
                    >
                </button>
            </header>

            <div
                class="gap-space-md flex flex-wrap items-center justify-between"
            >
                <FilterPills
                    :counts="counts"
                    :model-value="filter"
                    @update:model-value="filter = $event"
                />
                <ProjectsToolbar
                    ref="toolbar"
                    :sort="sort"
                    :view="view"
                    @update:sort="sort = $event"
                    @update:view="persistView"
                    @search="search = $event"
                />
            </div>

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
                    class="hover:border-primary/40 hover:text-primary group gap-space-xs border-outline-variant p-space-lg text-on-surface-variant flex min-h-24 flex-col items-center justify-center rounded-xl border border-dashed text-center transition-colors"
                    @click="openCreate"
                >
                    <AppIcon name="add" :size="20" />
                    <span class="text-label-md font-label-md">
                        Nuevo proyecto
                    </span>
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
        </div>

        <ProjectFormModal
            :open="modalOpen"
            :mode="modalMode"
            :project="editingProject ?? undefined"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
