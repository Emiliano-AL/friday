<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import RichTextContent from '@/Components/RichText/RichTextContent.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import type { SprintDetail, SprintStatusValue } from '@/types/sprint';
import { index as projectsIndex, show as projectShow } from '@/routes/projects';

const props = defineProps<{
    sprint: SprintDetail;
}>();

const emit = defineEmits<{
    edit: [];
    addTask: [];
    start: [];
    complete: [];
}>();

const statusTone = (
    status: SprintStatusValue,
): 'primary' | 'neutral' | 'outline' => {
    switch (status) {
        case 'active':
            return 'primary';
        case 'completed':
            return 'outline';
        default:
            return 'neutral';
    }
};

const formattedRange = (start: string, end: string): string => {
    const format = (value: string) =>
        new Date(`${value}T00:00:00`).toLocaleDateString('es-MX', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });

    return `${format(start)} – ${format(end)}`;
};
</script>

<template>
    <div class="gap-space-md flex flex-col">
        <nav
            class="text-label-sm font-label-sm text-on-surface-variant flex items-center gap-1.5"
            aria-label="Migas de pan"
        >
            <a
                :href="projectsIndex.url()"
                class="hover:text-on-surface transition-colors"
                >Proyectos</a
            >
            <span aria-hidden="true">/</span>
            <a
                :href="projectShow.url(sprint.project.id)"
                class="hover:text-on-surface transition-colors"
                >{{ sprint.project.title }}</a
            >
            <span aria-hidden="true">/</span>
            <a
                :href="projectShow.url(sprint.project.id)"
                class="hover:text-on-surface transition-colors"
                >Sprints</a
            >
            <span aria-hidden="true">/</span>
            <span class="text-on-surface font-medium">{{ sprint.name }}</span>
        </nav>

        <div
            class="bg-surface-container-lowest shadow-card p-space-lg flex flex-wrap items-center justify-between gap-4 rounded-xl"
        >
            <div class="gap-space-sm flex min-w-0 flex-col">
                <div class="flex flex-wrap items-center gap-2">
                    <h1
                        class="text-headline-lg font-headline-lg text-on-surface truncate"
                    >
                        {{ sprint.name }}
                    </h1>
                    <UiBadge
                        :label="sprint.statusLabel"
                        :tone="statusTone(sprint.status)"
                    />
                    <span
                        class="text-label-xs font-label-xs text-on-surface-variant"
                    >
                        Iteración {{ sprint.iteration.current }} de
                        {{ sprint.iteration.total }}
                    </span>
                </div>

                <div
                    class="text-body-sm font-body-sm text-on-surface-variant flex flex-wrap items-center gap-x-3 gap-y-1"
                >
                    <span class="flex items-center gap-1">
                        <AppIcon name="calendar_today" :size="14" />
                        {{ formattedRange(sprint.startDate, sprint.endDate) }}
                    </span>
                    <span aria-hidden="true">•</span>
                    <span
                        >Quedan {{ sprint.daysRemaining }} días laborables ({{
                            sprint.percentElapsed
                        }}% del tiempo transcurrido)</span
                    >
                </div>

                <RichTextContent
                    v-if="sprint.goal"
                    :content="sprint.goal"
                    class="border-surface-container-low text-on-surface-variant mt-1 max-w-2xl border-l-2 pl-3 italic"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="sprint.canWrite && sprint.status === 'planned'"
                    type="button"
                    class="px-space-md py-space-sm border-outline-variant text-on-surface-variant hover:bg-surface-container font-label-md text-label-md rounded-lg border transition-colors"
                    @click="emit('start')"
                >
                    Activar sprint
                </button>
                <button
                    v-if="sprint.canWrite && sprint.status === 'active'"
                    type="button"
                    class="px-space-md py-space-sm border-outline-variant text-on-surface-variant hover:bg-surface-container font-label-md text-label-md rounded-lg border transition-colors"
                    @click="emit('complete')"
                >
                    Completar Sprint
                </button>
                <button
                    v-if="sprint.canWrite"
                    type="button"
                    class="px-space-md py-space-sm border-outline-variant text-on-surface-variant hover:bg-surface-container font-label-md text-label-md flex items-center gap-1.5 rounded-lg border transition-colors"
                    @click="emit('edit')"
                >
                    <AppIcon name="edit" :size="15" />
                    Editar Sprint
                </button>
                <button
                    v-if="sprint.canWrite"
                    type="button"
                    class="bg-primary-container hover:bg-primary px-space-md py-space-sm font-label-md text-label-md shadow-primary-container/30 flex items-center gap-1.5 rounded-lg text-white shadow-md transition-colors"
                    @click="emit('addTask')"
                >
                    <AppIcon name="add" :size="15" />
                    Añadir Tarea
                </button>
            </div>
        </div>
    </div>
</template>
