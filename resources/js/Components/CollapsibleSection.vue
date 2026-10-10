<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { useId } from 'vue';

const open = defineModel<boolean>('open', { default: false });

const props = withDefaults(
    defineProps<{
        title: string;
        icon: string;
        toggleHint?: boolean;
    }>(),
    {
        toggleHint: true,
    },
);

const contentId = useId();
</script>

<template>
    <section
        class="border-outline-variant/30 bg-surface-container-low/80 rounded-xl border"
    >
        <button
            type="button"
            class="group p-space-md flex w-full items-center justify-between text-left"
            :aria-expanded="open"
            :aria-controls="contentId"
            @click="open = !open"
        >
            <div class="flex items-center gap-2">
                <AppIcon
                    :name="icon"
                    :size="16"
                    class="text-on-surface-variant group-hover:text-primary transition-colors"
                />
                <span
                    class="text-label-xs font-label-xs text-on-surface-variant group-hover:text-on-surface font-semibold tracking-wider uppercase transition-colors"
                >
                    {{ title }}
                </span>
                <slot name="badge" />
            </div>
            <div
                class="text-on-surface-variant flex items-center gap-1.5 transition-colors group-hover:text-on-surface"
            >
                <span v-if="toggleHint" class="text-label-xs font-label-xs">{{
                    open ? 'Ocultar' : 'Mostrar'
                }}</span>
                <AppIcon
                    name="expand_more"
                    :size="16"
                    class="transition-transform duration-200"
                    :class="open ? 'rotate-180' : ''"
                />
            </div>
        </button>
        <div
            :id="contentId"
            class="grid transition-all duration-300 ease-out motion-reduce:transition-none"
            :class="
                open ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'
            "
        >
            <div class="overflow-hidden">
                <div class="px-space-md pb-space-md" :inert="!open">
                    <slot />
                </div>
            </div>
        </div>
    </section>
</template>
