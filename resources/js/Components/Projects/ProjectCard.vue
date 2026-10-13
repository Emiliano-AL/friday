<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import AvatarStack from '@/Components/Projects/AvatarStack.vue';
import ProjectContextMenu from '@/Components/Projects/ProjectContextMenu.vue';
import type { DirectoryProject } from '@/types/project';
import { tiptapToPlainText } from '@/utils/tiptapContent';

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

const statusBorder = computed(() => {
    switch (props.project.status) {
        case 'active':
            return 'border-t-primary';
        case 'completed':
            return 'border-t-tertiary';
        case 'archived':
            return 'border-t-outline-variant';
    }
});

const targetDate = computed(() => {
    if (!props.project.targetDate) {
        return null;
    }

    const date = new Date(`${props.project.targetDate}T00:00:00`);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return {
        label: date.toLocaleDateString('es-MX', {
            day: 'numeric',
            month: 'short',
        }),
        isOverdue: props.project.status === 'active' && date < today,
    };
});

const identityClasses = computed(() =>
    props.project.status === 'active'
        ? 'bg-primary/10 text-primary'
        : 'bg-surface-container text-on-surface-variant',
);

const descriptionExcerpt = computed(() =>
    tiptapToPlainText(props.project.description),
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
        class="bg-surface-container-lowest shadow-card hover:shadow-popover group p-space-lg relative flex min-h-[220px] cursor-pointer flex-col rounded-xl border-t-[3px] transition-shadow duration-200"
        :class="project.color ? '' : statusBorder"
        :style="project.color ? { borderTopColor: project.color } : undefined"
        @click="emit('open', project)"
        @keydown.enter="emit('open', project)"
    >
        <div class="gap-space-md flex items-start">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                :class="project.color ? '' : identityClasses"
                :style="
                    project.color
                        ? {
                              backgroundColor: `${project.color}1A`,
                              color: project.color,
                          }
                        : undefined
                "
            >
                <AppIcon :name="project.icon ?? 'folder'" :size="20" />
            </div>
            <div class="min-w-0 flex-1">
                <h2
                    class="font-headline-sm text-headline-sm text-on-surface group-hover:text-primary line-clamp-2 text-balance transition-colors"
                >
                    {{ project.title }}
                </h2>
                <p
                    class="text-on-surface-variant text-label-sm font-label-sm mt-0.5 flex min-w-0 items-center gap-1.5"
                >
                    <span
                        v-if="project.key"
                        class="text-on-surface shrink-0 tracking-wide"
                        >{{ project.key }}</span
                    >
                    <span
                        v-if="project.key"
                        aria-hidden="true"
                        class="text-outline-variant"
                        >·</span
                    >
                    <AppIcon name="sprint" :size="14" class="shrink-0" />
                    <span class="truncate">{{
                        project.activeSprint?.name ?? 'Sin sprint activo'
                    }}</span>
                </p>
            </div>
            <ProjectContextMenu
                :project="project"
                @view="emit('view', $event)"
                @edit="emit('edit', $event)"
                @transition="onTransition"
            />
        </div>

        <p
            v-if="descriptionExcerpt"
            class="text-body-md font-body-md text-on-surface-variant mt-space-md line-clamp-2"
        >
            {{ descriptionExcerpt }}
        </p>

        <div
            class="border-surface-container gap-space-sm pt-space-md mt-auto flex flex-col border-t"
            :class="descriptionExcerpt ? 'mt-space-lg' : ''"
        >
            <div class="flex items-baseline justify-between">
                <span
                    class="text-label-md font-label-md text-on-surface font-semibold tabular-nums"
                    >{{ project.progress }}%</span
                >
                <span
                    class="text-label-sm text-on-surface-variant tabular-nums"
                >
                    {{ project.taskDoneCount }} de
                    {{ project.taskTotalCount }} tareas
                </span>
            </div>

            <div
                class="bg-surface-container h-1.5 w-full overflow-hidden rounded-full"
                role="progressbar"
                :aria-valuenow="project.progress"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="`Avance de ${project.title}`"
            >
                <div
                    class="h-full rounded-full transition-all"
                    :class="project.color ? '' : 'bg-primary'"
                    :style="{
                        width: `${project.progress}%`,
                        backgroundColor: project.color ?? undefined,
                    }"
                />
            </div>

            <div class="gap-space-md flex items-center justify-between pt-1">
                <span
                    class="text-label-sm text-on-surface-variant gap-space-md flex min-w-0 items-center"
                >
                    <span class="flex items-center gap-1.5">
                        <span
                            class="h-2 w-2 shrink-0 rounded-full"
                            :class="statusColor"
                        />
                        {{ project.statusLabel }}
                    </span>
                    <span
                        v-if="targetDate"
                        class="flex items-center gap-1"
                        :class="targetDate.isOverdue ? 'text-error' : ''"
                        :title="
                            targetDate.isOverdue
                                ? 'Fecha objetivo vencida'
                                : 'Fecha objetivo'
                        "
                    >
                        <AppIcon name="calendar_today" :size="13" />
                        {{ targetDate.label }}
                    </span>
                </span>
                <AvatarStack :people="people" :max="3" />
            </div>
        </div>
    </div>
</template>
