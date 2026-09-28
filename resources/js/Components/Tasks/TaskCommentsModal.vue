<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { store as storeComment } from '@/routes/projects/tasks/comments';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { CommentItem, TaskItem } from './types';

const props = withDefaults(
    defineProps<{
        show: boolean;
        task: TaskItem | null;
        projectId?: number;
        canComment: boolean;
        useGlobal?: boolean;
        endpoints?: { show: string; store: string };
    }>(),
    {
        useGlobal: false,
    },
);

const emit = defineEmits(['close']);

const form = useForm({
    body: '',
});

const globalComments = ref<CommentItem[]>([]);
const globalBody = ref('');
const globalSubmitting = ref(false);

const comments = computed<CommentItem[]>(() =>
    props.useGlobal ? globalComments.value : (props.task?.comments ?? []),
);

const bodyModel = computed<string>({
    get: () => (props.useGlobal ? globalBody.value : form.body),
    set: (value: string) => {
        if (props.useGlobal) {
            globalBody.value = value;
        } else {
            form.body = value;
        }
    },
});

const isProcessing = computed(() =>
    props.useGlobal ? globalSubmitting.value : form.processing,
);

const bodyError = computed(() =>
    props.useGlobal ? undefined : form.errors.body,
);

const close = () => {
    emit('close');
};

watch(
    () => props.show,
    async (visible) => {
        if (!visible || !props.useGlobal || !props.endpoints) {
            return;
        }

        const response = await fetch(props.endpoints.show, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            globalComments.value = data.comments ?? [];
        }
    },
);

const submitGlobal = async () => {
    if (!globalBody.value.trim() || !props.endpoints) {
        return;
    }

    globalSubmitting.value = true;

    try {
        const response = await fetch(props.endpoints.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
                ),
            },
            body: JSON.stringify({ body: globalBody.value }),
        });

        if (response.ok) {
            const data = await response.json();
            globalComments.value = data.comments ?? globalComments.value;
            globalBody.value = '';
        }
    } finally {
        globalSubmitting.value = false;
    }
};

const submit = () => {
    if (props.useGlobal) {
        submitGlobal();

        return;
    }

    if (!props.task || props.useGlobal || props.projectId === undefined) {
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
                    v-for="comment in comments"
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
                <li v-if="comments.length === 0" class="text-sm text-gray-500">
                    Sin comentarios todavía.
                </li>
            </ul>

            <form v-if="canComment" class="mt-4" @submit.prevent="submit">
                <InputLabel for="comment-body" value="Añadir comentario" />
                <textarea
                    id="comment-body"
                    v-model="bodyModel"
                    rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    required
                ></textarea>
                <InputError class="mt-2" :message="bodyError" />

                <div class="mt-3 flex justify-end">
                    <PrimaryButton
                        :class="{ 'opacity-25': isProcessing }"
                        :disabled="isProcessing"
                    >
                        Comentar
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
