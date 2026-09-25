<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { store as storeComment } from '@/routes/projects/tasks/comments';
import { useForm } from '@inertiajs/vue3';
import type { TaskItem } from './types';

const props = defineProps<{
    show: boolean;
    task: TaskItem | null;
    projectId: number;
    canComment: boolean;
}>();

const emit = defineEmits(['close']);

const form = useForm({
    body: '',
});

const close = () => {
    emit('close');
};

const submit = () => {
    if (!props.task) {
        return;
    }

    form.post(storeComment.url([props.projectId, props.task.id]), {
        onSuccess: () => form.reset('body'),
        preserveScroll: true,
    });
};
</script>

<template>
    <Modal :show="show" @close="close">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Comentarios{{ task ? ` — ${task.title}` : '' }}
            </h2>

            <ul class="mt-4 max-h-64 space-y-3 overflow-y-auto">
                <li
                    v-for="comment in task?.comments ?? []"
                    :key="comment.id"
                    class="text-sm"
                >
                    <span class="font-medium text-gray-900">{{
                        comment.author.name
                    }}</span>
                    <span class="text-xs text-gray-500">
                        · {{ new Date(comment.createdAt).toLocaleString('es') }}
                    </span>
                    <p class="text-gray-700">{{ comment.body }}</p>
                </li>
                <li
                    v-if="!task || task.comments.length === 0"
                    class="text-sm text-gray-500"
                >
                    Sin comentarios todavía.
                </li>
            </ul>

            <form v-if="canComment" class="mt-4" @submit.prevent="submit">
                <InputLabel for="comment-body" value="Añadir comentario" />
                <textarea
                    id="comment-body"
                    v-model="form.body"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    required
                ></textarea>
                <InputError class="mt-2" :message="form.errors.body" />

                <div class="mt-3 flex justify-end">
                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Comentar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
