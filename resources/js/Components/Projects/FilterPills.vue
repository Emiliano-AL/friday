<script setup lang="ts">
import { computed } from 'vue';
import type { ProjectCounts } from '@/types/project';

export type ProjectsFilter = 'all' | 'active' | 'completed' | 'archived';

const props = defineProps<{
    counts: ProjectCounts;
    modelValue: ProjectsFilter;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: ProjectsFilter];
}>();

const filters = computed(() => [
    {
        value: 'all' as ProjectsFilter,
        label: 'Todos',
        count:
            props.counts.active +
            props.counts.completed +
            props.counts.archived,
    },
    {
        value: 'active' as ProjectsFilter,
        label: 'Activos',
        count: props.counts.active,
    },
    {
        value: 'completed' as ProjectsFilter,
        label: 'Completados',
        count: props.counts.completed,
    },
    {
        value: 'archived' as ProjectsFilter,
        label: 'Archivados',
        count: props.counts.archived,
    },
]);
</script>

<template>
    <div class="gap-space-xs flex items-center overflow-x-auto pb-1">
        <button
            v-for="filter in filters"
            :key="filter.value"
            type="button"
            :aria-pressed="modelValue === filter.value"
            class="text-label-xs font-label-xs shadow-card rounded-full px-3 py-1 whitespace-nowrap transition-colors"
            :class="
                modelValue === filter.value
                    ? 'bg-surface-container text-primary'
                    : 'bg-surface-container-lowest text-on-surface-variant hover:text-on-surface'
            "
            @click="emit('update:modelValue', filter.value)"
        >
            {{ filter.label }}
            <span>{{ filter.count }}</span>
        </button>
    </div>
</template>
