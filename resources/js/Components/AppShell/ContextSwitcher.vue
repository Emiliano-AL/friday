<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { create, show } from '@/routes/projects';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { ProjectSummary } from '@/types/shell';

const props = defineProps<{
    projects: ProjectSummary[];
}>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const page = usePage();

const currentId = computed(() => {
    const match = page.url.match(/^\/projects\/(\d+)/);

    return match ? Number(match[1]) : null;
});

const current = computed(
    () =>
        props.projects.find((project) => project.id === currentId.value) ??
        null,
);

const currentTitle = computed(() => current.value?.title ?? 'Mi Espacio');
const currentInitial = computed(
    () => currentTitle.value.trim().charAt(0).toUpperCase() || 'M',
);

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
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            :aria-expanded="open"
            class="bg-surface-container-lowest px-space-md py-space-sm shadow-card hover:bg-surface-container-high flex w-full items-center justify-between rounded-lg transition-colors"
            @click="open = !open"
        >
            <span class="gap-space-sm flex min-w-0 items-center">
                <span
                    class="bg-primary-container text-label-xs text-on-primary flex h-5 w-5 shrink-0 items-center justify-center rounded font-semibold"
                    >{{ currentInitial }}</span
                >
                <span class="text-label-md text-on-surface truncate">{{
                    currentTitle
                }}</span>
            </span>
            <AppIcon name="unfold_more" class="text-on-surface-variant" />
        </button>

        <div
            v-if="open"
            class="bg-surface-container-lowest p-space-xs shadow-popover absolute top-full right-0 left-0 z-50 mt-1 flex flex-col gap-0.5 rounded-xl"
        >
            <template v-if="projects.length > 0">
                <Link
                    v-for="project in projects"
                    :key="project.id"
                    :href="show.url(project.id)"
                    class="gap-space-sm px-space-md py-space-sm text-label-md flex items-center rounded-lg transition-colors"
                    :class="
                        project.id === currentId
                            ? 'bg-surface-container text-on-surface font-medium'
                            : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'
                    "
                    @click="open = false"
                >
                    <span
                        class="bg-surface-container-high text-label-xs text-on-surface-variant flex h-5 w-5 shrink-0 items-center justify-center rounded font-semibold"
                        >{{
                            project.title.trim().charAt(0).toUpperCase()
                        }}</span
                    >
                    <span class="flex-1 truncate">{{ project.title }}</span>
                </Link>
            </template>
            <Link
                v-else
                :href="create.url()"
                class="gap-space-sm px-space-md py-space-sm text-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex items-center rounded-lg transition-colors"
                @click="open = false"
            >
                <AppIcon name="add" />
                <span class="flex-1 truncate">Crear tu primer proyecto</span>
            </Link>
        </div>
    </div>
</template>
