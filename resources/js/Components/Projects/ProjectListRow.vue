<script setup lang="ts">
import { computed } from 'vue';
import AvatarStack from '@/Components/Projects/AvatarStack.vue';
import ProjectContextMenu from '@/Components/Projects/ProjectContextMenu.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { DirectoryProject } from '@/types/project';

const props = defineProps<{
    project: DirectoryProject;
}>();

const emit = defineEmits<{
    open: [project: DirectoryProject];
    edit: [project: DirectoryProject];
    transition: [
        project: DirectoryProject,
        status: 'active' | 'archived' | 'completed',
    ];
    view: [project: DirectoryProject];
}>();

const statusColor = computed(() => {
    switch (props.project.status) {
        case 'active':
            return 'bg-primary';
        case 'completed':
            return 'bg-tertiary';
        case 'archived':
            return 'bg-outline-variant';
    }
});

const people = computed(() => [props.project.owner, ...props.project.members]);

function onTransition(
    project: DirectoryProject,
    status: 'active' | 'archived' | 'completed',
) {
    emit('transition', project, status);
}
</script>

<template>
    <div
        role="link"
        tabindex="0"
        class="bg-surface-container-lowest shadow-card hover:shadow-popover group gap-space-md px-space-md flex cursor-pointer items-center rounded-xl py-3 transition-all"
        @click="emit('open', project)"
        @keydown.enter="emit('open', project)"
    >
        <span
            class="h-2 w-2 shrink-0 rounded-full"
            :class="statusColor"
            aria-hidden="true"
        />
        <span
            class="font-body-sm text-body-sm text-on-surface min-w-0 flex-1 truncate font-medium"
        >
            {{ project.title }}
        </span>
        <UiBadge :label="project.statusLabel" tone="neutral" />
        <span
            class="text-on-surface-variant text-label-xs font-label-xs w-14 text-right"
        >
            {{ project.progress }}%
        </span>
        <AvatarStack :people="people" :max="3" />
        <ProjectContextMenu
            :project="project"
            @view="emit('view', $event)"
            @edit="emit('edit', $event)"
            @transition="onTransition"
        />
    </div>
</template>
