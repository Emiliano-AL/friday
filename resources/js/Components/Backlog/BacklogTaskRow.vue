<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { BacklogTask } from '@/types/backlog';
import type { TaskPriorityValue } from '@/types/task';

const props = withDefaults(
    defineProps<{
        task: BacklogTask;
        hideProjectChip?: boolean;
    }>(),
    {
        hideProjectChip: false,
    },
);

const emit = defineEmits<{
    select: [task: BacklogTask];
}>();

const clickable = computed(() => props.task.canUpdate);

function onSelect(): void {
    if (clickable.value) {
        emit('select', props.task);
    }
}

const priorityChips: Record<
    TaskPriorityValue,
    { label: string; icon: string; classes: string }
> = {
    low: {
        label: 'Baja',
        icon: 'south',
        classes: 'text-on-surface-variant',
    },
    medium: { label: 'Media', icon: 'drag_handle', classes: 'text-primary' },
    high: {
        label: 'Alta',
        icon: 'priority_high',
        classes: 'bg-tertiary-fixed text-on-tertiary-fixed font-semibold',
    },
    urgent: {
        label: 'Urgente',
        icon: 'emergency',
        classes: 'bg-error-container text-on-error-container font-semibold',
    },
};

const priorityChip = computed(() => priorityChips[props.task.priority]);

const assigneeShortName = computed(() => {
    const name = props.task.assignee?.name;

    if (!name) {
        return '';
    }

    const parts = name.trim().split(/\s+/);

    return parts.length > 1 ? `${parts[0]} ${parts[1][0]}.` : parts[0];
});
</script>

<template>
    <div
        :role="clickable ? 'button' : undefined"
        :tabindex="clickable ? 0 : undefined"
        class="border-surface-container-low bg-surface-container-lowest gap-space-md px-space-md py-space-sm flex items-center justify-between border-b transition-colors"
        :class="
            clickable ? 'hover:bg-surface-container-low cursor-pointer' : ''
        "
        @click="onSelect"
        @keydown.enter.prevent="onSelect"
    >
        <div class="gap-space-sm flex min-w-0 flex-1 items-center">
            <span
                class="bg-primary h-1.5 w-1.5 shrink-0 rounded-full"
                aria-hidden="true"
            />

            <span
                v-if="task.code"
                class="text-label-xs font-label-xs bg-surface-container-low text-on-surface-variant shrink-0 rounded px-1.5 py-0.5 font-medium"
            >
                {{ task.code }}
            </span>

            <span
                class="font-body-md text-body-md text-on-surface truncate font-medium"
            >
                {{ task.title }}
            </span>
        </div>

        <div
            class="gap-space-md font-label-xs text-label-xs flex shrink-0 items-center"
        >
            <span
                v-if="!hideProjectChip && task.project"
                :title="task.project.title"
                class="bg-surface-container-low text-on-surface-variant flex items-center gap-1.5 rounded px-2 py-1 font-medium"
            >
                <span
                    class="bg-primary h-1.5 w-1.5 shrink-0 rounded-full"
                    aria-hidden="true"
                />
                <span class="max-w-[10rem] truncate">{{
                    task.project.title
                }}</span>
            </span>
            <span
                v-else-if="!hideProjectChip"
                class="bg-surface-container text-on-surface-variant flex items-center gap-1.5 rounded px-2 py-1 font-medium"
            >
                <AppIcon name="folder_off" :size="12" />
                Sin Proyecto
            </span>

            <span
                v-if="task.points !== null"
                class="bg-surface-container-low text-on-surface-variant rounded px-2 py-1 font-medium"
                :title="`${task.points} puntos`"
            >
                {{ task.points }} pts
            </span>

            <span
                :class="priorityChip.classes"
                class="flex items-center gap-1 rounded px-2 py-1"
            >
                <AppIcon :name="priorityChip.icon" :size="13" />
                {{ priorityChip.label }}
            </span>

            <span v-if="task.assignee" class="flex items-center gap-1.5">
                <Avatar
                    :name="task.assignee.name"
                    :src="task.assignee.avatar"
                    :size="20"
                />
                <span
                    class="text-on-surface hidden max-w-[6rem] truncate font-medium xl:inline"
                >
                    {{ assigneeShortName }}
                </span>
            </span>
            <span
                v-else
                class="text-on-surface-variant flex items-center gap-1"
            >
                <AppIcon name="person" :size="13" />
                Sin asignar
            </span>
        </div>
    </div>
</template>
