<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import { computed, ref } from 'vue';
import type { TaskItem } from './types';

const props = defineProps<{
    tasks: TaskItem[];
    sprints: { id: number; name: string; startDate: string }[];
}>();

const sortKey = ref<'priority' | 'sprint'>('priority');
const sortDir = ref<'desc' | 'asc'>('desc');
const sprintFilter = ref<'all' | 'none' | number>('all');

const priorityRank: Record<string, number> = {
    urgent: 3,
    high: 2,
    medium: 1,
    low: 0,
};

const visibleTasks = computed(() => {
    let list = props.tasks;

    if (sprintFilter.value === 'none') {
        list = list.filter((task) => task.sprint === null);
    } else if (sprintFilter.value !== 'all') {
        list = list.filter((task) => task.sprint?.id === sprintFilter.value);
    }

    if (sortKey.value === 'priority') {
        const factor = sortDir.value === 'desc' ? -1 : 1;

        return [...list].sort(
            (a, b) =>
                factor * (priorityRank[a.priority] - priorityRank[b.priority]),
        );
    }

    const startDates = new Map(
        props.sprints.map((sprint) => [sprint.id, sprint.startDate]),
    );

    return [...list].sort((a, b) => {
        const aDate = a.sprint
            ? (startDates.get(a.sprint.id) ?? '9999-12-31')
            : '9999-12-31';
        const bDate = b.sprint
            ? (startDates.get(b.sprint.id) ?? '9999-12-31')
            : '9999-12-31';

        return aDate.localeCompare(bDate);
    });
});
</script>

<template>
    <div class="mt-2">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <InputLabel for="backlog-sort" value="Ordenar por" />
                <select
                    id="backlog-sort"
                    v-model="sortKey"
                    class="mt-1 block rounded-md border-gray-300 shadow-sm"
                >
                    <option value="priority">Prioridad</option>
                    <option value="sprint">Sprint</option>
                </select>
            </div>
            <div v-if="sortKey === 'priority'">
                <InputLabel for="backlog-dir" value="Dirección" />
                <select
                    id="backlog-dir"
                    v-model="sortDir"
                    class="mt-1 block rounded-md border-gray-300 shadow-sm"
                >
                    <option value="desc">Mayor prioridad primero</option>
                    <option value="asc">Menor prioridad primero</option>
                </select>
            </div>
            <div>
                <InputLabel for="backlog-sprint" value="Sprint" />
                <select
                    id="backlog-sprint"
                    v-model="sprintFilter"
                    class="mt-1 block rounded-md border-gray-300 shadow-sm"
                >
                    <option value="all">Todas</option>
                    <option value="none">Sin sprint</option>
                    <option
                        v-for="sprint in sprints"
                        :key="sprint.id"
                        :value="sprint.id"
                    >
                        {{ sprint.name }}
                    </option>
                </select>
            </div>
        </div>

        <ul class="mt-4 space-y-2">
            <li
                v-for="task in visibleTasks"
                :key="task.id"
                class="flex items-center justify-between gap-4 text-sm text-gray-900"
            >
                <div>
                    <span>{{ task.title }}</span>
                    <div class="text-xs text-gray-500">
                        {{ task.sprint ? task.sprint.name : 'Sin sprint' }}
                        ·
                        {{
                            task.assignee
                                ? task.assignee.name
                                : 'Sin responsable'
                        }}
                    </div>
                </div>
                <span class="flex shrink-0 gap-2 text-xs">
                    <span class="rounded bg-gray-100 px-2 py-0.5">{{
                        task.priorityLabel
                    }}</span>
                    <span class="rounded bg-gray-100 px-2 py-0.5">{{
                        task.statusLabel
                    }}</span>
                </span>
            </li>
            <li v-if="visibleTasks.length === 0" class="text-sm text-gray-500">
                Sin tareas para este filtro.
            </li>
        </ul>
    </div>
</template>
