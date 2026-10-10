<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { index as projectsIndex } from '@/routes/projects';
import type { ProjectsPanelPayload } from '@/types/dashboard';

defineProps<{
    projects: ProjectsPanelPayload;
}>();
</script>

<template>
    <section
        aria-label="Proyectos activos"
        class="bg-surface-container-lowest shadow-card flex flex-col rounded-xl"
    >
        <header
            class="px-space-lg pt-space-lg pb-space-md gap-space-md flex items-center justify-between"
        >
            <h2 class="text-headline-sm font-headline-sm text-on-surface">
                Proyectos activos
            </h2>
        </header>

        <ul
            v-if="projects.items.length > 0"
            class="gap-space-md px-space-lg pb-space-lg flex flex-col"
        >
            <li
                v-for="project in projects.items"
                :key="project.id"
                class="gap-space-xs flex flex-col"
            >
                <div class="gap-space-md flex items-center justify-between">
                    <span
                        class="text-body-md font-body-md text-on-surface truncate font-medium"
                    >
                        {{ project.title }}
                    </span>
                    <span
                        class="text-label-xs font-label-xs text-on-surface-variant shrink-0"
                    >
                        {{ project.progress }}%
                    </span>
                </div>
                <div
                    class="bg-surface-container-low h-2 overflow-hidden rounded-full"
                    role="progressbar"
                    :aria-valuenow="project.progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-label="`Avance de ${project.title}`"
                >
                    <div
                        class="bg-primary-container h-full rounded-full transition-all"
                        :style="{ width: `${project.progress}%` }"
                    />
                </div>
                <p class="text-label-xs font-label-xs text-on-surface-variant">
                    {{ project.note }}
                </p>
            </li>
        </ul>
        <p
            v-else
            class="text-body-sm font-body-sm text-on-surface-variant px-space-lg pb-space-lg italic"
        >
            Sin proyectos activos.
        </p>

        <footer
            class="border-surface-container-low px-space-lg py-space-md border-t"
        >
            <Link
                :href="projectsIndex.url()"
                class="text-label-md font-label-md text-primary hover:text-primary-container flex items-center gap-1 transition-colors"
            >
                Ver todos
                <AppIcon name="arrow_forward" :size="14" />
            </Link>
        </footer>
    </section>
</template>
