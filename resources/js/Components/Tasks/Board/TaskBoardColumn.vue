<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { TaskStatusValue } from '@/types/task';

withDefaults(
    defineProps<{
        title: string;
        status: TaskStatusValue | 'blocked';
        count: number;
        dotClass: string;
        disabled?: boolean;
        dragOver?: boolean;
        quickAdd?: boolean;
        wipLimit?: boolean;
    }>(),
    {
        disabled: false,
        dragOver: false,
        quickAdd: true,
        wipLimit: false,
    },
);

const emit = defineEmits<{
    drop: [status: TaskStatusValue];
    dragenter: [status: TaskStatusValue];
    dragleave: [status: TaskStatusValue];
    add: [status: TaskStatusValue];
}>();
</script>

<template>
    <section
        class="p-space-xs shadow-card flex max-w-[310px] min-w-[300px] flex-shrink-0 flex-col rounded-xl"
        :class="
            disabled
                ? 'bg-surface-container-low/40 opacity-70'
                : 'bg-surface-container-low/70'
        "
    >
        <header
            class="px-space-sm py-space-xs flex items-center justify-between"
        >
            <div class="flex min-w-0 items-center gap-2">
                <span
                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                    :class="dotClass"
                    aria-hidden="true"
                />
                <span
                    class="text-headline-sm font-headline-sm"
                    :class="
                        disabled
                            ? 'text-error font-semibold'
                            : 'text-on-surface font-semibold'
                    "
                >
                    {{ title }}
                </span>
                <UiBadge :label="String(count)" tone="neutral" />
            </div>

            <div class="gap-space-2xs flex items-center">
                <span
                    v-if="wipLimit"
                    class="font-label-xs text-label-xs text-on-surface-variant bg-surface-container-high px-space-xs py-space-2xs rounded font-mono"
                    >WIP</span
                >
                <UiBadge
                    v-if="wipLimit || disabled"
                    label="Próximamente"
                    tone="outline"
                />
                <button
                    v-if="!disabled"
                    type="button"
                    title="Añadir tarea"
                    class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-6 w-6 items-center justify-center rounded-lg transition-colors"
                    @click="emit('add', status as TaskStatusValue)"
                >
                    <AppIcon name="add" :size="16" />
                </button>
            </div>
        </header>

        <div
            class="gap-space-sm flex min-h-[60px] flex-col rounded-lg transition-colors"
            :class="{ 'bg-primary/5 ring-primary/30 ring-2': dragOver }"
            @dragover.prevent
            @dragenter="
                disabled ? null : emit('dragenter', status as TaskStatusValue)
            "
            @dragleave="
                disabled ? null : emit('dragleave', status as TaskStatusValue)
            "
            @drop="disabled ? null : emit('drop', status as TaskStatusValue)"
        >
            <slot />
        </div>

        <button
            v-if="!disabled && quickAdd"
            type="button"
            class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface mt-space-xs flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 transition-colors"
            @click="emit('add', status as TaskStatusValue)"
        >
            <AppIcon name="add" :size="16" />
            Añadir tarea rápida
        </button>
    </section>
</template>
