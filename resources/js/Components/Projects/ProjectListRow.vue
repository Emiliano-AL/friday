<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
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
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
            :class="project.color ? '' : 'bg-primary/10 text-primary'"
            :style="
                project.color
                    ? {
                          backgroundColor: `${project.color}1A`,
                          color: project.color,
                      }
                    : undefined
            "
            aria-hidden="true"
        >
            <AppIcon :name="project.icon ?? 'folder'" :size="16" />
        </span>
        <span
            class="font-body-sm text-body-sm text-on-surface min-w-0 flex-1 truncate font-medium"
        >
            {{ project.title }}
        </span>
        <UiBadge :label="project.statusLabel" tone="neutral" />
        <span class="hidden w-28 shrink-0 items-center gap-2 sm:flex">
            <span
                class="bg-surface-container h-1.5 flex-1 overflow-hidden rounded-full"
            >
                <span
                    class="block h-full rounded-full"
                    :class="project.color ? '' : 'bg-primary'"
                    :style="{
                        width: `${project.progress}%`,
                        backgroundColor: project.color ?? undefined,
                    }"
                />
            </span>
            <span
                class="text-on-surface-variant text-label-xs font-label-xs w-9 text-right tabular-nums"
            >
                {{ project.progress }}%
            </span>
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
