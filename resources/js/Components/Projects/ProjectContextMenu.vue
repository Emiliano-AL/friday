<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { DirectoryProject } from '@/types/project';

const props = defineProps<{
    project: DirectoryProject;
}>();

const emit = defineEmits<{
    edit: [project: DirectoryProject];
    transition: [
        project: DirectoryProject,
        status: 'active' | 'archived' | 'completed',
    ];
    view: [project: DirectoryProject];
}>();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const items = computed(() => {
    const list: { key: string; label: string; icon: string }[] = [
        { key: 'view', label: 'Ver detalle', icon: 'visibility' },
    ];

    if (props.project.isOwner) {
        list.push({ key: 'edit', label: 'Editar Proyecto', icon: 'edit' });

        if (props.project.status === 'active') {
            list.push({
                key: 'archive',
                label: 'Archivar',
                icon: 'archive',
            });
            list.push({
                key: 'complete',
                label: 'Completar',
                icon: 'task_alt',
            });
        } else {
            list.push({
                key: 'reactivate',
                label: 'Reactivar',
                icon: 'undo',
            });
        }
    }

    return list;
});

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

function onItemClick(key: string) {
    open.value = false;

    switch (key) {
        case 'view':
            emit('view', props.project);
            break;
        case 'edit':
            emit('edit', props.project);
            break;
        case 'archive':
            emit('transition', props.project, 'archived');
            break;
        case 'complete':
            emit('transition', props.project, 'completed');
            break;
        case 'reactivate':
            emit('transition', props.project, 'active');
            break;
    }
}
</script>

<template>
    <div ref="root" class="relative" @click.stop>
        <button
            type="button"
            :aria-expanded="open"
            aria-haspopup="menu"
            data-shell-popover-trigger="project-menu"
            class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
            @click="open = !open"
        >
            <AppIcon name="more_horiz" :size="18" />
        </button>

        <div
            v-if="open"
            data-shell-popover
            class="bg-surface-container-lowest shadow-popover absolute top-8 right-0 z-30 w-44 rounded-lg py-1"
        >
            <button
                v-for="item in items"
                :key="item.key"
                type="button"
                class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                @click="onItemClick(item.key)"
            >
                <AppIcon :name="item.icon" :size="16" />
                {{ item.label }}
            </button>
        </div>
    </div>
</template>
