<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { TaskStatusValue } from '@/types/task';

const props = withDefaults(
    defineProps<{
        title: string;
        status: TaskStatusValue | 'blocked';
        count: number;
        dotClass: string;
        disabled?: boolean;
        dragOver?: boolean;
        dropTarget?: boolean;
        quickAdd?: boolean;
        collapsible?: boolean;
        collapsed?: boolean;
        landed?: boolean;
        wipLimit?: boolean;
    }>(),
    {
        disabled: false,
        dragOver: false,
        dropTarget: false,
        quickAdd: true,
        collapsible: false,
        collapsed: false,
        landed: false,
        wipLimit: false,
    },
);

const emit = defineEmits<{
    drop: [status: TaskStatusValue];
    dragenter: [status: TaskStatusValue];
    dragleave: [status: TaskStatusValue];
    add: [status: TaskStatusValue];
    toggle: [];
}>();

/**
 * Ignore dragleave fired while the pointer only crosses into a child card.
 */
function onDragLeave(event: DragEvent): void {
    const column = event.currentTarget as HTMLElement;

    if (!column.contains(event.relatedTarget as Node | null)) {
        emit('dragleave', props.status as TaskStatusValue);
    }
}
</script>

<template>
    <section
        class="p-space-xs flex flex-col rounded-xl outline-2 -outline-offset-2 transition-[background-color,outline-color] duration-150"
        :class="[
            collapsed ? 'w-14 flex-none' : 'min-w-[260px] flex-1 basis-0',
            landed ? 'task-landed' : '',
            disabled
                ? 'bg-surface-container-low/40 opacity-70'
                : dragOver
                  ? 'bg-primary/[0.07] outline-primary/60 outline-solid'
                  : 'bg-surface-container-low/70',
            dropTarget && !dragOver
                ? 'outline-outline-variant outline-dashed'
                : '',
            !dropTarget && !dragOver ? 'outline-transparent' : '',
        ]"
        @dragover.prevent="
            disabled || !dropTarget
                ? null
                : emit('dragenter', status as TaskStatusValue)
        "
        @dragleave="disabled ? null : onDragLeave($event)"
        @drop.prevent="
            disabled ? null : emit('drop', status as TaskStatusValue)
        "
    >
        <button
            v-if="collapsed"
            type="button"
            class="hover:bg-surface-container group gap-space-sm py-space-sm flex min-h-80 flex-1 flex-col items-center rounded-lg transition-colors"
            :aria-label="`Mostrar ${title} (${count})`"
            :title="`Mostrar ${title}`"
            :aria-expanded="false"
            @click="emit('toggle')"
        >
            <AppIcon
                name="right_panel_open"
                :size="18"
                class="text-on-surface-variant group-hover:text-on-surface"
            />
            <span
                class="h-2.5 w-2.5 shrink-0 rounded-full"
                :class="dotClass"
                aria-hidden="true"
            />
            <UiBadge :label="String(count)" tone="neutral" />
            <span
                class="text-label-md font-label-md text-on-surface font-semibold [writing-mode:vertical-rl]"
            >
                {{ title }}
            </span>
        </button>

        <template v-else>
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
                        class="text-label-md font-label-md font-semibold"
                        :class="disabled ? 'text-error' : 'text-on-surface'"
                    >
                        {{ title }}
                    </span>
                    <UiBadge :label="String(count)" tone="neutral" />
                </div>

                <div class="gap-space-2xs flex items-center">
                    <UiBadge
                        v-if="disabled"
                        label="Próximamente"
                        tone="outline"
                    />
                    <button
                        v-if="collapsible"
                        type="button"
                        :title="`Contraer ${title}`"
                        :aria-label="`Contraer ${title}`"
                        :aria-expanded="true"
                        class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-6 w-6 items-center justify-center rounded-lg transition-colors"
                        @click="emit('toggle')"
                    >
                        <AppIcon name="right_panel_close" :size="16" />
                    </button>
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

            <div class="gap-space-sm flex min-h-[60px] flex-col rounded-lg">
                <div
                    v-if="dragOver"
                    class="task-drop-slot border-primary/50 bg-surface-container-lowest/60 text-primary text-label-sm font-label-sm flex h-16 items-center justify-center gap-1.5 rounded-lg border-2 border-dashed"
                    aria-hidden="true"
                >
                    <AppIcon name="south" :size="16" />
                    Soltar en {{ title }}
                </div>
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
        </template>
    </section>
</template>
