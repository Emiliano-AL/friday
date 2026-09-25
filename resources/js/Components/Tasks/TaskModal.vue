<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { update as updateTask } from '@/routes/projects/tasks';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import type { TaskItem } from './types';

const props = defineProps<{
    show: boolean;
    task: TaskItem | null;
    projectId: number;
    members: { id: number; name: string }[];
    sprints: { id: number; name: string }[];
}>();

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    description: '',
    type: 'other',
    priority: 'medium',
    status: 'backlog',
    assignee_id: '',
    sprint_id: '',
});

watch(
    () => props.task,
    (task) => {
        if (!task) {
            return;
        }

        form.title = task.title;
        form.description = task.description ?? '';
        form.type = task.type;
        form.priority = task.priority;
        form.status = task.status;
        form.assignee_id = task.assignee ? String(task.assignee.id) : '';
        form.sprint_id = task.sprint ? String(task.sprint.id) : '';
        form.clearErrors();
    },
);

const close = () => {
    emit('close');
};

const submit = () => {
    if (!props.task) {
        return;
    }

    form.transform((data) => ({
        ...data,
        assignee_id: data.assignee_id === '' ? null : data.assignee_id,
        sprint_id: data.sprint_id === '' ? null : data.sprint_id,
    })).put(updateTask.url([props.projectId, props.task.id]), {
        onSuccess: close,
        preserveScroll: true,
    });
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Editar tarea</h2>

            <form class="mt-4 space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="edit-task-title" value="Título" />
                    <TextInput
                        id="edit-task-title"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.title"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.title" />
                </div>

                <div>
                    <InputLabel
                        for="edit-task-description"
                        value="Descripción (opcional, markdown)"
                    />
                    <TextInput
                        id="edit-task-description"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.description"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.description"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="edit-task-type" value="Tipo" />
                        <select
                            id="edit-task-type"
                            v-model="form.type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="other">Otro</option>
                            <option value="feature">Feature</option>
                            <option value="bug">Bug</option>
                            <option value="test">Test</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.type" />
                    </div>
                    <div>
                        <InputLabel
                            for="edit-task-priority"
                            value="Prioridad"
                        />
                        <select
                            id="edit-task-priority"
                            v-model="form.priority"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="medium">Media</option>
                            <option value="low">Baja</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.priority"
                        />
                    </div>
                    <div>
                        <InputLabel for="edit-task-status" value="Estado" />
                        <select
                            id="edit-task-status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="backlog">Backlog</option>
                            <option value="todo">Por hacer</option>
                            <option value="in_progress">En progreso</option>
                            <option value="in_review">En revisión</option>
                            <option value="done">Hecho</option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.status"
                        />
                    </div>
                    <div>
                        <InputLabel
                            for="edit-task-assignee"
                            value="Responsable"
                        />
                        <select
                            id="edit-task-assignee"
                            v-model="form.assignee_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">Sin responsable</option>
                            <option
                                v-for="member in members"
                                :key="member.id"
                                :value="String(member.id)"
                            >
                                {{ member.name }}
                            </option>
                        </select>
                        <InputError
                            class="mt-2"
                            :message="form.errors.assignee_id"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel for="edit-task-sprint" value="Sprint" />
                    <select
                        id="edit-task-sprint"
                        v-model="form.sprint_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    >
                        <option value="">Sin sprint</option>
                        <option
                            v-for="sprint in sprints"
                            :key="sprint.id"
                            :value="String(sprint.id)"
                        >
                            {{ sprint.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.sprint_id" />
                </div>

                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="close">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Guardar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
