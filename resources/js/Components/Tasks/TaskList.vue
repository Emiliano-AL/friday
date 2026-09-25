<script setup lang="ts">
import type { TaskItem } from './types';

defineProps<{
    tasks: TaskItem[];
    editable: boolean;
    deletable: boolean;
}>();

const emit = defineEmits<{
    edit: [task: TaskItem];
    comment: [task: TaskItem];
    remove: [task: TaskItem];
}>();
</script>

<template>
    <ul class="mt-2 space-y-2">
        <li
            v-for="task in tasks"
            :key="task.id"
            class="flex items-center justify-between gap-4 text-sm text-gray-900"
        >
            <div>
                <span>{{ task.title }}</span>
                <div class="text-xs text-gray-500">
                    {{ task.assignee ? task.assignee.name : 'Sin responsable' }}
                    ·
                    {{ task.sprint ? task.sprint.name : 'Aislada' }}
                </div>
            </div>
            <span class="flex shrink-0 items-center gap-3">
                <span class="flex gap-2 text-xs">
                    <span class="rounded bg-gray-100 px-2 py-0.5">{{
                        task.typeLabel
                    }}</span>
                    <span class="rounded bg-gray-100 px-2 py-0.5">{{
                        task.priorityLabel
                    }}</span>
                    <span class="rounded bg-gray-100 px-2 py-0.5">{{
                        task.statusLabel
                    }}</span>
                </span>
                <button
                    v-if="editable"
                    type="button"
                    class="text-xs text-gray-600 hover:underline"
                    @click="emit('edit', task)"
                >
                    Editar
                </button>
                <button
                    type="button"
                    class="text-xs text-gray-600 hover:underline"
                    @click="emit('comment', task)"
                >
                    Comentarios ({{ task.comments.length }})
                </button>
                <button
                    v-if="deletable"
                    type="button"
                    class="text-xs text-red-600 hover:underline"
                    @click="emit('remove', task)"
                >
                    Eliminar
                </button>
            </span>
        </li>
        <li v-if="tasks.length === 0" class="text-sm text-gray-500">
            Sin tareas todavía.
        </li>
    </ul>
</template>
