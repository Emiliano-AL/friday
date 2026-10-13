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
            class="px-space-lg pt-space-lg pb-space-sm gap-space-md flex items-start"
        >
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition-colors"
                :class="form.color ? '' : 'bg-primary/10 text-primary'"
                :style="
                    form.color
                        ? {
                              backgroundColor: `${form.color}1A`,
                              color: form.color,
                          }
                        : undefined
                "
                aria-hidden="true"
            >
                <AppIcon :name="form.icon ?? 'folder'" :size="20" />
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-headline-sm font-headline-sm text-on-surface">
                    {{
                        mode === 'create'
                            ? 'Crear Nuevo Proyecto'
                            : 'Editar Proyecto'
                    }}
                </h3>
                <p class="text-body-sm font-body-sm text-on-surface-variant">
                    {{
                        mode === 'create'
                            ? 'Define la iniciativa y asígnala al ciclo de trabajo.'
                            : 'Actualiza los datos y la identidad del proyecto.'
                    }}
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

        <form class="flex flex-col" @submit.prevent="submit">
            <div class="gap-space-lg px-space-lg py-space-md flex flex-col">
                <div class="gap-space-md grid grid-cols-[1fr_6.5rem]">
                    <div class="flex min-w-0 flex-col gap-1.5">
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
                            class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-primary/20 h-9 w-full rounded-lg border px-3 transition-colors focus:ring-2 focus:outline-none"
                            :class="
                                form.errors.title
                                    ? 'border-error ring-error/20 ring-2'
                                    : ''
                            "
                        />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label
                            for="project-key"
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Clave
                        </label>
                        <input
                            id="project-key"
                            v-model="form.key"
                            type="text"
                            maxlength="5"
                            placeholder="FRN"
                            class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-primary/20 h-9 w-full rounded-lg border px-3 transition-colors focus:ring-2 focus:outline-none tracking-wide uppercase"
                            :class="
                                form.errors.key
                                    ? 'border-error ring-error/20 ring-2'
                                    : ''
                            "
                            @input="onKeyInput"
                        />
                    </div>
                    <p
                        v-if="form.errors.title"
                        class="text-label-xs font-label-xs text-error col-span-2 -mt-2"
                    >
                        {{ form.errors.title }}
                    </p>
                    <p
                        v-if="form.errors.key"
                        class="text-label-xs font-label-xs text-error col-span-2 -mt-2"
                    >
                        {{ form.errors.key }}
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
                    class="gap-x-space-md gap-y-space-sm grid grid-cols-[3.5rem_1fr] items-center"
                >
                    <div class="contents">
                        <span
                            id="project-color-label"
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Color
                        </span>
                        <div
                            class="flex flex-wrap items-center gap-2"
                            role="radiogroup"
                            aria-labelledby="project-color-label"
                        >
                            <button
                                v-for="color in PROJECT_PALETTE"
                                :key="color"
                                type="button"
                                role="radio"
                                :aria-checked="form.color === color"
                                :aria-label="`Color ${color}`"
                                class="ring-offset-surface-container-lowest h-6 w-6 rounded-full ring-offset-2 transition-transform"
                                :class="
                                    form.color === color
                                        ? 'ring-on-surface ring-2'
                                        : 'hover:scale-110'
                                "
                                :style="{ backgroundColor: color }"
                                @click="
                                    form.color =
                                        form.color === color ? null : color
                                "
                            />
                        </div>
                        <p
                            v-if="form.errors.color"
                            class="text-label-xs font-label-xs text-error col-start-2"
                        >
                            {{ form.errors.color }}
                        </p>
                    </div>

                    <div class="contents">
                        <span
                            id="project-icon-label"
                            class="text-label-sm font-label-sm text-on-surface"
                        >
                            Icono
                        </span>
                        <div
                            class="flex flex-wrap items-center gap-1"
                            role="radiogroup"
                            aria-labelledby="project-icon-label"
                        >
                            <button
                                v-for="icon in PROJECT_ICONS"
                                :key="icon"
                                type="button"
                                role="radio"
                                :aria-checked="form.icon === icon"
                                :aria-label="`Icono ${icon}`"
                                class="flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                                :class="
                                    form.icon === icon
                                        ? 'bg-primary/10 text-primary ring-primary ring-1'
                                        : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'
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
                            class="text-label-xs font-label-xs text-error col-start-2"
                        >
                            {{ form.errors.icon }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label
                        for="project-target-date"
                        class="text-label-sm font-label-sm text-on-surface"
                    >
                        Fecha objetivo
                        <span class="text-on-surface-variant font-normal"
                            >· opcional</span
                        >
                    </label>
                    <input
                        id="project-target-date"
                        v-model="form.target_date"
                        type="date"
                        class="border-outline-variant/60 bg-surface-container-lowest text-body-md font-body-md text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:ring-primary/20 h-9 w-full rounded-lg border px-3 transition-colors focus:ring-2 focus:outline-none sm:w-48"
                        :class="
                            form.errors.target_date
                                ? 'border-error ring-error/20 ring-2'
                                : ''
                        "
                    />
                    <p
                        v-if="form.errors.target_date"
                        class="text-label-xs font-label-xs text-error"
                    >
                        {{ form.errors.target_date }}
                    </p>
                </div>
            </div>

            <div
                class="border-surface-container gap-space-sm px-space-lg py-space-md flex items-center border-t"
            >
                <button
                    v-if="mode === 'edit'"
                    type="button"
                    title="Acción permanente, solo para el líder."
                    class="text-error hover:bg-error-container/50 text-label-sm font-label-sm -ml-3 flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                    @click="removeProject"
                >
                    <AppIcon name="delete" :size="16" />
                    Eliminar
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
                        type="submit"
                        :disabled="form.processing"
                        class="bg-primary hover:bg-primary-container text-label-md font-label-md text-on-primary gap-space-xs shadow-card flex items-center rounded-lg px-4 py-2 transition-colors disabled:opacity-50"
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
            </div>
        </form>
    </Modal>
</template>
