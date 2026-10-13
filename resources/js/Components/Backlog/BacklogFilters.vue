<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { BacklogFilter, BacklogSummary } from '@/types/backlog';

const props = defineProps<{
    filter: BacklogFilter;
    summary: BacklogSummary;
}>();

const emit = defineEmits<{
    'update:filter': [value: BacklogFilter];
}>();

const options: {
    value: BacklogFilter;
    label: string;
    icon: string;
    count: () => number;
}[] = [
    { value: 'all', label: 'Todas', icon: 'inbox', count: () => props.summary.total },
    {
        value: 'without_project',
        label: 'Sin proyecto',
        icon: 'folder_off',
        count: () => props.summary.withoutProject,
    },
    {
        value: 'with_project',
        label: 'Con proyecto',
        icon: 'folder_open',
        count: () => props.summary.withProject,
    },
];
</script>

<template>
    <div class="gap-space-xs flex flex-wrap items-center">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :aria-pressed="filter === option.value"
            class="text-label-sm font-label-sm flex items-center gap-1.5 rounded-lg px-3 py-1.5 whitespace-nowrap transition-colors"
            :class="
                filter === option.value
                    ? 'bg-primary-fixed text-on-primary-fixed font-medium'
                    : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
            "
            @click="emit('update:filter', option.value)"
        >
            <AppIcon :name="option.icon" :size="14" />
            {{ option.label }}
            <UiBadge :label="String(option.count())" tone="neutral" />
        </button>
    </div>
</template>
