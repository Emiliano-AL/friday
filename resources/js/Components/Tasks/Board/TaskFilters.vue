<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { TasksTab, TasksView, TaskOption } from '@/types/task';

const props = defineProps<{
    search: string;
    tab: TasksTab;
    projectId: number | 'all' | 'none';
    assigneeId: number | 'all';
    counts: {
        all: number;
        mine: number;
        standalone: number;
        urgent: number;
        done: number;
    };
    view: TasksView;
    projects: TaskOption[];
    assignees: { id: number; name: string; avatar: string | null }[];
}>();

const emit = defineEmits<{
    'update:search': [value: string];
    'update:tab': [value: TasksTab];
    'update:projectId': [value: number | 'all' | 'none'];
    'update:assigneeId': [value: number | 'all'];
    'update:view': [value: TasksView];
    create: [];
}>();

const tabs: {
    value: TasksTab;
    label: string;
    icon?: string;
    dot?: string;
}[] = [
    { value: 'all', label: 'Todas' },
    { value: 'mine', label: 'Mis Tareas' },
    { value: 'standalone', label: 'Sin Proyecto', icon: 'inbox' },
    { value: 'urgent', label: 'Urgentes', dot: 'bg-error' },
    { value: 'done', label: 'Completadas', icon: 'check' },
];

const tabCount = computed(() => {
    switch (props.tab) {
        case 'mine':
            return props.counts.mine;
        case 'standalone':
            return props.counts.standalone;
        case 'urgent':
            return props.counts.urgent;
        case 'done':
            return props.counts.done;
        default:
            return props.counts.all;
    }
});

function usePopover() {
    const open = ref(false);
    const root = ref<HTMLElement | null>(null);

    function onDocumentClick(event: MouseEvent) {
        if (!root.value?.contains(event.target as Node)) {
            open.value = false;
        }
    }

    function onKeydown(event: KeyboardEvent) {
        if (event.key === 'Escape') {
            open.value = false;
        }
    }

    watch(open, (isOpen) => {
        if (isOpen) {
            document.addEventListener('click', onDocumentClick, true);
            document.addEventListener('keydown', onKeydown);
        } else {
            document.removeEventListener('click', onDocumentClick, true);
            document.removeEventListener('keydown', onKeydown);
        }
    });

    onBeforeUnmount(() => {
        document.removeEventListener('click', onDocumentClick, true);
        document.removeEventListener('keydown', onKeydown);
    });

    return { open, root };
}

const projectMenu = usePopover();
const assigneeMenu = usePopover();

const projectLabel = computed(() => {
    if (props.projectId === 'all') {
        return 'Todos';
    }

    if (props.projectId === 'none') {
        return 'Sin Proyecto';
    }

    return (
        props.projects.find((project) => project.id === props.projectId)
            ?.title ?? 'Todos'
    );
});

const assigneeLabel = computed(() => {
    if (props.assigneeId === 'all') {
        return 'Todos';
    }

    return (
        props.assignees.find((assignee) => assignee.id === props.assigneeId)
            ?.name ?? 'Todos'
    );
});

const input = ref<HTMLInputElement | null>(null);

function onSearchInput(event: Event) {
    emit('update:search', (event.target as HTMLInputElement).value);
}

function selectProject(value: number | 'all' | 'none') {
    emit('update:projectId', value);
    projectMenu.open.value = false;
}

function selectAssignee(value: number | 'all') {
    emit('update:assigneeId', value);
    assigneeMenu.open.value = false;
}

function focus() {
    input.value?.focus();
}

defineExpose({ focus });
</script>

<template>
    <div class="bg-surface-container-lowest shadow-card rounded-xl">
        <div
            class="gap-space-md p-space-md flex flex-wrap items-center justify-between"
        >
            <div class="relative flex-1 sm:w-72">
                <AppIcon
                    name="search"
                    :size="16"
                    class="text-on-surface-variant pointer-events-none absolute top-1/2 left-3 -translate-y-1/2"
                />
                <input
                    ref="input"
                    type="search"
                    :value="search"
                    placeholder="Buscar tareas..."
                    class="bg-surface-container-low focus-within:bg-surface-container-lowest text-on-surface text-body-sm font-body-sm placeholder:text-on-surface-variant/60 focus:ring-primary/20 w-full rounded-lg py-1.5 pr-10 pl-9 focus:ring-2 focus:outline-none"
                    @input="onSearchInput"
                />
                <kbd
                    class="font-label-xs text-on-surface-variant/70 bg-surface-container pointer-events-none absolute top-1/2 right-2.5 hidden -translate-y-1/2 rounded px-1.5 py-0.5 text-[10px] sm:block"
                    >⌘F</kbd
                >
            </div>

            <div class="gap-space-sm flex flex-wrap items-center">
                <div ref="projectMenu.root" class="relative">
                    <button
                        type="button"
                        :aria-expanded="projectMenu.open.value"
                        class="bg-surface-container-low hover:bg-surface-container text-on-surface text-label-sm font-label-sm flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                        @click="
                            projectMenu.open.value = !projectMenu.open.value
                        "
                    >
                        <AppIcon name="folder_open" :size="16" />
                        <span>Proyecto: {{ projectLabel }}</span>
                        <AppIcon name="expand_more" :size="16" />
                    </button>

                    <div
                        v-if="projectMenu.open.value"
                        class="bg-surface-container-lowest shadow-popover absolute top-full right-0 left-0 z-30 mt-1 w-56 rounded-lg py-1"
                    >
                        <button
                            type="button"
                            class="text-label-sm font-label-sm flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                            :class="
                                projectId === 'all'
                                    ? 'bg-surface-container text-on-surface'
                                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                            "
                            @click="selectProject('all')"
                        >
                            Todos (incluye Sin Proyecto)
                        </button>
                        <button
                            v-for="project in projects"
                            :key="project.id"
                            type="button"
                            class="text-label-sm font-label-sm flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                            :class="
                                projectId === project.id
                                    ? 'bg-surface-container text-on-surface'
                                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                            "
                            @click="selectProject(project.id)"
                        >
                            {{ project.title }}
                        </button>
                        <button
                            type="button"
                            class="text-label-sm font-label-sm flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                            :class="
                                projectId === 'none'
                                    ? 'bg-surface-container text-on-surface'
                                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                            "
                            @click="selectProject('none')"
                        >
                            <AppIcon name="inbox" :size="16" />
                            Sin Proyecto
                        </button>
                    </div>
                </div>

                <div ref="assigneeMenu.root" class="relative">
                    <button
                        type="button"
                        :aria-expanded="assigneeMenu.open.value"
                        class="bg-surface-container-low hover:bg-surface-container text-on-surface text-label-sm font-label-sm flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                        @click="
                            assigneeMenu.open.value = !assigneeMenu.open.value
                        "
                    >
                        <AppIcon name="person_outline" :size="16" />
                        <span>Asignado: {{ assigneeLabel }}</span>
                        <AppIcon name="expand_more" :size="16" />
                    </button>

                    <div
                        v-if="assigneeMenu.open.value"
                        class="bg-surface-container-lowest shadow-popover absolute top-full right-0 left-0 z-30 mt-1 w-56 rounded-lg py-1"
                    >
                        <button
                            type="button"
                            class="text-label-sm font-label-sm flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                            :class="
                                assigneeId === 'all'
                                    ? 'bg-surface-container text-on-surface'
                                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                            "
                            @click="selectAssignee('all')"
                        >
                            Todos
                        </button>
                        <button
                            v-for="assignee in assignees"
                            :key="assignee.id"
                            type="button"
                            class="text-label-sm font-label-sm flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                            :class="
                                assigneeId === assignee.id
                                    ? 'bg-surface-container text-on-surface'
                                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                            "
                            @click="selectAssignee(assignee.id)"
                        >
                            <Avatar
                                :name="assignee.name"
                                :src="assignee.avatar"
                                :size="16"
                            />
                            {{ assignee.name }}
                        </button>
                    </div>
                </div>

                <div
                    class="bg-surface-container-low flex items-center rounded-lg p-0.5"
                >
                    <button
                        type="button"
                        :aria-pressed="view === 'list'"
                        aria-label="Vista de lista"
                        class="flex h-7 w-7 items-center justify-center rounded-md transition-colors"
                        :class="
                            view === 'list'
                                ? 'bg-surface-container-lowest text-primary shadow-card'
                                : 'text-on-surface-variant hover:text-on-surface'
                        "
                        @click="emit('update:view', 'list')"
                    >
                        <AppIcon name="view_agenda" :size="16" />
                    </button>
                    <button
                        type="button"
                        :aria-pressed="view === 'kanban'"
                        aria-label="Vista kanban"
                        class="flex h-7 w-7 items-center justify-center rounded-md transition-colors"
                        :class="
                            view === 'kanban'
                                ? 'bg-surface-container-lowest text-primary shadow-card'
                                : 'text-on-surface-variant hover:text-on-surface'
                        "
                        @click="emit('update:view', 'kanban')"
                    >
                        <AppIcon name="view_column" :size="16" />
                    </button>
                </div>

                <button
                    type="button"
                    class="bg-primary text-on-primary text-label-md font-label-md shadow-card hover:bg-primary-container gap-space-xs flex items-center rounded-lg px-3.5 py-1.5 transition-colors"
                    @click="emit('create')"
                >
                    <AppIcon name="add" :size="16" />
                    <span>Nueva Tarea</span>
                    <kbd
                        class="font-label-xs ml-1 rounded bg-white/20 px-1.5 py-0.5 text-[10px] text-white"
                        >C</kbd
                    >
                </button>
            </div>
        </div>

        <div
            class="border-surface-container px-space-md pt-space-xs flex items-center justify-between border-t"
        >
            <div
                class="gap-space-xs py-space-xs flex items-center overflow-x-auto"
            >
                <button
                    v-for="tabOption in tabs"
                    :key="tabOption.value"
                    type="button"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 whitespace-nowrap transition-colors"
                    :class="
                        tab === tabOption.value
                            ? 'bg-primary-fixed text-on-primary-fixed font-medium'
                            : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    "
                    @click="emit('update:tab', tabOption.value)"
                >
                    <AppIcon
                        v-if="tabOption.icon"
                        :name="tabOption.icon"
                        :size="14"
                    />
                    <span
                        v-if="tabOption.dot"
                        :class="tabOption.dot"
                        class="h-1.5 w-1.5 shrink-0 rounded-full"
                        aria-hidden="true"
                    />
                    {{ tabOption.label }}
                    <UiBadge
                        :label="
                            String(
                                tabOption.value === tab
                                    ? tabCount
                                    : counts[
                                          tabOption.value === 'all'
                                              ? 'all'
                                              : tabOption.value
                                      ],
                            )
                        "
                        tone="neutral"
                    />
                </button>
            </div>

            <div
                class="text-label-xs text-on-surface-variant hidden items-center gap-2 lg:flex"
            >
                <kbd
                    class="bg-surface-container rounded px-1.5 py-0.5 text-[10px]"
                    >↑</kbd
                >
                <kbd
                    class="bg-surface-container rounded px-1.5 py-0.5 text-[10px]"
                    >↓</kbd
                >
                <span>Navegar</span>
                <span aria-hidden="true">•</span>
                <span
                    class="cursor-not-allowed gap-1 opacity-50"
                    title="Próximamente"
                >
                    <kbd
                        class="bg-surface-container rounded px-1.5 py-0.5 text-[10px]"
                        >Espacio</kbd
                    >
                    <span>Previsualizar</span>
                </span>
            </div>
        </div>
    </div>
</template>
