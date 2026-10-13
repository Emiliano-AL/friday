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

const groupTitles: Record<CommandAction['group'], string> = {
    create: 'Crear',
    navigate: 'Ir a',
};

const groups = computed(() =>
    (['create', 'navigate'] as const)
        .map((group) => ({
            key: group,
            title: groupTitles[group],
            items: filtered.value
                .map((action, index) => ({ action, index }))
                .filter(({ action }) => action.group === group),
        }))
        .filter((group) => group.items.length > 0),
);

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
        class="bg-inverse-surface/20 fixed inset-0 z-[60] flex items-start justify-center px-4 pt-24 backdrop-blur-sm"
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
                class="border-surface-container px-space-md gap-space-sm flex items-center border-b"
            >
                <AppIcon
                    name="search"
                    :size="20"
                    class="text-on-surface-variant shrink-0"
                />
                <input
                    ref="input"
                    v-model="query"
                    type="text"
                    placeholder="Busca una acción o página..."
                    aria-label="Buscar comando"
                    class="text-body-lg font-body-lg text-on-surface placeholder:text-on-surface-variant/60 h-14 w-full border-0 bg-transparent p-0 focus:ring-0 focus:outline-none"
                />
            </div>

            <div
                ref="list"
                class="p-space-xs flex max-h-80 flex-col overflow-y-auto"
                role="listbox"
            >
                <section
                    v-for="group in groups"
                    :key="group.key"
                    class="flex flex-col gap-0.5 py-1"
                >
                    <h3
                        class="px-space-md text-label-xs font-label-xs text-on-surface-variant pt-1 pb-1.5"
                    >
                        {{ group.title }}
                    </h3>
                    <button
                        v-for="{ action, index } in group.items"
                        :key="action.label"
                        type="button"
                        role="option"
                        :aria-selected="index === activeIndex"
                        :data-active="index === activeIndex"
                        class="px-space-md text-body-md font-body-md gap-space-sm text-on-surface flex h-10 items-center rounded-lg text-left transition-colors"
                        :class="
                            index === activeIndex ? 'bg-surface-container' : ''
                        "
                        @click="run(action)"
                        @mousemove="activeIndex = index"
                    >
                        <AppIcon
                            :name="action.icon"
                            :size="18"
                            class="shrink-0"
                            :class="
                                index === activeIndex
                                    ? 'text-primary'
                                    : 'text-on-surface-variant'
                            "
                        />
                        <span class="min-w-0 flex-1 truncate">{{
                            action.label
                        }}</span>
                        <kbd
                            v-if="action.shortcut"
                            class="border-outline-variant/60 text-label-xs font-label-xs text-on-surface-variant flex h-5 min-w-5 items-center justify-center rounded border px-1"
                            >{{ action.shortcut }}</kbd
                        >
                    </button>
                </section>
                <p
                    v-if="filtered.length === 0"
                    class="text-body-sm text-on-surface-variant px-space-md py-space-lg text-center"
                >
                    Sin resultados para "{{ query }}"
                </p>
            </div>

            <footer
                class="border-surface-container bg-surface-container-low/50 px-space-md text-label-xs font-label-xs text-on-surface-variant gap-space-md hidden h-9 items-center border-t sm:flex"
            >
                <span class="flex items-center gap-1">
                    <kbd
                        class="border-outline-variant/60 bg-surface-container-lowest flex h-5 min-w-5 items-center justify-center rounded border px-1"
                        >↑</kbd
                    >
                    <kbd
                        class="border-outline-variant/60 bg-surface-container-lowest flex h-5 min-w-5 items-center justify-center rounded border px-1"
                        >↓</kbd
                    >
                    navegar
                </span>
                <span class="flex items-center gap-1">
                    <kbd
                        class="border-outline-variant/60 bg-surface-container-lowest flex h-5 min-w-5 items-center justify-center rounded border px-1"
                        >↵</kbd
                    >
                    abrir
                </span>
                <span class="ml-auto flex items-center gap-1">
                    <kbd
                        class="border-outline-variant/60 bg-surface-container-lowest flex h-5 items-center justify-center rounded border px-1"
                        >esc</kbd
                    >
                    cerrar
                </span>
            </footer>
        </div>
    </div>
</template>
