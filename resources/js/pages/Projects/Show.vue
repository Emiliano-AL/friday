<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { destroy, edit, status, update } from '@/routes/projects';
import {
    store as storeMember,
    destroy as destroyMember,
} from '@/routes/projects/members';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface ProjectMember {
    id: number;
    name: string;
    email: string;
}

interface ProjectPayload {
    id: number;
    title: string;
    description: string | null;
    status: string;
    statusLabel: string;
    progress: number;
    owner: ProjectMember;
    members: ProjectMember[];
    allowedTransitions: string[];
    isOwner: boolean;
}

defineProps<{
    project: ProjectPayload;
}>();

const memberForm = useForm({
    email: '',
});

const transitionLabels: Record<string, string> = {
    active: 'Reactivar',
    archived: 'Archivar',
    completed: 'Completar',
};

const submitMember = (projectId: number) => {
    memberForm.post(storeMember.url(projectId), {
        onSuccess: () => memberForm.reset('email'),
        preserveScroll: true,
    });
};

const removeMember = (projectId: number, userId: number) => {
    router.delete(destroyMember.url([projectId, userId]), {
        preserveScroll: true,
    });
};

const confirmDestroy = (project: ProjectPayload) => {
    if (
        window.confirm(
            `¿Eliminar el proyecto "${project.title}"? Esta acción no se puede deshacer.`,
        )
    ) {
        router.delete(destroy.url(project.id));
    }
};
</script>

<template>
    <Head :title="project.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    {{ project.title }}
                </h2>
                <Link
                    v-if="project.isOwner && project.status === 'active'"
                    :href="edit.url(project.id)"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700"
                >
                    Editar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="project.status !== 'active'"
                    class="rounded-md bg-gray-100 px-4 py-3 text-sm text-gray-700"
                >
                    Este proyecto está {{ project.statusLabel.toLowerCase() }} y
                    es de solo lectura.
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="space-y-4 p-6">
                        <div class="flex items-center justify-between">
                            <span
                                class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700"
                            >
                                {{ project.statusLabel }}
                            </span>
                            <div class="flex w-64 items-center">
                                <div class="h-2 w-full rounded bg-gray-200">
                                    <div
                                        class="h-2 rounded bg-gray-800"
                                        :style="{
                                            width: project.progress + '%',
                                        }"
                                    ></div>
                                </div>
                                <span class="ms-3 text-sm text-gray-600">
                                    {{ project.progress }}%
                                </span>
                            </div>
                        </div>

                        <p v-if="project.description" class="text-gray-700">
                            {{ project.description }}
                        </p>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500">
                                Miembros
                            </h3>
                            <ul class="mt-2 space-y-1">
                                <li class="text-sm text-gray-900">
                                    {{ project.owner.name }}
                                    <span class="text-gray-500"
                                        >(propietario)</span
                                    >
                                </li>
                                <li
                                    v-for="member in project.members"
                                    :key="member.id"
                                    class="flex items-center justify-between text-sm text-gray-900"
                                >
                                    <span>{{ member.name }}</span>
                                    <button
                                        v-if="project.isOwner"
                                        type="button"
                                        class="text-xs text-red-600 hover:underline"
                                        @click="
                                            removeMember(project.id, member.id)
                                        "
                                    >
                                        Retirar
                                    </button>
                                </li>
                                <li
                                    v-if="project.members.length === 0"
                                    class="text-sm text-gray-500"
                                >
                                    Sin colaboradores todavía.
                                </li>
                            </ul>

                            <form
                                v-if="project.isOwner"
                                class="mt-4 flex max-w-md items-start gap-2"
                                @submit.prevent="submitMember(project.id)"
                            >
                                <div class="flex-1">
                                    <TextInput
                                        id="member-email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        v-model="memberForm.email"
                                        placeholder="correo@ejemplo.com"
                                        required
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="memberForm.errors.email"
                                    />
                                </div>
                                <PrimaryButton
                                    class="mt-1"
                                    :class="{
                                        'opacity-25': memberForm.processing,
                                    }"
                                    :disabled="memberForm.processing"
                                >
                                    Añadir
                                </PrimaryButton>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    v-if="project.isOwner"
                    class="flex items-center justify-between"
                >
                    <div class="flex gap-2">
                        <SecondaryButton
                            v-for="target in project.allowedTransitions"
                            :key="target"
                            @click="
                                router.put(status.url(project.id), {
                                    status: target,
                                })
                            "
                        >
                            {{ transitionLabels[target] ?? target }}
                        </SecondaryButton>
                    </div>
                    <DangerButton @click="confirmDestroy(project)">
                        Eliminar proyecto
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
