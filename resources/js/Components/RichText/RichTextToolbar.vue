<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';

const props = defineProps<{
    editor: Editor;
}>();

function setLink(): void {
    const previousUrl = props.editor.getAttributes('link').href as
        | string
        | undefined;
    const url = window.prompt('URL del enlace', previousUrl ?? 'https://');

    if (url === null) {
        return;
    }

    if (url.trim() === '') {
        props.editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .unsetLink()
            .run();

        return;
    }

    props.editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .setLink({ href: url.trim() })
        .run();
}
</script>

<template>
    <div
        class="border-outline-variant/40 flex flex-wrap items-center gap-0.5 border-b px-2 py-1.5"
        role="toolbar"
        aria-label="Formato de texto"
    >
        <button
            type="button"
            :class="[
                editor.isActive('paragraph')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 min-w-7 items-center justify-center rounded-md px-1.5 text-label-xs font-label-xs transition-colors',
            ]"
            title="Párrafo"
            aria-label="Párrafo"
            @mousedown.prevent
            @click="editor.chain().focus().setParagraph().run()"
        >
            P
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('heading', { level: 2 })
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 min-w-7 items-center justify-center rounded-md px-1.5 text-label-xs font-label-xs transition-colors',
            ]"
            title="Título H2"
            aria-label="Título H2"
            @mousedown.prevent
            @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
        >
            H2
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('heading', { level: 3 })
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 min-w-7 items-center justify-center rounded-md px-1.5 text-label-xs font-label-xs transition-colors',
            ]"
            title="Título H3"
            aria-label="Título H3"
            @mousedown.prevent
            @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
        >
            H3
        </button>

        <span class="bg-outline-variant/40 mx-0.5 h-4 w-px shrink-0" />

        <button
            type="button"
            :class="[
                editor.isActive('bold')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Negrita"
            aria-label="Negrita"
            :disabled="!editor.can().chain().focus().toggleBold().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleBold().run()"
        >
            <AppIcon name="format_bold" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('italic')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Cursiva"
            aria-label="Cursiva"
            :disabled="!editor.can().chain().focus().toggleItalic().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleItalic().run()"
        >
            <AppIcon name="format_italic" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('underline')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Subrayado"
            aria-label="Subrayado"
            :disabled="!editor.can().chain().focus().toggleUnderline().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleUnderline().run()"
        >
            <AppIcon name="format_underlined" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('code')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Código en línea"
            aria-label="Código en línea"
            :disabled="!editor.can().chain().focus().toggleCode().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleCode().run()"
        >
            <AppIcon name="code" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('link')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Enlace"
            aria-label="Enlace"
            @mousedown.prevent
            @click="setLink"
        >
            <AppIcon name="link" :size="16" />
        </button>

        <span class="bg-outline-variant/40 mx-0.5 h-4 w-px shrink-0" />

        <button
            type="button"
            :class="[
                editor.isActive('bulletList')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Lista de viñetas"
            aria-label="Lista de viñetas"
            :disabled="!editor.can().chain().focus().toggleBulletList().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleBulletList().run()"
        >
            <AppIcon name="format_list_bulleted" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('orderedList')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Lista numerada"
            aria-label="Lista numerada"
            :disabled="!editor.can().chain().focus().toggleOrderedList().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleOrderedList().run()"
        >
            <AppIcon name="format_list_numbered" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('blockquote')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Cita"
            aria-label="Cita"
            :disabled="!editor.can().chain().focus().toggleBlockquote().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleBlockquote().run()"
        >
            <AppIcon name="format_quote" :size="16" />
        </button>
        <button
            type="button"
            :class="[
                editor.isActive('codeBlock')
                    ? 'bg-surface-container text-on-surface'
                    : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
                'inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors disabled:pointer-events-none disabled:opacity-40',
            ]"
            title="Bloque de código"
            aria-label="Bloque de código"
            :disabled="!editor.can().chain().focus().toggleCodeBlock().run()"
            @mousedown.prevent
            @click="editor.chain().focus().toggleCodeBlock().run()"
        >
            <AppIcon name="code_blocks" :size="16" />
        </button>

        <span class="bg-outline-variant/40 mx-0.5 h-4 w-px shrink-0" />

        <button
            type="button"
            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-on-surface-variant transition-colors hover:bg-surface-container hover:text-on-surface disabled:pointer-events-none disabled:opacity-40"
            title="Deshacer"
            aria-label="Deshacer"
            :disabled="!editor.can().chain().focus().undo().run()"
            @mousedown.prevent
            @click="editor.chain().focus().undo().run()"
        >
            <AppIcon name="undo" :size="16" />
        </button>
        <button
            type="button"
            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-on-surface-variant transition-colors hover:bg-surface-container hover:text-on-surface disabled:pointer-events-none disabled:opacity-40"
            title="Rehacer"
            aria-label="Rehacer"
            :disabled="!editor.can().chain().focus().redo().run()"
            @mousedown.prevent
            @click="editor.chain().focus().redo().run()"
        >
            <AppIcon name="redo" :size="16" />
        </button>
    </div>
</template>
