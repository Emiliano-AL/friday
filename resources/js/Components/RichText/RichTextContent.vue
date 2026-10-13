<script setup lang="ts">
import { computed, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';

import createStarterKit from './starterKit';
import { normalizeToTiptap } from '@/utils/tiptapContent';

const props = defineProps<{
    content: string | null;
}>();

const doc = computed(() => normalizeToTiptap(props.content));

const isEmpty = computed(() => doc.value.content.length === 0);

const editor = useEditor({
    editable: false,
    extensions: [createStarterKit()],
    content: doc.value,
});

watch(
    () => props.content,
    (value) => {
        editor.value?.commands.setContent(normalizeToTiptap(value), {
            emitUpdate: false,
        });
    },
);
</script>

<template>
    <div
        v-if="!isEmpty"
        class="text-body-sm font-body-sm text-on-surface rich-text-content [&_h2]:text-headline-sm [&_h2]:font-headline-sm [&_h3]:text-body-md [&_h3]:font-semibold [&_blockquote]:border-outline-variant [&_blockquote]:text-on-surface-variant [&_pre]:bg-surface-container-low [&_code]:text-body-sm [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:pl-3 [&_blockquote]:italic [&_li]:ml-4 [&_ol]:list-decimal [&_p]:mb-2 [&_pre]:rounded-lg [&_pre]:p-2 [&_ul]:list-disc"
    >
        <editor-content v-if="editor" :editor="editor" />
    </div>
</template>
