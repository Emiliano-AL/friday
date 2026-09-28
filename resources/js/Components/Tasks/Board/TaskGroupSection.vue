<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';

defineProps<{
    title: string;
    icon: string;
    count: number;
    hint?: string;
}>();
</script>

<template>
    <section
        class="bg-surface-container-lowest shadow-card flex flex-col overflow-hidden rounded-xl"
    >
        <header
            class="bg-surface-container-low/70 px-space-md py-space-sm flex items-center justify-between"
        >
            <div class="flex items-center gap-2">
                <AppIcon
                    :name="icon"
                    :size="16"
                    class="text-on-surface-variant"
                />
                <span
                    class="text-label-sm font-label-sm text-on-surface font-semibold"
                >
                    {{ title }}
                </span>
                <UiBadge :label="String(count)" tone="neutral" />
            </div>
            <div v-if="hint || $slots.actions" class="flex items-center gap-2">
                <span v-if="hint" class="text-label-xs text-on-surface-variant">
                    {{ hint }}
                </span>
                <slot name="actions" />
            </div>
        </header>
        <div class="flex flex-col">
            <slot />
        </div>
    </section>
</template>
