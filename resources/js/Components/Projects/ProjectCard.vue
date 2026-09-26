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

const identityClasses = computed(() =>
    props.project.status === 'active'
        ? 'bg-primary/10 text-primary'
        : 'bg-surface-container text-on-surface-variant',
);

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
        class="bg-surface-container-lowest shadow-card hover:shadow-popover group p-space-lg relative flex min-h-[220px] cursor-pointer flex-col justify-between rounded-xl transition-all duration-200"
        @click="emit('open', project)"
        @keydown.enter="emit('open', project)"
    >
        <div
            class="absolute top-0 right-0 left-0 h-1 rounded-t-xl"
            :class="statusColor"
        />

        <div class="gap-space-sm flex items-start justify-between">
            <div class="gap-space-sm flex min-w-0 items-center">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    :class="identityClasses"
                >
                    <AppIcon name="folder" :size="20" />
                </div>
                <div class="min-w-0">
                    <UiBadge :label="project.statusLabel" tone="neutral" />
                    <div
                        class="text-on-surface-variant font-label-xs mt-1 flex items-center gap-1 truncate"
                    >
                        <AppIcon name="sprint" :size="14" />
                        <span class="truncate">{{
                            project.activeSprint?.name ?? 'Sin sprint activo'
                        }}</span>
                    </div>
                </div>
            </div>
            <ProjectContextMenu
                :project="project"
                @view="emit('view', $event)"
                @edit="emit('edit', $event)"
                @transition="onTransition"
            />
        </div>

        <div class="min-w-0">
            <h2
                class="font-headline-sm text-headline-sm text-on-surface group-hover:text-primary leading-snug transition-colors"
            >
                {{ project.title }}
            </h2>
            <p
                v-if="project.description"
                class="font-body-sm text-body-sm text-on-surface-variant mt-1 line-clamp-2"
            >
                {{ project.description }}
            </p>
        </div>

        <div class="gap-space-sm pt-space-lg flex flex-col">
            <div
                class="font-label-xs text-label-xs flex items-center justify-between"
            >
                <span class="flex items-center gap-1.5">
                    <span
                        class="h-2 w-2 shrink-0 rounded-full"
                        :class="statusColor"
                    />
                    <span class="text-on-surface-variant">{{
                        project.statusLabel
                    }}</span>
                </span>
                <span v-if="project.taskTotalCount > 0">
                    <strong class="text-on-surface"
                        >{{ project.progress }}%</strong
                    >
                    <span class="text-on-surface-variant font-normal">
                        ({{ project.taskDoneCount }}/{{
                            project.taskTotalCount
                        }})</span
                    >
                </span>
                <strong v-else class="text-on-surface">0%</strong>
            </div>

            <div
                class="bg-surface-container h-1.5 w-full overflow-hidden rounded-full"
            >
                <div
                    class="bg-primary h-full rounded-full transition-all"
                    :style="{ width: `${project.progress}%` }"
                />
            </div>

            <div class="pt-space-xs flex items-center justify-between">
                <span
                    class="text-on-surface-variant font-label-xs text-label-xs flex items-center gap-1"
                >
                    <AppIcon name="flag" :size="14" />
                    Equipo
                </span>
                <AvatarStack :people="people" :max="3" />
            </div>
        </div>
    </div>
</template>
