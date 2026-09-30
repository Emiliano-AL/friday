<script setup lang="ts">
import EditorJS, { type OutputData } from '@editorjs/editorjs';
import Code from '@editorjs/code';
import Header from '@editorjs/header';
import InlineCode from '@editorjs/inline-code';
import List from '@editorjs/list';
import Marker from '@editorjs/marker';
import Quote from '@editorjs/quote';
import {
    blocksToPlainText,
    normalizeToBlocks,
    type EditorBlock,
} from '@/utils/blocks';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        placeholder?: string;
        error?: string;
        id?: string;
    }>(),
    {
        placeholder: 'Escribe la descripción…',
    },
);

const holder = ref<HTMLDivElement | null>(null);

let editor: EditorJS | null = null;
const empty = ref(true);

function refreshEmpty(blocks: EditorBlock[]): boolean {
    return blocksToPlainText({ blocks }).trim() === '';
}

function domLooksEmpty(): boolean {
    const root = holder.value;

    if (!root) {
        return true;
    }

    const blocks = root.querySelectorAll('.ce-block');

    if (blocks.length !== 1) {
        return false;
    }

    return (blocks[0].textContent ?? '').trim() === '';
}

async function persistEmpty(): Promise<void> {
    if (!editor) {
        return;
    }

    const data = await editor.save();

    empty.value = refreshEmpty(data.blocks as EditorBlock[]);
}

onMounted(async () => {
    empty.value = refreshEmpty(normalizeToBlocks(props.modelValue).blocks);

    if (!holder.value) {
        return;
    }

    editor = new EditorJS({
        holder: holder.value,
        data: normalizeToBlocks(props.modelValue) as unknown as OutputData,
        placeholder: props.placeholder,
        minHeight: 120,
        tools: {
            header: {
                class: Header,
                config: { levels: [2, 3, 4], defaultLevel: 2 },
            },
            list: { class: List, inlineToolbar: true },
            quote: { class: Quote, inlineToolbar: true },
            code: Code,
            inlineCode: { class: InlineCode },
            marker: { class: Marker },
        },
        inlineToolbar: ['bold', 'italic', 'link', 'marker', 'inlineCode'],
        onChange: () => {
            if (domLooksEmpty()) {
                empty.value = true;

                return;
            }

            void persistEmpty();
        },
    });

    await editor.isReady;
});

onBeforeUnmount(() => {
    if (editor) {
        void editor.destroy();
        editor = null;
    }
});

async function save(): Promise<string> {
    if (!editor || domLooksEmpty()) {
        return '';
    }

    const data = await editor.save();
    const blocks = data.blocks as EditorBlock[];

    if (refreshEmpty(blocks)) {
        return '';
    }

    return JSON.stringify(data);
}

async function render(value: string | null): Promise<void> {
    if (!editor) {
        return;
    }

    await editor.isReady;

    await editor.blocks.render(
        normalizeToBlocks(value) as unknown as OutputData,
    );

    empty.value = refreshEmpty(normalizeToBlocks(value).blocks);
}

function isEmpty(): boolean {
    return empty.value;
}

function focus(): void {
    if (editor) {
        editor.focus();
    }
}

defineExpose({ save, render, isEmpty, focus });
</script>

<template>
    <div>
        <div
            :id="id"
            ref="holder"
            :class="[
                error ? 'border-error' : 'border-outline-variant/40',
                'bg-surface-container-lowest focus-within:ring-primary/20 rounded-lg border shadow-xs focus-within:ring-2',
            ]"
            class="rich-text-editor"
        />
        <p v-if="error" class="text-label-xs font-label-xs text-error mt-1">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
.rich-text-editor :deep(.ce-block__content),
.rich-text-editor :deep(.ce-toolbar__content) {
    max-width: none;
}

.rich-text-editor :deep(.ce-paragraph),
.rich-text-editor :deep(.cdx-block),
.rich-text-editor :deep(.ce-header) {
    color: var(--color-on-surface);
    font-family: var(--font-inter), Inter, sans-serif;
}

.rich-text-editor :deep(.ce-paragraph),
.rich-text-editor :deep(.cdx-block) {
    font-size: 14px;
    line-height: 20px;
}

.rich-text-editor :deep(.ce-header) {
    font-weight: 600;
    letter-spacing: -0.01em;
}

.rich-text-editor :deep(.ce-header[data-level='2']) {
    font-size: 16px;
    line-height: 24px;
}

.rich-text-editor :deep(.ce-header[data-level='3']),
.rich-text-editor :deep(.ce-header[data-level='4']) {
    font-size: 14px;
    line-height: 20px;
}

.rich-text-editor :deep(.ce-toolbar__plus),
.rich-text-editor :deep(.ce-toolbar__settings-btn),
.rich-text-editor :deep(.ce-inline-toolbar__dropdown),
.rich-text-editor :deep(.ce-inline-tool),
.rich-text-editor :deep(.ce-conversion-toolbar__label),
.rich-text-editor :deep(.ce-toolbar__settings-btn) {
    color: var(--color-on-surface-variant);
}

.rich-text-editor :deep(.ce-inline-tool:hover),
.rich-text-editor :deep(.ce-toolbar__plus:hover),
.rich-text-editor :deep(.ce-toolbar__settings-btn:hover),
.rich-text-editor :deep(.ce-inline-tool--active) {
    background-color: var(--color-surface-container);
    color: var(--color-on-surface);
}

.rich-text-editor :deep(.ce-popover),
.rich-text-editor :deep(.ce-inline-toolbar),
.rich-text-editor :deep(.ce-conversion-toolbar) {
    background-color: var(--color-surface-container-lowest);
    border-color: var(--color-outline-variant);
    border-radius: 0.5rem;
}

.rich-text-editor :deep(.ce-popover__item:hover),
.rich-text-editor :deep(.ce-conversion-tool:hover) {
    background-color: var(--color-surface-container);
}

.rich-text-editor :deep(.ce-popover__item-label),
.rich-text-editor :deep(.ce-conversion-tool__label) {
    color: var(--color-on-surface);
}

.rich-text-editor :deep(.ce-block--selected .ce-block__content) {
    background-color: var(--color-surface-container);
}

.rich-text-editor :deep(a) {
    color: var(--color-primary);
    text-decoration: underline;
}

.rich-text-editor :deep(pre) {
    background-color: var(--color-surface-container-low);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
}

.rich-text-editor :deep(blockquote) {
    border-left: 2px solid var(--color-outline-variant);
    padding-left: 0.75rem;
    color: var(--color-on-surface-variant);
}
</style>
