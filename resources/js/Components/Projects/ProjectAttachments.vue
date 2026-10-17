<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Modal from '@/Components/Modal.vue';
import {
    destroy,
    storeFile,
    storeLink,
    update,
} from '@/actions/App/Http/Controllers/ProjectAttachmentController';
import type {
    AttachmentTypeValue,
    ProjectAttachmentItem,
    ProjectAttachments,
} from '@/types/project';
import { relativeTime } from '@/utils/relativeTime';
import { router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const MAX_FILES_PER_SELECTION = 10;

const props = defineProps<{
    projectId: number;
    attachments: ProjectAttachments | undefined;
}>();

type Filter = 'all' | AttachmentTypeValue;

const groups: { type: AttachmentTypeValue; label: string }[] = [
    { type: 'image', label: 'Imágenes' },
    { type: 'document', label: 'Archivos' },
    { type: 'link', label: 'Links' },
];

const activeFilter = ref<Filter>('all');

const items = computed(() => props.attachments?.items ?? []);

const countOf = (type: Filter): number =>
    type === 'all'
        ? items.value.length
        : items.value.filter((item) => item.type === type).length;

const filters = computed(() =>
    [{ type: 'all' as Filter, label: 'Todo' }, ...groups].map((filter) => ({
        ...filter,
        count: countOf(filter.type),
    })),
);

const visibleGroups = computed(() =>
    groups
        .filter(
            (group) =>
                activeFilter.value === 'all' ||
                group.type === activeFilter.value,
        )
        .map((group) => ({
            ...group,
            items: items.value.filter((item) => item.type === group.type),
        }))
        .filter((group) => group.items.length > 0),
);

const usage = computed(() => props.attachments?.usage);

const usageRatio = computed(() =>
    usage.value
        ? Math.max(
              usage.value.count / usage.value.maxCount,
              usage.value.bytes / usage.value.maxBytes,
          )
        : 0,
);

const isFull = computed(
    () =>
        usage.value !== undefined && usage.value.count >= usage.value.maxCount,
);

const canAdd = computed(
    () => props.attachments?.canCreate === true && !isFull.value,
);

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function extensionOf(name: string): string {
    return name.split('.').pop()?.toLowerCase() ?? '';
}

function documentIcon(item: ProjectAttachmentItem): string {
    const extension = extensionOf(item.originalName ?? '');

    if (extension === 'pdf') {
        return 'picture_as_pdf';
    }

    if (['xls', 'xlsx', 'csv'].includes(extension)) {
        return 'table_chart';
    }

    if (['ppt', 'pptx'].includes(extension)) {
        return 'slideshow';
    }

    return 'description';
}

const reloadOnly = ['attachments', 'flash'];

// Uploads: one request per file, sent one after another.

interface UploadEntry {
    key: number;
    name: string;
    progress: number;
    status: 'pending' | 'uploading' | 'done' | 'error';
    error: string | null;
}

const uploads = ref<UploadEntry[]>([]);
const fileInput = ref<HTMLInputElement | null>(null);
const uploading = computed(() =>
    uploads.value.some(
        (entry) => entry.status === 'pending' || entry.status === 'uploading',
    ),
);
const uploadsFinished = computed(
    () => uploads.value.length > 0 && !uploading.value,
);
let uploadKey = 0;

/**
 * Check type and size before sending, mirroring the server limits.
 */
function precheck(file: File): string | null {
    const limits = props.attachments!.limits;
    const extension = extensionOf(file.name);

    if (!limits.accept.split(',').includes(`.${extension}`)) {
        return 'Tipo de archivo no permitido.';
    }

    if (limits.imageExtensions.includes(extension)) {
        return file.size > limits.imageMaxBytes
            ? 'Las imágenes no pueden superar 5 MB.'
            : null;
    }

    return file.size > limits.documentMaxBytes
        ? 'Los documentos no pueden superar 15 MB.'
        : null;
}

function uploadOne(file: File, entry: UploadEntry): Promise<void> {
    return new Promise((resolve) => {
        router.post(
            storeFile.url(props.projectId),
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                only: reloadOnly,
                onStart: () => (entry.status = 'uploading'),
                onProgress: (event) =>
                    (entry.progress = event?.percentage ?? entry.progress),
                onSuccess: () => {
                    entry.status = 'done';
                    entry.progress = 100;
                },
                onError: (errors) => {
                    entry.status = 'error';
                    entry.error =
                        errors.file ??
                        errors.project ??
                        'No se pudo subir el archivo.';
                },
                onFinish: () => resolve(),
            },
        );
    });
}

async function uploadFiles(files: File[]): Promise<void> {
    if (!canAdd.value || uploading.value || files.length === 0) {
        return;
    }

    const accepted = files.slice(0, MAX_FILES_PER_SELECTION);
    const entries = accepted.map((file) => {
        const error = precheck(file);

        return {
            key: ++uploadKey,
            name: file.name,
            progress: 0,
            status: error ? 'error' : 'pending',
            error,
        } as UploadEntry;
    });

    if (files.length > MAX_FILES_PER_SELECTION) {
        entries.push({
            key: ++uploadKey,
            name: `${files.length - MAX_FILES_PER_SELECTION} archivo(s) más`,
            progress: 0,
            status: 'error',
            error: `Se pueden subir hasta ${MAX_FILES_PER_SELECTION} archivos a la vez.`,
        });
    }

    uploads.value = entries;

    for (const [index, file] of accepted.entries()) {
        const entry = uploads.value[index];

        if (entry.status === 'pending') {
            await uploadOne(file, entry);
        }
    }
}

function onFilesSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';

    uploadFiles(files);
}

// Drag and drop over the whole section.

const dragDepth = ref(0);
const isDragging = computed(() => dragDepth.value > 0);

function hasFiles(event: DragEvent): boolean {
    return Array.from(event.dataTransfer?.types ?? []).includes('Files');
}

function onDragEnter(event: DragEvent): void {
    if (canAdd.value && hasFiles(event)) {
        dragDepth.value++;
    }
}

function onDragLeave(event: DragEvent): void {
    if (canAdd.value && hasFiles(event)) {
        dragDepth.value = Math.max(0, dragDepth.value - 1);
    }
}

function onDrop(event: DragEvent): void {
    dragDepth.value = 0;
    uploadFiles(Array.from(event.dataTransfer?.files ?? []));
}

// Links

const linkFormOpen = ref(false);
const linkUrlInput = ref<HTMLInputElement | null>(null);
const linkForm = useForm({ url: '', title: '' });

async function openLinkForm(): Promise<void> {
    linkFormOpen.value = true;
    await nextTick();
    linkUrlInput.value?.focus();
}

function closeLinkForm(): void {
    linkFormOpen.value = false;
    linkForm.reset();
    linkForm.clearErrors();
}

function submitLink(): void {
    linkForm.post(storeLink.url(props.projectId), {
        preserveScroll: true,
        only: reloadOnly,
        onSuccess: () => closeLinkForm(),
    });
}

// Rename and delete

const editingId = ref<number | null>(null);
const renameForm = useForm({ title: '' });

async function startRename(item: ProjectAttachmentItem): Promise<void> {
    editingId.value = item.id;
    renameForm.title = item.title;
    renameForm.clearErrors();
    await nextTick();
    document.getElementById(`rename-${item.id}`)?.focus();
}

function saveRename(item: ProjectAttachmentItem): void {
    renameForm.put(update.url([props.projectId, item.id]), {
        preserveScroll: true,
        only: reloadOnly,
        onSuccess: () => (editingId.value = null),
    });
}

function remove(item: ProjectAttachmentItem): void {
    if (!window.confirm(`¿Eliminar "${item.title}"?`)) {
        return;
    }

    router.delete(destroy.url([props.projectId, item.id]), {
        preserveScroll: true,
        only: reloadOnly,
    });
}

function hrefOf(item: ProjectAttachmentItem): string {
    return (item.type === 'link' ? item.url : item.openUrl) ?? '#';
}

const previewItem = ref<ProjectAttachmentItem | null>(null);

const menuItemClass =
    'text-body-sm font-body-sm text-on-surface hover:bg-surface-container-low gap-space-sm px-space-md flex w-full items-center py-2 text-left';

const iconButtonClass =
    'text-on-surface-variant hover:bg-surface-container hover:text-on-surface focus-visible:ring-primary flex h-8 w-8 items-center justify-center rounded-lg transition-colors focus-visible:ring-2 focus-visible:outline-none';
</script>

<template>
    <section
        aria-labelledby="project-documents-heading"
        class="bg-surface-container-lowest shadow-card relative flex flex-col rounded-xl"
        @dragenter.prevent="onDragEnter"
        @dragover.prevent
        @dragleave.prevent="onDragLeave"
        @drop.prevent="onDrop"
    >
        <div
            v-if="!attachments"
            class="p-space-xl flex animate-pulse flex-col gap-3"
            aria-busy="true"
        >
            <div class="bg-surface-container h-6 w-40 rounded" />
            <div class="bg-surface-container h-1 w-56 rounded" />
            <div
                class="gap-space-md mt-space-md grid grid-cols-2 sm:grid-cols-4"
            >
                <div class="bg-surface-container aspect-[4/3] rounded-lg" />
                <div class="bg-surface-container aspect-[4/3] rounded-lg" />
            </div>
            <div class="bg-surface-container h-12 rounded-lg" />
            <div class="bg-surface-container h-12 rounded-lg" />
        </div>

        <template v-else>
            <header
                class="gap-space-lg p-space-lg sm:p-space-xl flex flex-col sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <h2
                        id="project-documents-heading"
                        class="text-headline-md font-headline-md text-on-surface"
                    >
                        Documentos
                    </h2>
                    <div
                        class="mt-space-sm gap-space-sm text-label-sm font-label-sm text-on-surface-variant flex items-center tabular-nums"
                    >
                        <div
                            class="bg-surface-container h-1 w-24 overflow-hidden rounded-full"
                            role="meter"
                            aria-label="Espacio usado"
                            :aria-valuenow="Math.round(usageRatio * 100)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full transition-[width] duration-300 ease-out"
                                :class="
                                    usageRatio >= 0.9
                                        ? 'bg-error'
                                        : 'bg-primary-container'
                                "
                                :style="{
                                    width: `${Math.min(100, Math.max(usageRatio * 100, 2))}%`,
                                }"
                            />
                        </div>
                        <span>
                            {{ usage!.count }}/{{ usage!.maxCount }} adjuntos ·
                            {{ formatBytes(usage!.bytes) }} de
                            {{ formatBytes(usage!.maxBytes) }}
                        </span>
                    </div>
                </div>

                <div
                    v-if="attachments.canCreate"
                    class="gap-space-sm flex shrink-0 flex-wrap items-center"
                >
                    <button
                        type="button"
                        :disabled="!canAdd"
                        class="border-outline-variant/40 bg-surface-container-lowest text-on-surface hover:bg-surface-container-low font-label-md text-label-md gap-space-xs focus-visible:ring-primary flex h-9 items-center rounded-lg border px-3 transition-colors focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        @click="linkFormOpen ? closeLinkForm() : openLinkForm()"
                    >
                        <AppIcon name="add_link" />
                        Agregar link
                    </button>
                    <button
                        type="button"
                        :disabled="!canAdd || uploading"
                        class="bg-primary-container hover:bg-primary font-label-md text-label-md gap-space-xs focus-visible:ring-primary flex h-9 items-center rounded-lg px-3 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.2)] transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        @click="fileInput?.click()"
                    >
                        <AppIcon name="upload" />
                        Subir archivos
                    </button>
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        class="hidden"
                        :accept="attachments.limits.accept"
                        @change="onFilesSelected"
                    />
                </div>
            </header>

            <p
                v-if="attachments.canCreate && isFull"
                class="px-space-lg sm:px-space-xl -mt-space-sm pb-space-md text-label-sm font-label-sm text-error"
            >
                El proyecto alcanzó el máximo de {{ usage!.maxCount }}
                adjuntos. Elimina alguno para agregar más.
            </p>

            <form
                v-if="linkFormOpen"
                class="mx-space-lg sm:mx-space-xl mb-space-lg gap-space-sm p-space-md bg-surface-container-low border-outline-variant/30 flex flex-col rounded-lg border sm:flex-row sm:items-start"
                @submit.prevent="submitLink"
                @keydown.esc="closeLinkForm"
            >
                <div class="flex flex-[3] flex-col gap-1">
                    <label for="attachment-link-url" class="sr-only">URL</label>
                    <input
                        id="attachment-link-url"
                        ref="linkUrlInput"
                        v-model="linkForm.url"
                        type="url"
                        required
                        placeholder="https://"
                        class="text-body-md font-body-md border-outline-variant/40 bg-surface-container-lowest text-on-surface placeholder:text-on-surface-variant/70 focus:border-primary-container focus:ring-primary-container/15 h-9 w-full rounded-lg border px-3 focus:ring-2 focus:outline-none"
                    />
                    <p
                        v-if="linkForm.errors.url"
                        class="text-label-sm font-label-sm text-error"
                    >
                        {{ linkForm.errors.url }}
                    </p>
                </div>
                <div class="flex flex-[2] flex-col gap-1">
                    <label for="attachment-link-title" class="sr-only">
                        Título
                    </label>
                    <input
                        id="attachment-link-title"
                        v-model="linkForm.title"
                        type="text"
                        maxlength="255"
                        placeholder="Título (opcional)"
                        class="text-body-md font-body-md border-outline-variant/40 bg-surface-container-lowest text-on-surface placeholder:text-on-surface-variant/70 focus:border-primary-container focus:ring-primary-container/15 h-9 w-full rounded-lg border px-3 focus:ring-2 focus:outline-none"
                    />
                    <p
                        v-if="linkForm.errors.title"
                        class="text-label-sm font-label-sm text-error"
                    >
                        {{ linkForm.errors.title }}
                    </p>
                </div>
                <div class="gap-space-xs flex shrink-0">
                    <button
                        type="button"
                        class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-md text-label-md h-9 rounded-lg px-3 transition-colors"
                        @click="closeLinkForm"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="linkForm.processing"
                        class="bg-primary-container hover:bg-primary font-label-md text-label-md h-9 rounded-lg px-3 text-white transition-colors disabled:opacity-60"
                    >
                        Agregar
                    </button>
                </div>
            </form>

            <div
                v-if="uploads.length"
                class="mx-space-lg sm:mx-space-xl mb-space-lg border-outline-variant/30 rounded-lg border"
                aria-live="polite"
            >
                <div
                    class="px-space-md text-label-sm font-label-sm text-on-surface-variant flex h-9 items-center justify-between"
                >
                    <span>
                        {{
                            uploading
                                ? 'Subiendo archivos…'
                                : 'Subida terminada'
                        }}
                    </span>
                    <button
                        v-if="uploadsFinished"
                        type="button"
                        :class="iconButtonClass"
                        class="h-7 w-7"
                        aria-label="Cerrar resumen de subida"
                        @click="uploads = []"
                    >
                        <AppIcon name="close" :size="16" />
                    </button>
                </div>
                <ul class="divide-outline-variant/20 divide-y">
                    <li
                        v-for="entry in uploads"
                        :key="entry.key"
                        class="gap-space-sm px-space-md text-body-sm font-body-sm flex min-h-10 flex-wrap items-center py-2"
                    >
                        <AppIcon
                            :name="
                                entry.status === 'done'
                                    ? 'check_circle'
                                    : entry.status === 'error'
                                      ? 'error'
                                      : 'progress_activity'
                            "
                            :size="18"
                            :class="{
                                'text-primary': entry.status === 'done',
                                'text-error': entry.status === 'error',
                                'text-on-surface-variant animate-spin':
                                    entry.status === 'uploading' ||
                                    entry.status === 'pending',
                            }"
                        />
                        <span class="text-on-surface min-w-0 flex-1 truncate">
                            {{ entry.name }}
                        </span>
                        <span
                            v-if="entry.status === 'uploading'"
                            class="bg-surface-container h-1 w-24 overflow-hidden rounded-full"
                        >
                            <span
                                class="bg-primary-container block h-full transition-[width] duration-200"
                                :style="{ width: `${entry.progress}%` }"
                            />
                        </span>
                        <span
                            v-if="entry.error"
                            class="text-error basis-full pl-[26px] sm:basis-auto sm:pl-0"
                        >
                            {{ entry.error }}
                        </span>
                    </li>
                </ul>
            </div>

            <div
                v-if="!items.length"
                class="mx-space-lg sm:mx-space-xl mb-space-xl gap-space-sm py-space-3xl flex flex-col items-center rounded-lg border border-dashed text-center transition-colors"
                :class="
                    isDragging
                        ? 'border-primary-container bg-primary-fixed/40'
                        : 'border-outline-variant/60'
                "
            >
                <AppIcon
                    name="folder_open"
                    :size="32"
                    class="text-on-surface-variant"
                />
                <p class="text-body-lg font-headline-sm text-on-surface">
                    Aún no hay documentos en este proyecto
                </p>
                <p
                    v-if="attachments.canCreate"
                    class="text-body-sm font-body-sm text-on-surface-variant max-w-sm"
                >
                    Arrastra archivos aquí o
                    <button
                        type="button"
                        class="text-primary font-label-md underline-offset-2 hover:underline"
                        @click="fileInput?.click()"
                    >
                        elígelos desde tu equipo</button
                    >. Imágenes hasta 5 MB, documentos hasta 15 MB.
                </p>
            </div>

            <template v-else>
                <div
                    class="border-outline-variant/30 px-space-lg sm:px-space-xl flex [scrollbar-width:none] overflow-x-auto border-b"
                    role="group"
                    aria-label="Filtrar documentos por tipo"
                >
                    <button
                        v-for="filter in filters"
                        :key="filter.type"
                        type="button"
                        :aria-pressed="activeFilter === filter.type"
                        :disabled="filter.count === 0"
                        class="font-label-md text-label-md gap-space-xs -mb-px flex h-10 shrink-0 items-center border-b-2 px-3 transition-colors disabled:cursor-default disabled:opacity-40"
                        :class="
                            activeFilter === filter.type
                                ? 'border-primary-container text-on-surface'
                                : 'text-on-surface-variant hover:text-on-surface border-transparent'
                        "
                        @click="activeFilter = filter.type"
                    >
                        {{ filter.label }}
                        <span
                            class="text-label-xs font-label-xs text-on-surface-variant tabular-nums"
                        >
                            {{ filter.count }}
                        </span>
                    </button>
                </div>

                <div
                    class="gap-space-xl p-space-lg sm:p-space-xl flex flex-col"
                >
                    <section
                        v-for="group in visibleGroups"
                        :key="group.type"
                        :aria-label="group.label"
                    >
                        <h3
                            v-if="activeFilter === 'all'"
                            class="mb-space-md text-label-sm font-label-sm text-on-surface-variant"
                        >
                            {{ group.label }}
                            <span class="tabular-nums"
                                >· {{ group.items.length }}</span
                            >
                        </h3>

                        <ul
                            v-if="group.type === 'image'"
                            class="gap-space-lg grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
                        >
                            <li
                                v-for="item in group.items"
                                :key="item.id"
                                class="group/tile relative flex min-w-0 flex-col gap-2"
                            >
                                <button
                                    type="button"
                                    class="bg-surface-container-low border-outline-variant/30 focus-visible:ring-primary relative aspect-[4/3] overflow-hidden rounded-lg border focus-visible:ring-2 focus-visible:outline-none"
                                    :aria-label="`Ver ${item.title}`"
                                    @click="previewItem = item"
                                >
                                    <img
                                        :src="item.thumbnailUrl ?? ''"
                                        alt=""
                                        loading="lazy"
                                        class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover/tile:scale-[1.03]"
                                    />
                                </button>

                                <div class="flex min-w-0 items-start gap-1">
                                    <div class="min-w-0 flex-1">
                                        <form
                                            v-if="editingId === item.id"
                                            @submit.prevent="saveRename(item)"
                                        >
                                            <input
                                                :id="`rename-${item.id}`"
                                                v-model="renameForm.title"
                                                type="text"
                                                maxlength="255"
                                                aria-label="Nuevo nombre"
                                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:border-primary-container focus:ring-primary-container/15 h-8 w-full rounded-md border px-2 focus:ring-2 focus:outline-none"
                                                @keydown.esc="editingId = null"
                                                @blur="editingId = null"
                                            />
                                            <p
                                                v-if="renameForm.errors.title"
                                                class="text-label-xs font-label-xs text-error mt-1"
                                            >
                                                {{ renameForm.errors.title }}
                                            </p>
                                        </form>
                                        <template v-else>
                                            <p
                                                class="text-body-sm font-label-md text-on-surface truncate"
                                                :title="item.title"
                                            >
                                                {{ item.title }}
                                            </p>
                                            <p
                                                class="text-label-xs font-label-xs text-on-surface-variant truncate tabular-nums"
                                            >
                                                {{
                                                    formatBytes(item.size ?? 0)
                                                }}
                                                ·
                                                {{
                                                    relativeTime(item.createdAt)
                                                }}
                                            </p>
                                        </template>
                                    </div>

                                    <Dropdown align="right">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="iconButtonClass"
                                                class="-mr-1 h-7 w-7"
                                                :aria-label="`Acciones de ${item.title}`"
                                            >
                                                <AppIcon name="more_horiz" />
                                            </button>
                                        </template>
                                        <template #content>
                                            <button
                                                type="button"
                                                :class="menuItemClass"
                                                @click="previewItem = item"
                                            >
                                                <AppIcon name="visibility" />
                                                Ver
                                            </button>
                                            <a
                                                :href="item.downloadUrl ?? '#'"
                                                :class="menuItemClass"
                                            >
                                                <AppIcon name="download" />
                                                Descargar
                                            </a>
                                            <template v-if="item.canManage">
                                                <button
                                                    type="button"
                                                    :class="menuItemClass"
                                                    @click="startRename(item)"
                                                >
                                                    <AppIcon name="edit" />
                                                    Renombrar
                                                </button>
                                                <div
                                                    class="border-outline-variant/30 my-1 border-t"
                                                />
                                                <button
                                                    type="button"
                                                    :class="menuItemClass"
                                                    class="text-error"
                                                    @click="remove(item)"
                                                >
                                                    <AppIcon name="delete" />
                                                    Eliminar
                                                </button>
                                            </template>
                                        </template>
                                    </Dropdown>
                                </div>
                            </li>
                        </ul>

                        <ul
                            v-else
                            class="border-outline-variant/30 divide-outline-variant/30 divide-y rounded-lg border"
                        >
                            <li
                                v-for="item in group.items"
                                :key="item.id"
                                class="gap-space-md px-space-md hover:bg-surface-container-low/60 flex min-h-14 items-center py-2 transition-colors duration-100 first:rounded-t-lg last:rounded-b-lg"
                            >
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md"
                                    :class="
                                        item.type === 'link'
                                            ? 'bg-primary-fixed/60 text-primary'
                                            : 'bg-surface-container text-on-surface-variant'
                                    "
                                >
                                    <AppIcon
                                        :name="
                                            item.type === 'link'
                                                ? 'link'
                                                : documentIcon(item)
                                        "
                                        :size="20"
                                    />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <form
                                        v-if="editingId === item.id"
                                        @submit.prevent="saveRename(item)"
                                    >
                                        <input
                                            :id="`rename-${item.id}`"
                                            v-model="renameForm.title"
                                            type="text"
                                            maxlength="255"
                                            aria-label="Nuevo nombre"
                                            class="text-body-md font-body-md border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:border-primary-container focus:ring-primary-container/15 h-8 w-full max-w-md rounded-md border px-2 focus:ring-2 focus:outline-none"
                                            @keydown.esc="editingId = null"
                                            @blur="editingId = null"
                                        />
                                        <p
                                            v-if="renameForm.errors.title"
                                            class="text-label-xs font-label-xs text-error mt-1"
                                        >
                                            {{ renameForm.errors.title }}
                                        </p>
                                    </form>
                                    <a
                                        v-else
                                        :href="hrefOf(item)"
                                        target="_blank"
                                        :rel="
                                            item.type === 'link'
                                                ? 'noopener noreferrer'
                                                : 'noopener'
                                        "
                                        class="text-body-md font-label-md text-on-surface hover:text-primary block truncate underline-offset-2 hover:underline"
                                        :title="item.title"
                                    >
                                        {{ item.title }}
                                    </a>
                                    <p
                                        class="text-label-sm font-body-sm text-on-surface-variant mt-0.5 flex min-w-0 items-center gap-1.5 tabular-nums"
                                    >
                                        <span class="truncate">
                                            {{
                                                item.type === 'link'
                                                    ? item.domain
                                                    : formatBytes(
                                                          item.size ?? 0,
                                                      )
                                            }}
                                        </span>
                                        <span aria-hidden="true">·</span>
                                        <span
                                            class="hidden shrink-0 items-center gap-1 sm:flex"
                                        >
                                            <Avatar
                                                v-if="item.author"
                                                :name="item.author.name"
                                                :src="item.author.avatar"
                                                :size="16"
                                            />
                                            {{
                                                item.author?.name ??
                                                'Usuario eliminado'
                                            }}
                                        </span>
                                        <span
                                            class="hidden sm:inline"
                                            aria-hidden="true"
                                            >·</span
                                        >
                                        <span class="shrink-0">{{
                                            relativeTime(item.createdAt)
                                        }}</span>
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center">
                                    <a
                                        :href="
                                            item.type === 'link'
                                                ? (item.url ?? '#')
                                                : (item.downloadUrl ?? '#')
                                        "
                                        :target="
                                            item.type === 'link'
                                                ? '_blank'
                                                : undefined
                                        "
                                        :rel="
                                            item.type === 'link'
                                                ? 'noopener noreferrer'
                                                : undefined
                                        "
                                        :class="iconButtonClass"
                                        :aria-label="
                                            item.type === 'link'
                                                ? `Abrir ${item.title} en otra pestaña`
                                                : `Descargar ${item.title}`
                                        "
                                        :title="
                                            item.type === 'link'
                                                ? 'Abrir'
                                                : 'Descargar'
                                        "
                                    >
                                        <AppIcon
                                            :name="
                                                item.type === 'link'
                                                    ? 'open_in_new'
                                                    : 'download'
                                            "
                                        />
                                    </a>
                                    <Dropdown
                                        v-if="item.canManage"
                                        align="right"
                                    >
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="iconButtonClass"
                                                :aria-label="`Más acciones de ${item.title}`"
                                            >
                                                <AppIcon name="more_horiz" />
                                            </button>
                                        </template>
                                        <template #content>
                                            <button
                                                type="button"
                                                :class="menuItemClass"
                                                @click="startRename(item)"
                                            >
                                                <AppIcon name="edit" />
                                                Renombrar
                                            </button>
                                            <div
                                                class="border-outline-variant/30 my-1 border-t"
                                            />
                                            <button
                                                type="button"
                                                :class="menuItemClass"
                                                class="text-error"
                                                @click="remove(item)"
                                            >
                                                <AppIcon name="delete" />
                                                Eliminar
                                            </button>
                                        </template>
                                    </Dropdown>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>
            </template>

            <div
                v-if="isDragging && items.length"
                class="border-primary-container bg-surface-container-lowest/85 pointer-events-none absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed backdrop-blur-[2px]"
            >
                <AppIcon name="upload" :size="28" class="text-primary" />
                <p class="text-headline-sm font-headline-sm text-on-surface">
                    Suelta para subir
                </p>
                <p class="text-body-sm font-body-sm text-on-surface-variant">
                    Hasta {{ MAX_FILES_PER_SELECTION }} archivos a la vez
                </p>
            </div>
        </template>

        <Modal
            :show="previewItem !== null"
            max-width="2xl"
            @close="previewItem = null"
        >
            <div v-if="previewItem" class="p-space-lg flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4">
                    <p
                        class="text-headline-sm font-headline-sm text-on-surface truncate"
                    >
                        {{ previewItem.title }}
                    </p>
                    <div class="flex shrink-0 items-center">
                        <a
                            :href="previewItem.downloadUrl ?? '#'"
                            :class="iconButtonClass"
                            :aria-label="`Descargar ${previewItem.title}`"
                            title="Descargar"
                        >
                            <AppIcon name="download" />
                        </a>
                        <button
                            type="button"
                            :class="iconButtonClass"
                            aria-label="Cerrar"
                            @click="previewItem = null"
                        >
                            <AppIcon name="close" />
                        </button>
                    </div>
                </div>
                <img
                    :src="previewItem.openUrl ?? ''"
                    :alt="previewItem.title"
                    class="bg-surface-container-low max-h-[75vh] w-full rounded-lg object-contain"
                />
            </div>
        </Modal>
    </section>
</template>
