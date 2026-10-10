<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import RichTextEditor from '@/Components/RichText/RichTextEditor.vue';
import { destroy, store, update } from '@/routes/projects';
import { PROJECT_ICONS, PROJECT_PALETTE } from '@/types/project';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    project?: {
        id: number;
        title: string;
        description: string | null;
        key: string | null;
        color: string | null;
        icon: string | null;
        targetDate: string | null;
    };
}>();

const emit = defineEmits<{
    close: [];
}>();

const titleInput = ref<HTMLInputElement | null>(null);
const descriptionEditor = ref<InstanceType<typeof RichTextEditor> | null>(null);

const form = useForm({
    title: '',
    description: '',
    key: '',
    color: null as string | null,
    icon: null as string | null,
    target_date: '',
});

watch(
    () => props.open,
    async (open) => {
        if (open) {
            form.title = props.project?.title ?? '';
            form.description = props.project?.description ?? '';
            form.key = props.project?.key ?? '';
            form.color = props.project?.color ?? null;
            form.icon = props.project?.icon ?? null;
            form.target_date = props.project?.targetDate ?? '';
            form.clearErrors();
            await nextTick();
            titleInput.value?.focus();
        }
    },
);

async function submit(): Promise<void> {
    form.description = (await descriptionEditor.value?.save()) ?? '';
    form.clearErrors('description');

    form.transform((data) => ({
        ...data,
        key: data.key === '' ? null : data.key,
        color: data.color === '' ? null : data.color,
        icon: data.icon === '' ? null : data.icon,
        target_date: data.target_date === '' ? null : data.target_date,
    }));

    if (props.mode === 'create') {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });

        return;
    }

    if (props.project) {
        form.put(update.url(props.project.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
}

function onKeyInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    input.value = input.value.toUpperCase();
    form.key = input.value;
}

function removeProject(): void {
    if (!props.project) {
        return;
    }

    if (
        window.confirm(
            '¿Eliminar este proyecto definitivamente? Esta acción no se puede deshacer.',
        )
    ) {
        form.delete(destroy.url(props.project.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    }
}
</script>

<template>
    <Modal :show="open" max-width="lg" @close="emit('close')">
        <div
            class="px-space-lg pt-space-lg pb-space-sm flex items-start justify-between"
        >
            <div class="gap-space-sm flex items-center">
                <div
                    class="bg-surface-container text-primary flex h-8 w-8 items-center justify-center rounded-lg"
                >
                    <AppIcon name="add_task" :size="18" />
                </div>
                <div>
                    <h3
                        class="text-headline-sm font-headline-sm text-on-surface"
                    >
                        {{
                            mode === 'create'
                                ? 'Crear Nuevo Proyecto'
                                : 'Editar Proyecto'
                        }}
                    </h3>
                    <p
                        class="text-label-xs font-label-xs text-on-surface-variant"
                    >
                        {{
                            mode === 'create'
                                ? 'Define la iniciativa y asígnala al ciclo de trabajo.'
                                : 'Actualiza el nombre y la descripción.'
                        }}
                    </p>
                </div>
            </div>
            <button
                type="button"
                aria-label="Cerrar"
                class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                @click="emit('close')"
            >
                <AppIcon name="close" :size="20" />
            </button>
        </div>

        <form
            class="gap-space-md px-space-lg py-space-md flex flex-col"
            @submit.prevent="submit"
        >
            <div class="flex flex-col gap-2">
                <span
                    class="text-label-sm font-label-sm text-on-surface font-medium"
                >
                    Identidad Visual
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        v-model="form.key"
                        type="text"
                        maxlength="5"
                        placeholder="CLAVE"
                        aria-label="Clave del proyecto"
                        class="placeholder:text-on-surface-variant/50 bg-surface-container-lowest text-body-sm font-body-sm shadow-card w-20 rounded-lg border-none px-3 py-1.5 text-center uppercase"
                        :class="form.errors.key ? 'ring-error ring-2' : ''"
                        @input="onKeyInput"
                    />
                    <input
                        v-model="form.target_date"
                        type="date"
                        aria-label="Fecha objetivo"
                        class="bg-surface-container-lowest text-body-sm font-body-sm text-on-surface shadow-card rounded-lg border-none px-3 py-1.5"
                        :class="
                            form.errors.target_date ? 'ring-error ring-2' : ''
                        "
                    />
                </div>
                <p
                    v-if="form.errors.key"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.key }}
                </p>
                <p
                    v-if="form.errors.target_date"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.target_date }}
                </p>
                <div
                    class="flex flex-wrap items-center gap-1.5"
                    role="radiogroup"
                    aria-label="Color del proyecto"
                >
                    <button
                        v-for="color in PROJECT_PALETTE"
                        :key="color"
                        type="button"
                        role="radio"
                        :aria-checked="form.color === color"
                        :aria-label="`Color ${color}`"
                        class="h-6 w-6 rounded-full transition-transform"
                        :class="
                            form.color === color
                                ? 'ring-on-surface scale-110 ring-2'
                                : 'hover:scale-110'
                        "
                        :style="{ backgroundColor: color }"
                        @click="
                            form.color = form.color === color ? null : color
                        "
                    />
                </div>
                <p
                    v-if="form.errors.color"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.color }}
                </p>
                <div
                    class="grid w-fit grid-cols-4 gap-1.5"
                    role="radiogroup"
                    aria-label="Icono del proyecto"
                >
                    <button
                        v-for="icon in PROJECT_ICONS"
                        :key="icon"
                        type="button"
                        role="radio"
                        :aria-checked="form.icon === icon"
                        :aria-label="`Icono ${icon}`"
                        class="text-on-surface-variant hover:bg-surface-container flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                        :class="
                            form.icon === icon
                                ? 'bg-surface-container text-on-surface ring-on-surface ring-2'
                                : ''
                        "
                        @click="
                            form.icon = form.icon === icon ? null : icon
                        "
                    >
                        <AppIcon :name="icon" :size="18" />
                    </button>
                </div>
                <p
                    v-if="form.errors.icon"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.icon }}
                </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label
                    for="project-title"
                    class="text-label-sm font-label-sm text-on-surface"
                >
                    Nombre del proyecto
                </label>
                <input
                    id="project-title"
                    ref="titleInput"
                    v-model="form.title"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="Ej. Rediseño de Motor de Búsqueda"
                    class="focus:ring-primary/20 bg-surface-container-lowest text-body-sm font-body-sm text-on-surface shadow-card w-full rounded-lg border-none px-3 py-2 focus:ring-2 focus:outline-none"
                    :class="form.errors.title ? 'ring-error ring-2' : ''"
                />
                <p
                    v-if="form.errors.title"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.title }}
                </p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label
                    for="project-description"
                    class="text-label-sm font-label-sm text-on-surface"
                >
                    Descripción breve
                </label>
                <RichTextEditor
                    id="project-description"
                    ref="descriptionEditor"
                    :model-value="form.description"
                    placeholder="Objetivo principal y alcance de este proyecto..."
                    :error="form.errors.description"
                />
            </div>

            <div
                v-if="mode === 'edit'"
                class="border-error-container px-space-md py-space-sm flex items-center justify-between rounded-lg border"
            >
                <div>
                    <p
                        class="text-label-sm font-label-sm text-on-surface font-medium"
                    >
                        Eliminar proyecto
                    </p>
                    <p
                        class="text-label-xs font-label-xs text-on-surface-variant"
                    >
                        Acción permanente, solo para el líder.
                    </p>
                </div>
                <button
                    type="button"
                    class="text-error hover:bg-error-container/50 text-label-sm font-label-sm rounded-lg px-3 py-1.5 transition-colors"
                    @click="removeProject"
                >
                    Eliminar
                </button>
            </div>

            <div class="gap-space-sm pt-space-md flex items-center justify-end">
                <button
                    type="button"
                    class="text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg px-4 py-1.5 transition-colors"
                    @click="emit('close')"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary hover:bg-primary-container text-label-sm font-label-sm text-on-primary gap-space-xs shadow-card flex items-center rounded-lg px-4 py-1.5 transition-colors disabled:opacity-50"
                >
                    <AppIcon
                        v-if="form.processing"
                        name="progress_activity"
                        :size="16"
                    />
                    {{
                        form.processing
                            ? 'Guardando...'
                            : mode === 'create'
                              ? 'Crear Proyecto'
                              : 'Guardar cambios'
                    }}
                </button>
            </div>
        </form>
    </Modal>
</template>
