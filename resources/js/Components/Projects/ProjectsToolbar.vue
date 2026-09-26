<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';

export type ProjectsSort = 'recent' | 'alpha' | 'progress';
export type ProjectsView = 'grid' | 'list';

const props = defineProps<{
    sort: ProjectsSort;
    view: ProjectsView;
}>();

const emit = defineEmits<{
    'update:sort': [value: ProjectsSort];
    'update:view': [value: ProjectsView];
    search: [term: string];
    create: [];
}>();

const sortOptions: { value: ProjectsSort; label: string }[] = [
    { value: 'recent', label: 'Recientes' },
    { value: 'alpha', label: 'Alfabético' },
    { value: 'progress', label: 'Progreso' },
];

const currentSortLabel = computed(
    () => sortOptions.find((option) => option.value === props.sort)?.label,
);

const open = ref(false);
const root = ref<HTMLElement | null>(null);
const input = ref<HTMLInputElement | null>(null);

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

function onSearchInput(event: Event) {
    emit('search', (event.target as HTMLInputElement).value);
}

function selectSort(value: ProjectsSort) {
    emit('update:sort', value);
    open.value = false;
}

function focus() {
    input.value?.focus();
}

defineExpose({ focus });
</script>

<template>
    <div class="gap-space-sm flex flex-wrap items-center">
        <div class="relative flex-1 sm:w-64">
            <AppIcon
                name="search"
                :size="16"
                class="text-on-surface-variant pointer-events-none absolute top-1/2 left-3 -translate-y-1/2"
            />
            <input
                ref="input"
                type="search"
                placeholder="Filtrar por nombre..."
                class="bg-surface-container-lowest text-on-surface text-body-sm font-body-sm shadow-card placeholder:text-on-surface-variant/60 focus:ring-primary/20 w-full rounded-lg py-1.5 pr-8 pl-9 focus:ring-2 focus:outline-none"
                @input="onSearchInput"
            />
        </div>

        <div ref="root" class="relative">
            <button
                type="button"
                :aria-expanded="open"
                class="bg-surface-container-lowest text-on-surface text-label-sm font-label-sm shadow-card hover:bg-surface-container-high gap-space-xs flex items-center rounded-lg px-3 py-1.5 transition-colors"
                @click="open = !open"
            >
                <AppIcon name="swap_vert" :size="16" />
                <span>{{ currentSortLabel }}</span>
                <AppIcon name="expand_more" :size="16" />
            </button>

            <div
                v-if="open"
                class="bg-surface-container-lowest shadow-popover absolute top-full right-0 left-0 z-30 mt-1 rounded-lg py-1"
            >
                <button
                    v-for="option in sortOptions"
                    :key="option.value"
                    type="button"
                    class="text-label-sm font-label-sm flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                    :class="
                        option.value === sort
                            ? 'bg-surface-container text-on-surface'
                            : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    "
                    @click="selectSort(option.value)"
                >
                    {{ option.label }}
                </button>
            </div>
        </div>

        <div
            class="bg-surface-container-low flex items-center rounded-lg p-0.5"
        >
            <button
                type="button"
                :aria-pressed="view === 'grid'"
                aria-label="Vista de cuadrícula"
                class="flex h-7 w-7 items-center justify-center rounded-md transition-colors"
                :class="
                    view === 'grid'
                        ? 'bg-surface-container-lowest text-primary shadow-card'
                        : 'text-on-surface-variant hover:text-on-surface'
                "
                @click="emit('update:view', 'grid')"
            >
                <AppIcon name="grid_view" :size="16" />
            </button>
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
        </div>

        <button
            type="button"
            class="bg-primary text-on-primary text-label-md font-label-md shadow-card hover:bg-primary-container gap-space-xs flex items-center rounded-lg px-3.5 py-1.5 transition-colors"
            @click="emit('create')"
        >
            <AppIcon name="add" :size="16" />
            <span>Nuevo Proyecto</span>
            <kbd
                class="font-label-xs ml-1 rounded bg-white/20 px-1.5 py-0.5 text-[10px] text-white"
                >N</kbd
            >
        </button>
    </div>
</template>
