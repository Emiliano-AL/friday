<script setup lang="ts">
import { computed } from 'vue';
import BacklogTaskRow from '@/Components/Backlog/BacklogTaskRow.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { BacklogTask } from '@/types/backlog';
import type { TaskPriorityValue } from '@/types/task';

const props = withDefaults(
    defineProps<{
        tasks: BacklogTask[];
        sort?: 'priority' | 'recent';
        hideProjectChip?: boolean;
    }>(),
    {
        sort: 'priority',
        hideProjectChip: false,
    },
);

const emit = defineEmits<{
    select: [task: BacklogTask];
}>();

const sections: {
    key: string;
    name: string;
    priorities: TaskPriorityValue[];
}[] = [
    {
        key: 'high',
        name: 'Alta y urgente',
        priorities: ['high', 'urgent'],
    },
    { key: 'medium', name: 'Media', priorities: ['medium'] },
    { key: 'low', name: 'Baja e ideas', priorities: ['low'] },
];

function byNewest(a: BacklogTask, b: BacklogTask): number {
    return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
}

const groupedSections = computed(() =>
    sections
        .map((section) => ({
            ...section,
            tasks: props.tasks
                .filter((task) => section.priorities.includes(task.priority))
                .sort(byNewest),
        }))
        .filter((section) => section.tasks.length > 0),
);

const recentTasks = computed(() => [...props.tasks].sort(byNewest));
</script>

<template>
    <div
        v-if="sort === 'recent'"
        class="bg-surface-container-lowest shadow-card flex flex-col overflow-hidden rounded-xl"
    >
        <BacklogTaskRow
            v-for="task in recentTasks"
            :key="task.id"
            :task="task"
            :hide-project-chip="hideProjectChip"
            @select="emit('select', $event)"
        />
    </div>

    <div v-else class="gap-space-md flex flex-col">
        <section
            v-for="section in groupedSections"
            :key="section.key"
            class="gap-space-xs flex flex-col"
        >
            <header class="px-space-xs flex items-center justify-between">
                <h2
                    class="text-label-sm font-label-sm text-on-surface-variant font-semibold tracking-wider uppercase"
                >
                    {{ section.name }}
                </h2>
                <UiBadge :label="String(section.tasks.length)" tone="neutral" />
            </header>

            <div
                class="bg-surface-container-lowest shadow-card flex flex-col overflow-hidden rounded-xl"
            >
                <BacklogTaskRow
                    v-for="task in section.tasks"
                    :key="task.id"
                    :task="task"
                    :hide-project-chip="hideProjectChip"
                    @select="emit('select', $event)"
                />
            </div>
        </section>
    </div>
</template>
