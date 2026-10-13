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
    medium: {
        label: 'Media',
        icon: 'drag_handle',
        classes: 'text-on-surface-variant',
    },
    high: {
        label: 'Alta',
        icon: 'priority_high',
        classes: 'text-tertiary font-semibold',
    },
    urgent: {
        label: 'Urgente',
        icon: 'emergency',
        classes: 'text-error font-semibold',
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
        class="border-surface-container-low bg-surface-container-lowest gap-space-md px-space-md py-space-sm flex items-center justify-between border-b transition-colors last:border-b-0"
        :class="
            clickable ? 'hover:bg-surface-container-low cursor-pointer' : ''
        "
        @click="onSelect"
        @keydown.enter.prevent="onSelect"
    >
        <div class="gap-space-sm flex min-w-0 flex-1 items-center">
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

            <span
                v-if="task.points !== null"
                class="text-label-xs font-label-xs text-on-surface-variant shrink-0 tabular-nums"
                :title="`${task.points} puntos`"
            >
                {{ task.points }} pts
            </span>
        </div>

        <div class="gap-space-md text-label-sm flex shrink-0 items-center">
            <span
                v-if="!hideProjectChip"
                class="text-on-surface-variant hidden w-44 min-w-0 items-center gap-1.5 md:flex"
                :title="task.project?.title"
            >
                <template v-if="task.project">
                    <span
                        class="bg-primary h-1.5 w-1.5 shrink-0 rounded-full"
                        aria-hidden="true"
                    />
                    <span class="truncate">{{ task.project.title }}</span>
                </template>
                <span v-else class="italic">Sin proyecto</span>
            </span>

            <span
                :class="priorityChip.classes"
                class="text-label-xs font-label-xs flex w-20 items-center gap-1"
            >
                <AppIcon :name="priorityChip.icon" :size="13" />
                {{ priorityChip.label }}
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
                    <span class="text-on-surface hidden truncate xl:inline">
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
        </div>
    </div>
</template>
