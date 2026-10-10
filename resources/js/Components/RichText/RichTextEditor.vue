<script setup lang="ts">
import { Placeholder } from '@tiptap/extensions';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';
import RichTextToolbar from '@/Components/RichText/RichTextToolbar.vue';
import createStarterKit from '@/Components/RichText/starterKit';
import { normalizeToTiptap } from '@/utils/tiptapContent';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        placeholder?: string;
        error?: string;
        id?: string;
        minHeight?: number;
        maxHeightClass?: string;
    }>(),
    {
        placeholder: 'Escribe la descripción…',
        minHeight: 120,
        maxHeightClass: '',
    },
);

const editor = useEditor({
    editable: true,
    extensions: [
        createStarterKit(),
        Placeholder.configure({
            placeholder: () => props.placeholder,
        }),
    ],
    content: normalizeToTiptap(props.modelValue),
});

watch(
    () => props.modelValue,
    (value) => {
        editor.value?.commands.setContent(normalizeToTiptap(value), {
            emitUpdate: false,
        });
    },
);

async function save(): Promise<string> {
    if (!editor.value || editor.value.isEmpty) {
        return '';
    }

    return JSON.stringify(editor.value.getJSON());
}

function focus(): void {
    editor.value?.commands.focus();
}

defineExpose({ save, focus });
</script>

<template>
    <div>
        <div
            :id="id"
            :class="[
                error ? 'border-error' : 'border-outline-variant/40',
                maxHeightClass,
                { 'rich-text-editor--scroll': maxHeightClass !== '' },
                'rich-text-editor bg-surface-container-lowest focus-within:ring-primary/20 flex flex-col rounded-lg border shadow-xs focus-within:ring-2',
            ]"
            :style="{ '--rte-min-height': `${minHeight}px` }"
        >
            <RichTextToolbar v-if="editor" :editor="editor" />
            <editor-content
                v-if="editor"
                :editor="editor"
                class="rich-text-editor__content min-h-0 flex-1"
            />
        </div>
        <p v-if="error" class="text-label-xs font-label-xs text-error mt-1">
            {{ error }}
        </p>
    </div>
</template>

<style scoped>
.rich-text-editor--scroll .rich-text-editor__content {
    overflow-y: auto;
}

.rich-text-editor__content :deep(.tiptap) {
    min-height: var(--rte-min-height);
    outline: none;
    padding: 0.75rem;
    color: var(--color-on-surface);
    font-family: var(--font-inter), Inter, sans-serif;
    font-size: 14px;
    line-height: 20px;
}

.rich-text-editor__content :deep(.tiptap > * + *) {
    margin-top: 0.5rem;
}

.rich-text-editor__content :deep(.tiptap p.is-editor-empty:first-child::before) {
    color: var(--color-on-surface-variant);
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.rich-text-editor__content :deep(.tiptap h2) {
    font-size: 16px;
    font-weight: 600;
    letter-spacing: -0.01em;
    line-height: 24px;
}

.rich-text-editor__content :deep(.tiptap h3) {
    font-size: 14px;
    font-weight: 600;
    letter-spacing: -0.01em;
    line-height: 20px;
}

.rich-text-editor__content :deep(.tiptap ul) {
    list-style: disc;
    padding-left: 1.25rem;
}

.rich-text-editor__content :deep(.tiptap ol) {
    list-style: decimal;
    padding-left: 1.25rem;
}

.rich-text-editor__content :deep(.tiptap blockquote) {
    border-left: 2px solid var(--color-outline-variant);
    color: var(--color-on-surface-variant);
    padding-left: 0.75rem;
}

.rich-text-editor__content :deep(.tiptap pre) {
    background-color: var(--color-surface-container-low);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
}

.rich-text-editor__content :deep(.tiptap code) {
    background-color: var(--color-surface-container-low);
    border-radius: 0.25rem;
    font-size: 13px;
    padding: 0.125rem 0.25rem;
}

.rich-text-editor__content :deep(.tiptap pre code) {
    background-color: transparent;
    padding: 0;
}

.rich-text-editor__content :deep(.tiptap a) {
    color: var(--color-primary);
    text-decoration: underline;
}
</style>
