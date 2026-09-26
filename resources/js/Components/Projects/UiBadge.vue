<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        label: string;
        tone?: 'primary' | 'neutral' | 'error' | 'tertiary' | 'outline';
        dot?: boolean;
    }>(),
    {
        tone: 'neutral',
        dot: false,
    },
);

const toneClasses = computed(() => {
    switch (props.tone) {
        case 'primary':
            return 'bg-primary/10 text-primary';
        case 'error':
            return 'bg-error-container text-on-error-container';
        case 'tertiary':
            return 'bg-tertiary/10 text-tertiary';
        case 'outline':
            return 'border border-outline-variant text-on-surface-variant bg-transparent';
        default:
            return 'bg-surface-container text-on-surface-variant';
    }
});

const dotClasses = computed(() => {
    switch (props.tone) {
        case 'primary':
            return 'bg-primary';
        case 'error':
            return 'bg-on-error-container';
        case 'tertiary':
            return 'bg-tertiary';
        case 'outline':
            return 'bg-outline-variant';
        default:
            return 'bg-on-surface-variant';
    }
});
</script>

<template>
    <span
        class="text-label-xs font-label-xs inline-flex items-center gap-1 rounded-full px-2 py-0.5"
        :class="toneClasses"
    >
        <span
            v-if="dot"
            class="h-1.5 w-1.5 shrink-0 rounded-full"
            :class="dotClasses"
            aria-hidden="true"
        />
        {{ label }}
    </span>
</template>
