<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { TaskPriorityValue, TaskProjectOption } from '@/types/task';
import { store as storeTask } from '@/routes/tasks';
import { useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps<{
    projects: TaskProjectOption[];
}>();

const titleInput = ref<HTMLInputElement | null>(null);

const priorityOptions: {
    value: TaskPriorityValue;
    label: string;
    icon: string;
}[] = [
    { value: 'low', label: 'Baja', icon: 'south' },
    { value: 'medium', label: 'Media', icon: 'drag_handle' },
    { value: 'high', label: 'Alta', icon: 'priority_high' },
    { value: 'urgent', label: 'Urgente', icon: 'emergency' },
];

const form = useForm({
    title: '',
    status: 'backlog' as const,
    priority: null as TaskPriorityValue | null,
    project_id: null as number | null,
});

form.transform((data) => {
    const payload: Record<string, unknown> = {
        title: data.title,
        status: data.status,
        project_id: data.project_id,
    };

    if (data.priority !== null) {
        payload.priority = data.priority;
    }

    return payload;
});

function togglePriority(value: TaskPriorityValue): void {
    form.priority = form.priority === value ? null : value;
    titleInput.value?.focus();
}

function submit(): void {
    if (form.processing) {
        return;
    }

    form.post(storeTask.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('title');
            form.clearErrors();
            titleInput.value?.focus();
        },
        onError: () => {
            titleInput.value?.focus();
        },
    });
}

onMounted(() => titleInput.value?.focus());
</script>

<template>
    <form class="gap-space-xs flex flex-col" @submit.prevent="submit">
        <div
            class="border-outline-variant/40 bg-surface-container-lowest shadow-card focus-within:border-primary/50 focus-within:ring-primary/15 flex items-center gap-3 rounded-xl border px-space-md py-3 transition-[border-color,box-shadow] focus-within:ring-4"
        >
            <AppIcon
                name="bolt"
                :size="18"
                class="text-primary shrink-0"
                aria-hidden="true"
            />
            <label class="sr-only" for="quick-capture-title"
                >Captura rápida</label
            >
            <input
                id="quick-capture-title"
                ref="titleInput"
                v-model="form.title"
                type="text"
                maxlength="255"
                placeholder="Captura un pendiente y pulsa Enter..."
                class="text-body-lg font-body-lg placeholder:text-on-surface-variant/60 text-on-surface min-w-0 flex-1 border-0 bg-transparent p-0 shadow-none focus:ring-0 focus:outline-none"
                :aria-invalid="form.errors.title ? 'true' : undefined"
            />
            <kbd
                class="text-label-xs font-label-xs border-outline-variant/40 text-on-surface-variant hidden shrink-0 items-center gap-1 rounded border px-1.5 py-0.5 sm:flex"
            >
                <AppIcon name="keyboard_return" :size="12" />
                Enter
            </kbd>
        </div>

        <div class="gap-space-sm flex flex-wrap items-center px-space-xs">
            <div
                class="bg-surface-container-low flex items-center rounded-lg p-0.5"
                role="group"
                aria-label="Prioridad"
            >
                <button
                    v-for="option in priorityOptions"
                    :key="option.value"
                    type="button"
                    :aria-pressed="form.priority === option.value"
                    class="text-label-sm font-label-sm flex items-center gap-1 rounded-md px-2.5 py-1 transition-colors"
                    :class="
                        form.priority === option.value
                            ? 'bg-surface-container-lowest text-on-surface shadow-card font-semibold'
                            : 'text-on-surface-variant hover:text-on-surface'
                    "
                    @click="togglePriority(option.value)"
                >
                    <AppIcon :name="option.icon" :size="13" />
                    {{ option.label }}
                </button>
            </div>

            <label class="sr-only" for="quick-capture-project"
                >Proyecto (opcional)</label
            >
            <div class="relative flex items-center">
                <AppIcon
                    name="folder"
                    :size="14"
                    class="text-on-surface-variant pointer-events-none absolute left-2.5"
                    aria-hidden="true"
                />
                <select
                    id="quick-capture-project"
                    v-model="form.project_id"
                    class="text-label-sm font-label-sm border-outline-variant/40 bg-surface-container-lowest text-on-surface hover:bg-surface-container-low h-8 cursor-pointer appearance-none rounded-lg border pr-7 pl-8 transition-colors focus:outline-none"
                >
                    <option :value="null">Sin proyecto</option>
                    <option
                        v-for="project in projects"
                        :key="project.id"
                        :value="project.id"
                    >
                        {{ project.title }}
                    </option>
                </select>
                <AppIcon
                    name="keyboard_arrow_down"
                    :size="14"
                    class="text-on-surface-variant pointer-events-none absolute right-2"
                    aria-hidden="true"
                />
            </div>
        </div>

        <p
            v-if="form.errors.title"
            class="text-label-xs font-label-xs text-error px-space-xs"
        >
            {{ form.errors.title }}
        </p>
    </form>
</template>
