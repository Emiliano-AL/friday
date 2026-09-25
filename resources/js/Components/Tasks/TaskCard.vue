<script setup lang="ts">
import type { TaskItem } from './types';

defineProps<{
    task: TaskItem;
    draggable: boolean;
}>();

const emit = defineEmits<{
    dragstart: [event: DragEvent, task: TaskItem];
}>();
</script>

<template>
    <div
        class="rounded-md border border-gray-200 bg-white p-2 shadow-sm"
        :class="{
            'cursor-grab active:cursor-grabbing': draggable,
            'opacity-80': !draggable,
        }"
        :draggable="draggable"
        :data-task-id="task.id"
        @dragstart="emit('dragstart', $event, task)"
    >
        <div class="text-sm text-gray-900">{{ task.title }}</div>
        <div
            class="mt-1 flex items-center justify-between text-xs text-gray-500"
        >
            <span>{{ task.priorityLabel }}</span>
            <span>{{
                task.assignee ? task.assignee.name : 'Sin responsable'
            }}</span>
        </div>
        <div class="mt-1 text-xs text-gray-500">
            {{ task.sprint ? task.sprint.name : 'Sin sprint' }}
        </div>
    </div>
</template>
