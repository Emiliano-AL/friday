<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
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
import { computed, ref } from 'vue';

const MAX_FILES_PER_SELECTION = 10;

const props = defineProps<{
    projectId: number;
    attachments: ProjectAttachments | undefined;
}>();

type Filter = 'all' | AttachmentTypeValue;

const filters: { value: Filter; label: string }[] = [
    { value: 'all', label: 'Todos' },
    { value: 'document', label: 'Documentos' },
    { value: 'image', label: 'Imágenes' },
    { value: 'link', label: 'Links' },
];

const activeFilter = ref<Filter>('all');

const visibleItems = computed(() =>
    (props.attachments?.items ?? []).filter(
        (item) =>
            activeFilter.value === 'all' || item.type === activeFilter.value,
    ),
);

const isFull = computed(
    () =>
        props.attachments !== undefined &&
        props.attachments.usage.count >= props.attachments.usage.maxCount,
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
let uploadKey = 0;

function extensionOf(name: string): string {
    return name.split('.').pop()?.toLowerCase() ?? '';
}

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

async function onFilesSelected(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    input.value = '';

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

// Links

const linkFormOpen = ref(false);
const linkForm = useForm({ url: '', title: '' });

function submitLink(): void {
    linkForm.post(storeLink.url(props.projectId), {
        preserveScroll: true,
        only: reloadOnly,
        onSuccess: () => {
            linkForm.reset();
            linkFormOpen.value = false;
        },
    });
}

// Rename and delete

const editingId = ref<number | null>(null);
const renameForm = useForm({ title: '' });

function startRename(item: ProjectAttachmentItem): void {
    editingId.value = item.id;
    renameForm.title = item.title;
    renameForm.clearErrors();
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

// Image preview

const previewItem = ref<ProjectAttachmentItem | null>(null);

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
</script>

<template>
    <section
        aria-label="Archivos y referencias"
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl"
    >
        <div v-if="!attachments" class="flex animate-pulse flex-col gap-3">
            <div class="bg-surface-container h-6 w-1/3 rounded" />
            <div class="bg-surface-container h-14 rounded-lg" />
            <div class="bg-surface-container h-14 rounded-lg" />
            <div class="bg-surface-container h-14 rounded-lg" />
        </div>

        <template v-else>
            <div
                class="gap-space-sm flex flex-wrap items-center justify-between"
            >
                <div>
                    <h2
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        Archivos y referencias
                    </h2>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant mt-1"
                    >
                        {{ attachments.usage.count }} de
                        {{ attachments.usage.maxCount }} adjuntos ·
                        {{ formatBytes(attachments.usage.bytes) }} de
                        {{ formatBytes(attachments.usage.maxBytes) }}
                    </p>
                </div>

                <div
                    v-if="attachments.canCreate"
                    class="gap-space-sm flex items-center"
                >
                    <button
                        type="button"
                        :disabled="isFull"
                        class="border-outline-variant/40 text-on-surface hover:bg-surface-container font-label-md text-label-md gap-space-xs flex h-10 items-center rounded-lg border px-4 transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                        @click="linkFormOpen = !linkFormOpen"
                    >
                        <AppIcon name="add_link" />
                        Agregar link
                    </button>
                    <button
                        type="button"
                        :disabled="isFull || uploading"
                        class="bg-primary-container hover:bg-primary shadow-primary-container/30 font-label-md text-label-md gap-space-xs flex h-10 items-center rounded-lg px-4 text-white shadow-md transition-colors disabled:cursor-not-allowed disabled:opacity-60"
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
            </div>

            <p
                v-if="attachments.canCreate && isFull"
                class="text-label-xs font-label-xs text-error"
            >
                El proyecto alcanzó el máximo de
                {{ attachments.usage.maxCount }} adjuntos.
            </p>

            <form
                v-if="linkFormOpen"
                class="gap-space-sm p-space-sm bg-surface-container-low/50 flex flex-col rounded-lg sm:flex-row sm:items-start"
                @submit.prevent="submitLink"
            >
                <div class="flex flex-1 flex-col gap-1">
                    <input
                        v-model="linkForm.url"
                        type="url"
                        required
                        placeholder="https://..."
                        aria-label="URL"
                        class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-10 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
                    />
                    <p
                        v-if="linkForm.errors.url"
                        class="text-label-xs font-label-xs text-error"
                    >
                        {{ linkForm.errors.url }}
                    </p>
                </div>
                <div class="flex flex-1 flex-col gap-1">
                    <input
                        v-model="linkForm.title"
                        type="text"
                        maxlength="255"
                        placeholder="Título (opcional)"
                        aria-label="Título"
                        class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-10 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
                    />
                    <p
                        v-if="linkForm.errors.title"
                        class="text-label-xs font-label-xs text-error"
                    >
                        {{ linkForm.errors.title }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="linkForm.processing"
                    class="bg-primary-container hover:bg-primary font-label-md text-label-md h-10 shrink-0 rounded-lg px-4 text-white transition-colors disabled:opacity-60"
                >
                    Guardar
                </button>
            </form>

            <ul v-if="uploads.length" class="flex flex-col gap-1">
                <li
                    v-for="entry in uploads"
                    :key="entry.key"
                    class="text-body-sm font-body-sm gap-space-sm flex items-center"
                >
                    <AppIcon
                        :name="
                            entry.status === 'done'
                                ? 'check_circle'
                                : entry.status === 'error'
                                  ? 'error'
                                  : 'progress_activity'
                        "
                        :class="{
                            'text-primary': entry.status === 'done',
                            'text-error': entry.status === 'error',
                            'text-on-surface-variant animate-spin':
                                entry.status === 'uploading',
                        }"
                    />
                    <span class="text-on-surface truncate">{{
                        entry.name
                    }}</span>
                    <progress
                        v-if="entry.status === 'uploading'"
                        :value="entry.progress"
                        max="100"
                        class="h-1.5 w-24"
                    />
                    <span v-if="entry.error" class="text-error">
                        {{ entry.error }}
                    </span>
                </li>
            </ul>

            <div
                v-if="attachments.items.length"
                class="gap-space-xs flex flex-wrap"
                role="group"
                aria-label="Filtrar por tipo"
            >
                <button
                    v-for="filter in filters"
                    :key="filter.value"
                    type="button"
                    :aria-pressed="activeFilter === filter.value"
                    class="font-label-md text-label-md rounded-full px-3 py-1 transition-colors"
                    :class="
                        activeFilter === filter.value
                            ? 'bg-primary-container text-white'
                            : 'bg-surface-container-low text-on-surface-variant hover:text-on-surface'
                    "
                    @click="activeFilter = filter.value"
                >
                    {{ filter.label }}
                </button>
            </div>

            <div
                v-if="!attachments.items.length"
                class="gap-space-sm py-space-lg flex flex-col items-center text-center"
            >
                <AppIcon
                    name="folder_open"
                    :size="40"
                    class="text-on-surface-variant"
                />
                <p class="text-body-md font-body-md text-on-surface">
                    Este proyecto todavía no tiene archivos ni links.
                </p>
                <p
                    v-if="attachments.canCreate"
                    class="text-body-sm font-body-sm text-on-surface-variant"
                >
                    Sube documentos o imágenes de referencia, o agrega un link.
                </p>
            </div>

            <p
                v-else-if="!visibleItems.length"
                class="text-body-sm font-body-sm text-on-surface-variant py-space-md text-center"
            >
                No hay adjuntos de este tipo.
            </p>

            <ul v-else class="gap-space-xs flex flex-col">
                <li
                    v-for="item in visibleItems"
                    :key="item.id"
                    class="gap-space-sm p-space-sm bg-surface-container-low/50 flex items-center rounded-lg"
                >
                    <button
                        v-if="item.type === 'image'"
                        type="button"
                        class="h-12 w-12 shrink-0 overflow-hidden rounded-md"
                        :aria-label="`Ver ${item.title}`"
                        @click="previewItem = item"
                    >
                        <img
                            :src="item.thumbnailUrl ?? ''"
                            :alt="item.title"
                            loading="lazy"
                            class="h-full w-full object-cover"
                        />
                    </button>
                    <span
                        v-else
                        class="bg-surface-container text-on-surface-variant flex h-12 w-12 shrink-0 items-center justify-center rounded-md"
                    >
                        <AppIcon
                            :name="
                                item.type === 'link'
                                    ? 'link'
                                    : documentIcon(item)
                            "
                            :size="24"
                        />
                    </span>

                    <div class="min-w-0 flex-1">
                        <form
                            v-if="editingId === item.id"
                            class="flex flex-col gap-1"
                            @submit.prevent="saveRename(item)"
                        >
                            <input
                                v-model="renameForm.title"
                                type="text"
                                maxlength="255"
                                aria-label="Nuevo título"
                                class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-9 w-full rounded-lg border px-3 focus:ring-2 focus:outline-none"
                                @keydown.esc="editingId = null"
                            />
                            <p
                                v-if="renameForm.errors.title"
                                class="text-label-xs font-label-xs text-error"
                            >
                                {{ renameForm.errors.title }}
                            </p>
                        </form>
                        <template v-else>
                            <a
                                v-if="item.type === 'link'"
                                :href="item.url ?? '#'"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-body-md font-body-md text-on-surface block truncate hover:underline"
                            >
                                {{ item.title }}
                            </a>
                            <a
                                v-else
                                :href="item.openUrl ?? '#'"
                                target="_blank"
                                rel="noopener"
                                class="text-body-md font-body-md text-on-surface block truncate hover:underline"
                            >
                                {{ item.title }}
                            </a>
                        </template>
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant gap-space-xs flex flex-wrap items-center"
                        >
                            <span>{{ item.typeLabel }}</span>
                            <span>·</span>
                            <span v-if="item.type === 'link'">{{
                                item.domain
                            }}</span>
                            <span v-else-if="item.size !== null">{{
                                formatBytes(item.size)
                            }}</span>
                            <span>·</span>
                            <Avatar
                                v-if="item.author"
                                :name="item.author.name"
                                :src="item.author.avatar"
                                :size="16"
                            />
                            <span>{{
                                item.author?.name ?? 'Usuario eliminado'
                            }}</span>
                            <span>·</span>
                            <span>{{ relativeTime(item.createdAt) }}</span>
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center">
                        <a
                            v-if="item.downloadUrl"
                            :href="item.downloadUrl"
                            class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                            :aria-label="`Descargar ${item.title}`"
                            title="Descargar"
                        >
                            <AppIcon name="download" />
                        </a>
                        <template v-if="item.canManage">
                            <button
                                v-if="editingId === item.id"
                                type="button"
                                class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                                aria-label="Guardar título"
                                title="Guardar"
                                @click="saveRename(item)"
                            >
                                <AppIcon name="check" />
                            </button>
                            <button
                                v-else
                                type="button"
                                class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                                :aria-label="`Renombrar ${item.title}`"
                                title="Renombrar"
                                @click="startRename(item)"
                            >
                                <AppIcon name="edit" />
                            </button>
                            <button
                                type="button"
                                class="text-on-surface-variant hover:bg-error/10 hover:text-error flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                                :aria-label="`Eliminar ${item.title}`"
                                title="Eliminar"
                                @click="remove(item)"
                            >
                                <AppIcon name="delete" />
                            </button>
                        </template>
                    </div>
                </li>
            </ul>
        </template>

        <Modal
            :show="previewItem !== null"
            max-width="2xl"
            @close="previewItem = null"
        >
            <div v-if="previewItem" class="p-space-md flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4">
                    <p
                        class="text-body-md font-body-md text-on-surface truncate"
                    >
                        {{ previewItem.title }}
                    </p>
                    <button
                        type="button"
                        class="text-on-surface-variant hover:bg-surface-container flex h-8 w-8 items-center justify-center rounded-lg"
                        aria-label="Cerrar"
                        @click="previewItem = null"
                    >
                        <AppIcon name="close" />
                    </button>
                </div>
                <img
                    :src="previewItem.openUrl ?? ''"
                    :alt="previewItem.title"
                    class="max-h-[75vh] w-full rounded-lg object-contain"
                />
            </div>
        </Modal>
    </section>
</template>
