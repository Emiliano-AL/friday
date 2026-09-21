<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { create, show } from '@/routes/projects';
import { Head, Link } from '@inertiajs/vue3';

interface ProjectListItem {
    id: number;
    title: string;
    status: string;
    statusLabel: string;
    progress: number;
}

defineProps<{
    projects: ProjectListItem[];
}>();
</script>

<template>
    <Head title="Proyectos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Proyectos
                </h2>
                <Link
                    :href="create.url()"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase hover:bg-gray-700"
                >
                    Nuevo proyecto
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    v-if="projects.length === 0"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-500">
                        Todavía no tienes proyectos. Crea el primero.
                    </div>
                </div>

                <div
                    v-else
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <ul class="divide-y divide-gray-200">
                        <li
                            v-for="project in projects"
                            :key="project.id"
                            class="flex items-center justify-between p-6"
                        >
                            <div>
                                <Link
                                    :href="show.url(project.id)"
                                    class="font-medium text-gray-900 hover:underline"
                                >
                                    {{ project.title }}
                                </Link>
                                <div class="mt-1 text-sm text-gray-500">
                                    {{ project.statusLabel }}
                                </div>
                            </div>
                            <div class="flex w-48 items-center">
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
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
