<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { CommandAction } from '@/types/shell';

const props = defineProps<{
    open: boolean;
    actions: CommandAction[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const query = ref('');
const activeIndex = ref(0);
const input = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (term === '') {
        return props.actions;
    }

    return props.actions.filter(
        (action) =>
            action.label.toLowerCase().includes(term) ||
            action.keywords.some((keyword) =>
                keyword.toLowerCase().includes(term),
            ),
    );
});

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            query.value = '';
            activeIndex.value = 0;
            await nextTick();
            input.value?.focus();
        }
    },
);

watch(filtered, () => {
    activeIndex.value = 0;
});

function scrollActiveIntoView(): void {
    list.value
        ?.querySelector('[data-active="true"]')
        ?.scrollIntoView({ block: 'nearest' });
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');

        return;
    }

    if (filtered.value.length === 0) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % filtered.value.length;
        scrollActiveIntoView();
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value =
            (activeIndex.value - 1 + filtered.value.length) %
            filtered.value.length;
        scrollActiveIntoView();
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        run(filtered.value[activeIndex.value]);
    }
}

function run(action: CommandAction | undefined): void {
    if (!action) {
        return;
    }

    emit('close');
    action.run();
}
</script>

<template>
    <div
        v-if="open"
        class="bg-inverse-surface/20 fixed inset-0 z-[60] flex items-start justify-center pt-24 backdrop-blur-sm"
        role="presentation"
        @click.self="emit('close')"
        @keydown="onKeydown"
    >
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Paleta de comandos"
            class="bg-surface-container-lowest shadow-modal w-full max-w-xl overflow-hidden rounded-xl"
        >
            <div
                class="bg-surface-container-low px-space-md py-space-md flex items-center"
            >
                <AppIcon
                    name="search"
                    :size="20"
                    class="text-primary-container mr-space-sm"
                />
                <input
                    ref="input"
                    v-model="query"
                    type="text"
                    placeholder="Escribe un comando o busca tareas..."
                    class="text-body-md text-on-surface placeholder:text-on-surface-variant w-full bg-transparent outline-none"
                />
                <kbd
                    class="bg-surface text-label-xs text-on-surface-variant rounded px-1.5 py-0.5"
                    >ESC</kbd
                >
            </div>

            <div
                ref="list"
                class="p-space-xs flex max-h-80 flex-col gap-0.5 overflow-y-auto"
            >
                <div
                    class="px-space-md py-space-xs text-label-xs text-on-surface-variant tracking-wider uppercase"
                >
                    Acciones sugeridas
                </div>
                <button
                    v-for="(action, index) in filtered"
                    :key="action.label"
                    type="button"
                    :data-active="index === activeIndex"
                    class="px-space-md py-space-sm text-label-md flex items-center justify-between rounded-lg text-left transition-colors"
                    :class="
                        index === activeIndex
                            ? 'bg-surface-container text-on-surface'
                            : 'text-on-surface hover:bg-surface-container'
                    "
                    @click="run(action)"
                    @mousemove="activeIndex = index"
                >
                    <span class="gap-space-sm flex items-center">
                        <AppIcon
                            :name="action.icon"
                            :class="
                                index === activeIndex
                                    ? 'text-primary'
                                    : 'text-on-surface-variant'
                            "
                        />
                        {{ action.label }}
                    </span>
                    <kbd
                        v-if="action.shortcut"
                        class="bg-surface-container-high text-label-xs text-on-surface-variant rounded px-1.5 py-0.5"
                        >{{ action.shortcut }}</kbd
                    >
                </button>
                <div
                    v-if="filtered.length === 0"
                    class="text-body-sm text-on-surface-variant px-space-md py-space-lg text-center"
                >
                    Sin resultados para "{{ query }}"
                </div>
            </div>
        </div>
    </div>
</template>
