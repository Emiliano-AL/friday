<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import Modal from '@/Components/Modal.vue';
import { destroy, store, update } from '@/routes/projects';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    mode: 'create' | 'edit';
    project?: { id: number; title: string; description: string | null };
}>();

const emit = defineEmits<{
    close: [];
}>();

const titleInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    title: '',
    description: '',
});

watch(
    () => props.open,
    async (open) => {
        if (open) {
            form.title = props.project?.title ?? '';
            form.description = props.project?.description ?? '';
            form.clearErrors();
            await nextTick();
            titleInput.value?.focus();
        }
    },
);

function submit(): void {
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
                <div class="flex items-center justify-between">
                    <span
                        class="text-label-sm font-label-sm text-on-surface font-medium"
                    >
                        Identidad Visual
                    </span>
                    <UiBadge label="Próximamente" tone="outline" />
                </div>
                <div class="flex flex-wrap items-center gap-2 opacity-70">
                    <div class="flex items-center gap-1" aria-hidden="true">
                        <span
                            v-for="color in [
                                '#5b5bd6',
                                '#2563eb',
                                '#10b981',
                                '#f59e0b',
                            ]"
                            :key="color"
                            class="h-6 w-6 rounded-full"
                            :style="{ backgroundColor: color }"
                        />
                        <span
                            class="text-label-xs font-label-xs text-on-surface-variant"
                        >
                            +3
                        </span>
                    </div>
                    <input
                        type="text"
                        disabled
                        placeholder="CLAVE"
                        aria-label="Clave del proyecto (próximamente)"
                        class="placeholder:text-on-surface-variant/50 bg-surface-container-lowest text-body-sm font-body-sm shadow-card w-20 rounded-lg border-none px-3 py-1.5 text-center uppercase"
                    />
                    <input
                        type="date"
                        disabled
                        aria-label="Fecha objetivo (próximamente)"
                        class="bg-surface-container-lowest text-body-sm font-body-sm text-on-surface-variant shadow-card rounded-lg border-none px-3 py-1.5"
                    />
                </div>
                <p class="text-label-xs font-label-xs text-on-surface-variant">
                    Clave, color de acento, icono y fecha objetivo estarán
                    disponibles próximamente.
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
                <textarea
                    id="project-description"
                    v-model="form.description"
                    rows="2"
                    placeholder="Objetivo principal y alcance de este proyecto..."
                    class="focus:ring-primary/20 bg-surface-container-lowest text-body-sm font-body-sm text-on-surface shadow-card w-full resize-none rounded-lg border-none px-3 py-2 focus:ring-2 focus:outline-none"
                    :class="form.errors.description ? 'ring-error ring-2' : ''"
                />
                <p
                    v-if="form.errors.description"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.description }}
                </p>
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
