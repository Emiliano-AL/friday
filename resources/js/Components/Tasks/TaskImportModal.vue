<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import {
    preview as projectPreviewRoute,
    store as projectStoreRoute,
} from '@/routes/projects/tasks/import';
import {
    preview as previewRoute,
    store as storeRoute,
} from '@/routes/tasks/import';
import type { ImportPreview, ImportPreviewTask } from '@/types/task';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    projectId: number | null;
    destinationLabel: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage<{
    flash: { importPreview?: ImportPreview | null };
    errors: Record<string, string>;
}>();

const content = ref('');
const fileName = ref<string | null>(null);
const preview = ref<ImportPreview | null>(null);
const processing = ref(false);

const error = computed(() => page.props.errors?.content ?? null);

const withErrors = computed(
    () =>
        preview.value?.tasks.filter((task) => task.state === 'error').length ??
        0,
);

const skipped = computed(
    () =>
        preview.value?.tasks.filter(
            (task) => task.state === 'duplicate' || task.state === 'repeated',
        ).length ?? 0,
);

watch(
    () => props.open,
    (open) => {
        if (open) {
            content.value = '';
            fileName.value = null;
            preview.value = null;
        }
    },
);

async function onFileChange(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    fileName.value = file.name;
    content.value = await file.text();
}

function urls(): { preview: string; store: string } {
    return props.projectId !== null
        ? {
              preview: projectPreviewRoute.url(props.projectId),
              store: projectStoreRoute.url(props.projectId),
          }
        : { preview: previewRoute.url(), store: storeRoute.url() };
}

function review(): void {
    router.post(
        urls().preview,
        { content: content.value },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => {
                preview.value = page.props.flash.importPreview ?? null;
            },
        },
    );
}

function confirm(): void {
    router.post(
        urls().store,
        { content: content.value },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
            onSuccess: () => {
                if (!page.props.errors?.content) {
                    emit('close');
                }
            },
        },
    );
}

const stateStyles: Record<
    ImportPreviewTask['state'],
    { icon: string; classes: string; label: string }
> = {
    ok: { icon: 'check_circle', classes: 'text-primary', label: 'Lista' },
    warning: {
        icon: 'warning',
        classes: 'text-tertiary',
        label: 'Con aviso',
    },
    error: {
        icon: 'error',
        classes: 'text-error',
        label: 'No se importará',
    },
    duplicate: {
        icon: 'content_copy',
        classes: 'text-on-surface-variant',
        label: 'Ya existe',
    },
    repeated: {
        icon: 'content_copy',
        classes: 'text-on-surface-variant',
        label: 'Repetida',
    },
};
</script>

<template>
    <Modal :show="open" max-width="2xl" @close="emit('close')">
        <div class="flex max-h-[85vh] flex-col">
            <div
                class="px-space-lg pt-space-lg pb-space-sm gap-space-md flex items-start"
            >
                <div class="min-w-0 flex-1">
                    <h3
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        Importar tareas
                    </h3>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant"
                    >
                        Destino:
                        <span class="text-on-surface font-medium">{{
                            destinationLabel
                        }}</span>
                        · se crean como pendientes del backlog.
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Cerrar"
                    class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container -mt-1 -mr-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors"
                    @click="emit('close')"
                >
                    <AppIcon name="close" :size="20" />
                </button>
            </div>

            <div
                v-if="!preview"
                class="gap-space-md px-space-lg py-space-md flex flex-col"
            >
                <label
                    class="border-outline-variant hover:border-primary/60 hover:bg-primary/[0.03] gap-space-xs p-space-lg flex cursor-pointer flex-col items-center rounded-xl border-2 border-dashed text-center transition-colors"
                >
                    <AppIcon
                        name="upload_file"
                        :size="28"
                        class="text-primary"
                    />
                    <span class="text-label-md font-label-md text-on-surface">
                        {{ fileName ?? 'Elige un archivo .md' }}
                    </span>
                    <span class="text-label-sm text-on-surface-variant">
                        Formato de Tareas: secciones por prioridad y tareas con
                        Tipo, Prioridad, Responsable y Descripción.
                    </span>
                    <input
                        type="file"
                        accept=".md,.markdown,text/markdown,text/plain"
                        class="sr-only"
                        @change="onFileChange"
                    />
                </label>

                <div class="flex flex-col gap-1.5">
                    <label
                        for="import-content"
                        class="text-label-sm font-label-sm text-on-surface"
                    >
                        o pega el contenido
                    </label>
                    <textarea
                        id="import-content"
                        v-model="content"
                        rows="6"
                        placeholder="## Prioridad Alta&#10;&#10;### Nombre de la tarea&#10;- **Tipo:** Bug&#10;..."
                        class="border-outline-variant/60 bg-surface-container-lowest text-body-sm text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-primary/20 w-full rounded-lg border px-3 py-2 font-mono transition-colors focus:ring-2 focus:outline-none"
                    />
                </div>

                <p
                    v-if="error"
                    class="text-label-sm font-label-sm text-error"
                    role="alert"
                >
                    {{ error }}
                </p>
            </div>

            <div
                v-else
                class="gap-space-md px-space-lg py-space-md flex min-h-0 flex-1 flex-col overflow-y-auto"
            >
                <p class="text-body-sm font-body-sm text-on-surface">
                    <span class="font-semibold"
                        >{{ preview.counts.importable }} de
                        {{ preview.counts.total }}</span
                    >
                    tareas listas para importar.
                    <span v-if="withErrors > 0" class="text-error">
                        {{ withErrors }} con error no se importarán.
                    </span>
                    <span v-if="skipped > 0" class="text-on-surface-variant">
                        {{ skipped }} se omiten por estar repetidas o existir
                        ya.
                    </span>
                </p>

                <ul
                    class="border-surface-container flex flex-col rounded-lg border"
                >
                    <li
                        v-for="task in preview.tasks"
                        :key="task.line"
                        class="border-surface-container gap-space-sm px-space-sm py-space-sm flex items-start border-b last:border-b-0"
                        :class="
                            task.state === 'duplicate' ||
                            task.state === 'repeated'
                                ? 'opacity-60'
                                : ''
                        "
                    >
                        <AppIcon
                            :name="stateStyles[task.state].icon"
                            :size="18"
                            :class="stateStyles[task.state].classes"
                            class="mt-0.5 shrink-0"
                            :title="stateStyles[task.state].label"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-body-md font-body-md text-on-surface truncate font-medium"
                            >
                                {{ task.title || 'Sin nombre' }}
                            </p>
                            <p
                                class="text-label-sm text-on-surface-variant flex flex-wrap items-center gap-x-2"
                            >
                                <span v-if="task.typeLabel">{{
                                    task.typeLabel
                                }}</span>
                                <span v-if="task.priorityLabel"
                                    >· {{ task.priorityLabel }}</span
                                >
                                <span
                                    >·
                                    {{
                                        task.assignee?.name ?? 'Sin responsable'
                                    }}</span
                                >
                                <span class="text-outline">
                                    · línea {{ task.line }}</span
                                >
                            </p>
                            <p
                                v-if="task.excerpt"
                                class="text-label-sm text-on-surface-variant mt-0.5 line-clamp-1"
                            >
                                {{ task.excerpt }}
                            </p>
                            <ul
                                v-if="task.messages.length > 0"
                                class="mt-1 flex flex-col gap-0.5"
                            >
                                <li
                                    v-for="message in task.messages"
                                    :key="message"
                                    class="text-label-sm"
                                    :class="stateStyles[task.state].classes"
                                >
                                    {{ message }}
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>

                <div
                    v-if="preview.excluded.length > 0"
                    class="flex flex-col gap-1"
                >
                    <p
                        class="text-label-sm font-label-sm text-on-surface-variant"
                    >
                        No incluidos ({{ preview.excluded.length }}) · no se
                        crean
                    </p>
                    <p
                        v-for="item in preview.excluded"
                        :key="item.name"
                        class="text-label-sm text-on-surface-variant"
                    >
                        {{ item.name }} — {{ item.reason }}
                    </p>
                </div>

                <p
                    v-if="error"
                    class="text-label-sm font-label-sm text-error"
                    role="alert"
                >
                    {{ error }}
                </p>
            </div>

            <div
                class="border-surface-container gap-space-sm px-space-lg py-space-md flex shrink-0 items-center border-t"
            >
                <button
                    v-if="preview"
                    type="button"
                    class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container hover:text-on-surface -ml-3 flex items-center gap-1 rounded-lg px-3 py-1.5 transition-colors"
                    @click="preview = null"
                >
                    <AppIcon name="arrow_back" :size="16" />
                    Cambiar archivo
                </button>
                <div class="gap-space-sm ml-auto flex items-center">
                    <button
                        type="button"
                        class="text-label-md font-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg px-4 py-2 transition-colors"
                        @click="emit('close')"
                    >
                        Cancelar
                    </button>
                    <button
                        v-if="!preview"
                        type="button"
                        :disabled="processing || content.trim() === ''"
                        class="bg-primary hover:bg-primary-container text-on-primary text-label-md font-label-md shadow-card rounded-lg px-4 py-2 transition-colors disabled:opacity-50"
                        @click="review"
                    >
                        Revisar
                    </button>
                    <button
                        v-else
                        type="button"
                        :disabled="
                            processing || preview.counts.importable === 0
                        "
                        class="bg-primary hover:bg-primary-container text-on-primary text-label-md font-label-md shadow-card rounded-lg px-4 py-2 transition-colors disabled:opacity-50"
                        @click="confirm"
                    >
                        Importar {{ preview.counts.importable }} de
                        {{ preview.counts.total }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
