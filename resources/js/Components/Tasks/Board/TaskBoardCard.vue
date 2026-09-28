<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { BoardTask } from '@/types/task';

const props = defineProps<{
    task: BoardTask;
    draggable?: boolean;
}>();

const emit = defineEmits<{
    dragstart: [event: DragEvent, task: BoardTask];
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
            return { icon: 'drag_handle', classes: 'text-primary' };
        case 'high':
            return {
                icon: 'priority_high',
                classes:
                    'bg-tertiary-fixed text-on-tertiary-fixed font-semibold',
            };
        default:
            return {
                icon: 'emergency',
                classes:
                    'bg-error-container text-on-error-container font-semibold',
            };
    }
});

const accentClass = computed(() => {
    if (props.task.status === 'in_progress') {
        return 'border-primary';
    }

    if (props.task.status === 'done') {
        return 'border-transparent opacity-80';
    }

    return 'border-transparent hover:border-primary-container';
});
</script>

<template>
    <article
        :draggable="draggable"
        class="group gap-space-xs bg-surface-container-lowest p-space-md shadow-card hover:shadow-popover relative flex cursor-pointer flex-col rounded-lg border-l-2 transition-all duration-150"
        :class="accentClass"
        role="button"
        tabindex="0"
        @click="emit('open', task)"
        @keydown.enter="emit('open', task)"
        @dragstart="emit('dragstart', $event, task)"
    >
        <div class="flex min-w-0 items-center justify-between">
            <AppIcon
                :name="typeIcon.icon"
                :size="16"
                :class="typeIcon.classes"
                class="shrink-0"
                :title="task.typeLabel"
            />

            <span
                v-if="task.project"
                :title="task.project.title"
                class="bg-surface-container-low text-on-surface-variant flex items-center gap-1.5 rounded px-2 py-1 font-medium"
            >
                <span
                    class="bg-primary h-1.5 w-1.5 shrink-0 rounded-full"
                    aria-hidden="true"
                />
                <span class="max-w-[9rem] truncate">{{
                    task.project.title
                }}</span>
            </span>
            <span
                v-else
                class="bg-surface-container text-on-surface-variant flex items-center gap-1.5 rounded px-2 py-1 font-medium"
            >
                <AppIcon name="folder_off" :size="12" />
                Sin Proyecto
            </span>
        </div>

        <h3
            class="text-body-md font-body-sm text-on-surface group-hover:text-primary leading-snug font-medium transition-colors"
            :class="
                task.status === 'done'
                    ? 'text-on-surface-variant line-through'
                    : ''
            "
        >
            {{ task.title }}
        </h3>

        <p
            class="text-label-xs font-label-xs"
            :class="
                task.sprint
                    ? 'text-on-surface-variant'
                    : 'text-on-surface-variant italic'
            "
        >
            {{ task.sprint?.name ?? 'Sin Sprint' }}
        </p>

        <div class="gap-space-sm pt-space-xs flex items-center justify-between">
            <div class="flex min-w-0 items-center gap-2">
                <span
                    :class="priorityChip.classes"
                    class="text-label-xs font-label-xs flex items-center gap-1 rounded px-2 py-1"
                >
                    <AppIcon :name="priorityChip.icon" :size="12" />
                    {{ task.priorityLabel }}
                </span>

                <span
                    v-if="task.commentsCount > 0"
                    class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1"
                >
                    <AppIcon name="chat_bubble_outline" :size="12" />
                    {{ task.commentsCount }}
                </span>
            </div>

            <Avatar
                v-if="task.assignee"
                :name="task.assignee.name"
                :src="task.assignee.avatar"
                :size="24"
            />
            <AppIcon
                v-else
                name="person"
                :size="24"
                class="text-on-surface-variant"
            />
        </div>
    </article>
</template>
