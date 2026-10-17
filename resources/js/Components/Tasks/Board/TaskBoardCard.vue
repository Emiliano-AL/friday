<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { BoardTask } from '@/types/task';

const props = defineProps<{
    task: BoardTask;
    draggable?: boolean;
    dragging?: boolean;
    landed?: boolean;
}>();

const emit = defineEmits<{
    dragstart: [event: DragEvent, task: BoardTask];
    dragend: [];
    open: [task: BoardTask];
    edit: [task: BoardTask];
    comment: [task: BoardTask];
}>();

const typeIcon = computed(() => {
    switch (props.task.type) {
        case 'bug':
            return { icon: 'bug_report', classes: 'text-error' };
        case 'feature':
            return { icon: 'new_releases', classes: 'text-primary' };
        case 'test':
            return { icon: 'science', classes: 'text-tertiary' };
        default:
            return { icon: 'task_alt', classes: 'text-on-surface-variant' };
    }
});

const priorityChip = computed(() => {
    switch (props.task.priority) {
        case 'low':
            return { icon: 'south', classes: 'text-on-surface-variant' };
        case 'medium':
            return { icon: 'drag_handle', classes: 'text-on-surface-variant' };
        case 'high':
            return {
                icon: 'priority_high',
                classes: 'text-tertiary font-semibold',
            };
        default:
            return {
                icon: 'emergency',
                classes: 'text-error font-semibold',
            };
    }
});

</script>

<template>
    <article
        :draggable="draggable"
        class="group gap-space-sm p-space-md shadow-card hover:shadow-popover relative flex flex-col rounded-lg transition-[opacity,box-shadow] duration-150"
        :style="
            task.project?.color ? { '--project-tint': task.project.color } : {}
        "
        :class="[
            task.project?.color
                ? 'bg-[color-mix(in_srgb,var(--project-tint)_9%,var(--color-surface-container-lowest))]'
                : 'bg-surface-container-lowest',
            draggable ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer',
            dragging
                ? 'outline-primary/50 opacity-40 shadow-none outline-2 outline-dashed'
                : task.status === 'done'
                  ? 'opacity-80'
                  : '',
            landed ? 'task-landed' : '',
        ]"
        role="button"
        tabindex="0"
        @click="emit('open', task)"
        @keydown.enter="emit('open', task)"
        @dragstart="emit('dragstart', $event, task)"
        @dragend="emit('dragend')"
    >
        <span
            v-if="task.project?.color"
            aria-hidden="true"
            class="pointer-events-none absolute inset-y-2.5 left-1 w-[3px] rounded-full bg-(--project-tint) opacity-70"
        />
        <div class="flex min-w-0 flex-col gap-1">
            <h3
                class="text-body-md font-body-md text-on-surface group-hover:text-primary leading-snug font-medium transition-colors"
                :class="
                    task.status === 'done'
                        ? 'text-on-surface-variant line-through'
                        : ''
                "
            >
                {{ task.title }}
            </h3>
            <p
                class="text-label-sm text-on-surface-variant flex min-w-0 items-center gap-1.5"
            >
                <template v-if="task.project">
                    <span
                        class="bg-primary h-1.5 w-1.5 shrink-0 rounded-full"
                        :style="
                            task.project.color
                                ? { backgroundColor: task.project.color }
                                : {}
                        "
                        aria-hidden="true"
                    />
                    <span class="truncate" :title="task.project.title">{{
                        task.project.title
                    }}</span>
                </template>
                <span v-else class="shrink-0 italic">Sin proyecto</span>
                <template v-if="task.sprint">
                    <span aria-hidden="true" class="text-outline-variant"
                        >·</span
                    >
                    <span class="truncate">{{ task.sprint.name }}</span>
                </template>
            </p>
        </div>

        <div class="gap-space-sm flex items-center justify-between">
            <div
                class="gap-space-md text-label-xs font-label-xs flex min-w-0 items-center"
            >
                <AppIcon
                    :name="typeIcon.icon"
                    :size="14"
                    :class="typeIcon.classes"
                    class="shrink-0"
                    :title="task.typeLabel"
                />
                <span
                    :class="priorityChip.classes"
                    class="flex items-center gap-1"
                >
                    <AppIcon :name="priorityChip.icon" :size="13" />
                    {{ task.priorityLabel }}
                </span>
                <span
                    v-if="task.commentsCount > 0"
                    class="text-on-surface-variant flex items-center gap-1"
                    :aria-label="`${task.commentsCount} comentarios`"
                >
                    <AppIcon name="chat_bubble_outline" :size="12" />
                    {{ task.commentsCount }}
                </span>
            </div>

            <Avatar
                v-if="task.assignee"
                :name="task.assignee.name"
                :src="task.assignee.avatar"
                :size="22"
            />
            <span
                v-else
                class="border-outline-variant text-on-surface-variant flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full border border-dashed"
                title="Sin asignar"
            >
                <AppIcon name="person" :size="12" />
            </span>
        </div>
    </article>
</template>
