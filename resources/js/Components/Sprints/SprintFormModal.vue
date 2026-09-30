<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { SprintSummary } from '@/types/project';
import { store, update } from '@/routes/projects/sprints';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    open: boolean;
    project: { id: number; title: string };
    sprint?: SprintSummary;
}>();

const emit = defineEmits<{
    close: [];
}>();

const nameInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    name: '',
    goal: '',
    start_date: '',
    end_date: '',
    start_now: false,
});

function resetForm(): void {
    form.name = props.sprint?.name ?? '';
    form.goal = props.sprint?.goal ?? '';
    form.start_date = props.sprint?.startDate ?? '';
    form.end_date = props.sprint?.endDate ?? '';
    form.start_now = false;
    form.clearErrors();
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            resetForm();
            await nextTick();
            nameInput.value?.focus();
        }
    },
);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    };

    if (props.sprint) {
        form.put(update.url([props.project.id, props.sprint.id]), options);
    } else {
        form.post(store.url(props.project.id), options);
    }
}

const dateError = (key: 'start_date' | 'end_date'): string | undefined =>
    form.errors[key];
</script>

<template>
    <Modal :show="open" max-width="2xl" @close="emit('close')">
        <form
            class="gap-space-lg p-space-lg flex flex-col"
            @submit.prevent="submit"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2
                        class="text-headline-md font-headline-md text-on-surface"
                    >
                        {{ sprint ? 'Editar Sprint' : 'Nuevo Sprint' }}
                    </h2>
                    <p
                        class="text-body-sm font-body-sm text-on-surface-variant mt-1"
                    >
                        {{ project.title }}
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Cerrar modal"
                    class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                    @click="emit('close')"
                >
                    <AppIcon name="close" :size="18" />
                </button>
            </div>

            <div class="flex flex-col gap-1">
                <label
                    for="sprint-name"
                    class="text-label-sm font-label-sm text-on-surface font-medium"
                >
                    Nombre del sprint
                </label>
                <input
                    id="sprint-name"
                    ref="nameInput"
                    v-model="form.name"
                    type="text"
                    maxlength="255"
                    placeholder="Sprint 15 — Integración pasarela"
                    :class="form.errors.name ? 'border-error' : ''"
                    class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-10 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
                />
                <p
                    v-if="form.errors.name"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.name }}
                </p>
            </div>

            <div class="flex flex-col gap-1">
                <label
                    for="sprint-goal"
                    class="text-label-sm font-label-sm text-on-surface font-medium"
                >
                    Objetivo del sprint
                    <span class="text-on-surface-variant font-normal"
                        >(opcional)</span
                    >
                </label>
                <textarea
                    id="sprint-goal"
                    v-model="form.goal"
                    rows="2"
                    maxlength="1000"
                    placeholder="Describe brevemente el valor clave entregable de esta iteración..."
                    :class="form.errors.goal ? 'border-error' : ''"
                    class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary w-full resize-none rounded-lg border px-3 py-2 leading-relaxed shadow-xs focus:ring-2 focus:outline-none"
                />
                <p
                    v-if="form.errors.goal"
                    class="text-label-xs font-label-xs text-error"
                >
                    {{ form.errors.goal }}
                </p>
            </div>

            <div class="gap-space-md grid grid-cols-1 sm:grid-cols-2">
                <div class="flex flex-col gap-1">
                    <label
                        for="sprint-start"
                        class="text-label-sm font-label-sm text-on-surface font-medium"
                    >
                        Fecha de inicio
                    </label>
                    <input
                        id="sprint-start"
                        v-model="form.start_date"
                        type="date"
                        :class="dateError('start_date') ? 'border-error' : ''"
                        class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-10 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
                    />
                    <p
                        v-if="dateError('start_date')"
                        class="text-label-xs font-label-xs text-error"
                    >
                        {{ dateError('start_date') }}
                    </p>
                </div>
                <div class="flex flex-col gap-1">
                    <label
                        for="sprint-end"
                        class="text-label-sm font-label-sm text-on-surface font-medium"
                    >
                        Fecha de fin
                    </label>
                    <input
                        id="sprint-end"
                        v-model="form.end_date"
                        type="date"
                        :class="dateError('end_date') ? 'border-error' : ''"
                        class="text-body-sm font-body-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface focus:ring-primary h-10 w-full rounded-lg border px-3 shadow-xs focus:ring-2 focus:outline-none"
                    />
                    <p
                        v-if="dateError('end_date')"
                        class="text-label-xs font-label-xs text-error"
                    >
                        {{ dateError('end_date') }}
                    </p>
                </div>
            </div>

            <div
                v-if="!sprint"
                class="border-outline-variant/40 bg-surface-container-low/50 gap-space-sm p-space-sm flex flex-col rounded-lg border"
                aria-disabled="true"
            >
                <div class="flex items-center justify-between gap-3">
                    <span
                        class="text-label-sm font-label-sm text-on-surface flex items-center gap-1.5 font-medium"
                    >
                        <AppIcon name="checklist" :size="15" />
                        Planificación de tareas
                    </span>
                    <UiBadge label="Próximamente" tone="outline" />
                </div>
                <div class="flex items-center gap-2">
                    <AppIcon
                        name="lock"
                        :size="14"
                        class="text-on-surface-variant shrink-0"
                    />
                    <p
                        class="text-label-xs font-label-xs text-on-surface-variant"
                    >
                        Seleccionar tareas del backlog para arrancar el sprint
                        con alcance definido.
                    </p>
                </div>
            </div>

            <label
                v-if="!sprint"
                class="hover:bg-surface-container-low/60 p-space-sm flex cursor-pointer items-center justify-between rounded-lg border border-transparent transition-colors"
            >
                <span class="flex flex-col">
                    <span
                        class="text-label-md font-label-md text-on-surface font-medium"
                    >
                        Comenzar inmediatamente
                    </span>
                    <span
                        class="text-label-xs font-label-xs text-on-surface-variant"
                    >
                        Estado por defecto: Planificado
                    </span>
                </span>
                <input
                    v-model="form.start_now"
                    type="checkbox"
                    class="border-outline-variant bg-surface-container-lowest text-primary-container focus:ring-primary/30 h-4 w-4 rounded border focus:ring-2"
                />
            </label>

            <div class="flex items-center justify-end gap-2">
                <button
                    type="button"
                    class="px-space-md py-space-sm bg-surface-container-low text-on-surface hover:bg-surface-container font-label-md text-label-md rounded-lg transition-colors"
                    @click="emit('close')"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    :disabled="form.processing"
                    :class="{ 'opacity-60': form.processing }"
                    class="bg-primary-container hover:bg-primary px-space-lg py-space-sm shadow-primary-container/30 font-label-md text-label-md flex items-center gap-1.5 rounded-lg text-white shadow-md transition-colors"
                >
                    {{ sprint ? 'Guardar cambios' : 'Crear Sprint' }}
                </button>
            </div>
        </form>
    </Modal>
</template>
