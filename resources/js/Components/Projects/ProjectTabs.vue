<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import { ref } from 'vue';

export type ProjectTabValue = 'general' | 'sprints';

const props = defineProps<{
    modelValue: ProjectTabValue;
    taskCount: number;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: ProjectTabValue];
}>();

interface ProjectTab {
    value: ProjectTabValue | 'documentation' | 'resources' | 'history';
    label: string;
    icon: string;
    disabled?: boolean;
}

const tabs: ProjectTab[] = [
    {
        value: 'general',
        label: 'General y resumen',
        icon: 'dashboard_customize',
    },
    { value: 'sprints', label: 'Sprints y tareas', icon: 'sprint' },
    {
        value: 'documentation',
        label: 'Documentación',
        icon: 'article',
        disabled: true,
    },
    {
        value: 'resources',
        label: 'Recursos y enlaces',
        icon: 'link',
        disabled: true,
    },
    { value: 'history', label: 'Historial', icon: 'history', disabled: true },
];

const root = ref<HTMLElement | null>(null);

function activate(tab: ProjectTab): void {
    if (tab.disabled) {
        return;
    }

    if (tab.value !== 'general' && tab.value !== 'sprints') {
        return;
    }

    emit('update:modelValue', tab.value);
}

function tabIndex(tab: ProjectTab): number {
    return tab.disabled || tab.value !== props.modelValue ? -1 : 0;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
        return;
    }

    const buttons = Array.from(
        root.value?.querySelectorAll<HTMLButtonElement>('[role="tab"]') ?? [],
    );

    if (buttons.length === 0) {
        return;
    }

    const current = buttons.indexOf(
        document.activeElement as HTMLButtonElement,
    );

    if (current === -1) {
        return;
    }

    event.preventDefault();

    const delta = event.key === 'ArrowRight' ? 1 : -1;
    const next = (current + delta + buttons.length) % buttons.length;

    buttons[next].focus();
}
</script>

<template>
    <div
        ref="root"
        role="tablist"
        aria-label="Secciones del proyecto"
        class="bg-surface-container-low shadow-card gap-space-xs flex items-center overflow-x-auto rounded-xl p-1"
        @keydown="onKeydown"
    >
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            :tabindex="tabIndex(tab)"
            :aria-selected="tab.value === modelValue"
            :aria-disabled="tab.disabled || undefined"
            :title="tab.disabled ? 'Disponible próximamente' : undefined"
            class="text-label-md font-label-md gap-space-xs px-space-md flex shrink-0 items-center rounded-lg py-2 transition-colors"
            :class="
                tab.disabled
                    ? 'text-on-surface-variant cursor-not-allowed opacity-60'
                    : tab.value === modelValue
                      ? 'bg-surface-container-lowest text-primary shadow-card'
                      : 'text-on-surface-variant hover:bg-surface-container-lowest/60 hover:text-on-surface'
            "
            @click="activate(tab)"
        >
            <AppIcon :name="tab.icon" :size="18" />
            <span>{{ tab.label }}</span>
            <span
                v-if="tab.value === 'sprints'"
                class="bg-surface-container text-on-surface font-label-xs text-label-xs rounded-full px-1.5 py-0.5"
            >
                {{ taskCount }}
            </span>
            <UiBadge v-if="tab.disabled" label="Próximamente" tone="outline" />
        </button>
    </div>
</template>
