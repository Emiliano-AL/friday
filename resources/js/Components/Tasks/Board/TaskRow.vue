<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { BoardTask, TaskStatusValue } from '@/types/task';

const props = defineProps<{
    task: BoardTask;
    canWrite: boolean;
}>();

const emit = defineEmits<{
    open: [task: BoardTask];
    edit: [task: BoardTask];
    status: [task: BoardTask, status: TaskStatusValue];
    comment: [task: BoardTask];
    remove: [task: BoardTask];
}>();

const statusOptions: { value: TaskStatusValue; label: string; icon: string }[] =
    [
        { value: 'backlog', label: 'Backlog', icon: 'view_list' },
        { value: 'todo', label: 'Por Hacer', icon: 'circle' },
        { value: 'in_progress', label: 'En Curso', icon: 'sync' },
        { value: 'in_review', label: 'En Revisión', icon: 'rate_review' },
        { value: 'done', label: 'Hecho', icon: 'check_circle' },
    ];

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

const statusMenu = usePopover();
const actionsMenu = usePopover();

const statusButtonClasses = computed(() => {
    switch (props.task.status) {
        case 'in_progress':
            return 'bg-primary text-on-primary';
        case 'in_review':
            return 'bg-tertiary text-white';
        case 'done':
            return 'bg-outline text-white';
        default:
            return 'bg-surface-container';
    }
});

const statusIcon = computed(() => {
    switch (props.task.status) {
        case 'in_progress':
            return 'sync';
        case 'in_review':
            return 'rate_review';
        case 'done':
            return 'check';
        default:
            return '';
    }
});

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

const assigneeShortName = computed(() => {
    const name = props.task.assignee?.name;

    if (!name) {
        return '';
    }

    const parts = name.trim().split(/\s+/);

    return parts.length > 1 ? `${parts[0]} ${parts[1][0]}.` : parts[0];
});

function selectStatus(value: TaskStatusValue) {
    emit('status', props.task, value);
    statusMenu.open.value = false;
    actionsMenu.open.value = false;
}

function onAction(key: 'edit' | 'comment' | 'remove') {
    actionsMenu.open.value = false;

    if (key === 'edit') {
        emit('edit', props.task);
    } else if (key === 'comment') {
        emit('comment', props.task);
    } else {
        emit('remove', props.task);
    }
}
</script>

<template>
    <div
        role="button"
        tabindex="0"
        class="group gap-space-md px-space-md py-space-sm flex items-center justify-between border-b transition-colors"
        :class="
            task.project?.color
                ? 'border-b-[color-mix(in_srgb,var(--project-tint)_35%,var(--color-surface-container-low))] bg-[color-mix(in_srgb,var(--project-tint)_7%,var(--color-surface-container-lowest))] hover:bg-[color-mix(in_srgb,var(--project-tint)_13%,var(--color-surface-container-lowest))]'
                : 'border-surface-container-low bg-surface-container-lowest hover:bg-surface-container-low'
        "
        :style="
            task.project?.color ? { '--project-tint': task.project.color } : {}
        "
        @click="emit('open', task)"
        @keydown.enter="emit('open', task)"
    >
        <div class="gap-space-sm flex min-w-0 flex-1 items-center">
            <div ref="statusMenu.root" class="relative shrink-0" @click.stop>
                <button
                    type="button"
                    title="Cambiar estado"
                    :aria-expanded="statusMenu.open.value"
                    :class="statusButtonClasses"
                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full transition-colors"
                    @click="statusMenu.open.value = !statusMenu.open.value"
                >
                    <span
                        v-if="!statusIcon"
                        class="bg-outline-variant h-1.5 w-1.5 rounded-full"
                        aria-hidden="true"
                    />
                    <AppIcon v-else :name="statusIcon" :size="10" />
                </button>

                <div
                    v-if="statusMenu.open.value"
                    class="bg-surface-container-lowest shadow-popover absolute top-6 left-0 z-30 w-40 rounded-lg py-1"
                >
                    <button
                        v-for="option in statusOptions"
                        :key="option.value"
                        type="button"
                        class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                        @click="selectStatus(option.value)"
                    >
                        <AppIcon :name="option.icon" :size="16" />
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <AppIcon
                :name="typeIcon.icon"
                :size="15"
                :class="typeIcon.classes"
                class="shrink-0"
                :title="task.typeLabel"
            />

            <span
                class="font-body-md text-body-md text-on-surface group-hover:text-primary truncate font-medium transition-colors"
            >
                {{ task.title }}
            </span>
        </div>

        <div
            class="gap-space-md text-label-sm flex shrink-0 items-center"
        >
            <span
                class="text-on-surface-variant hidden w-44 min-w-0 items-center gap-1.5 lg:flex"
                :title="task.project?.title"
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
                    <span class="truncate">{{ task.project.title }}</span>
                </template>
                <span v-else class="italic">Sin proyecto</span>
            </span>

            <span
                class="text-on-surface-variant hidden w-36 truncate xl:inline"
                :class="{ italic: !task.sprint }"
            >
                {{ task.sprint?.name ?? 'Sin sprint' }}
            </span>

            <span
                :class="priorityChip.classes"
                class="text-label-xs font-label-xs flex w-20 items-center gap-1"
            >
                <AppIcon :name="priorityChip.icon" :size="13" />
                {{ task.priorityLabel }}
            </span>

            <span
                class="flex w-6 items-center gap-1.5 xl:w-28"
                :title="task.assignee?.name ?? 'Sin asignar'"
            >
                <template v-if="task.assignee">
                    <Avatar
                        :name="task.assignee.name"
                        :src="task.assignee.avatar"
                        :size="20"
                    />
                    <span
                        class="text-on-surface hidden truncate xl:inline"
                    >
                        {{ assigneeShortName }}
                    </span>
                </template>
                <template v-else>
                    <span
                        class="border-outline-variant text-on-surface-variant flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-dashed"
                    >
                        <AppIcon name="person" :size="11" />
                    </span>
                    <span
                        class="text-on-surface-variant hidden truncate xl:inline"
                        >Sin asignar</span
                    >
                </template>
            </span>

            <div ref="actionsMenu.root" class="relative" @click.stop>
                <button
                    type="button"
                    :aria-expanded="actionsMenu.open.value"
                    aria-haspopup="menu"
                    data-shell-popover-trigger="task-menu"
                    class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-7 w-7 items-center justify-center rounded-lg opacity-0 transition-opacity group-hover:opacity-100 focus-visible:opacity-100"
                    @click="actionsMenu.open.value = !actionsMenu.open.value"
                >
                    <AppIcon name="more_horiz" :size="18" />
                </button>

                <div
                    v-if="actionsMenu.open.value"
                    data-shell-popover
                    class="bg-surface-container-lowest shadow-popover absolute top-8 right-0 z-30 w-52 rounded-lg py-1"
                >
                    <button
                        v-if="canWrite"
                        type="button"
                        class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                        @click="onAction('edit')"
                    >
                        <AppIcon name="edit" :size="16" />
                        Editar
                    </button>

                    <div class="border-surface-container my-1 border-t" />

                    <p class="text-label-xs text-on-surface-variant px-3 py-1">
                        Cambiar estado
                    </p>
                    <button
                        v-for="option in statusOptions"
                        :key="option.value"
                        type="button"
                        class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                        @click="selectStatus(option.value)"
                    >
                        <AppIcon :name="option.icon" :size="16" />
                        {{ option.label }}
                    </button>

                    <div class="border-surface-container my-1 border-t" />

                    <button
                        type="button"
                        class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                        @click="onAction('comment')"
                    >
                        <AppIcon name="chat_bubble_outline" :size="16" />
                        Comentarios ({{ task.commentsCount }})
                    </button>

                    <template v-if="canWrite">
                        <div class="border-surface-container my-1 border-t" />
                        <button
                            type="button"
                            class="text-label-sm font-label-sm text-error hover:bg-surface-container-low flex w-full items-center gap-2 px-3 py-1.5 text-left transition-colors"
                            @click="onAction('remove')"
                        >
                            <AppIcon name="delete" :size="16" />
                            Eliminar
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
