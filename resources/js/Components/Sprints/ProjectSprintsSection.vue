<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import { show as showSprint } from '@/routes/projects/sprints';
import type { ProjectDetail, SprintSummary } from '@/types/project';
import type { BoardTask } from '@/types/task';
import { sprintMetrics, weekdayDaysRemaining } from '@/utils/sprint';

const props = defineProps<{
    project: ProjectDetail;
    tasks: BoardTask[];
    canWriteSprints: boolean;
}>();

const emit = defineEmits<{
    create: [];
    edit: [sprint: SprintSummary];
    destroy: [sprint: SprintSummary];
    complete: [];
}>();

type SprintFilter = 'all' | 'active' | 'planned' | 'completed';

const filter = ref<SprintFilter>('all');
const othersCollapsed = ref(false);

const counts = computed(() => ({
    active: props.project.sprints.filter((s) => s.status === 'active').length,
    planned: props.project.sprints.filter((s) => s.status === 'planned').length,
    completed: props.project.sprints.filter((s) => s.status === 'completed')
        .length,
}));

const chips: Array<{ key: SprintFilter; label: string }> = [
    { key: 'all', label: 'Todos' },
    { key: 'active', label: 'Activo' },
    { key: 'planned', label: 'Planificados' },
    { key: 'completed', label: 'Completados' },
];

function chipLabel(chip: { key: SprintFilter; label: string }): string {
    if (chip.key === 'all') {
        return chip.label;
    }

    return `${chip.label} (${counts.value[chip.key]})`;
}

const others = computed(() =>
    props.project.sprints.filter(
        (sprint) =>
            sprint.id !== props.project.activeSprint?.id &&
            (filter.value === 'all' || sprint.status === filter.value),
    ),
);

const emptyFilterMessage = computed(() => {
    if (filter.value === 'all') {
        return 'Sin sprints en este filtro.';
    }

    const label =
        filter.value === 'planned'
            ? 'planificados'
            : filter.value === 'completed'
              ? 'completados'
              : 'activos';

    return `No hay sprints ${label} en este proyecto.`;
});

const active = computed(() => props.project.activeSprint);

const activeMetrics = computed(() =>
    active.value === null
        ? { total: 0, done: 0, coverage: 0 }
        : sprintMetrics(active.value, props.tasks),
);

const activeDaysRemaining = computed(() =>
    active.value === null
        ? 0
        : weekdayDaysRemaining(new Date(), active.value.endDate),
);

const dayFormatter = new Intl.DateTimeFormat('es-MX', {
    day: '2-digit',
    month: 'short',
});

function formatDay(value: string): string {
    return dayFormatter.format(new Date(`${value}T00:00:00`)).replace('.', '');
}

function formatRange(sprint: SprintSummary): string {
    return `${formatDay(sprint.startDate)} — ${formatDay(sprint.endDate)}, ${new Date(`${sprint.endDate}T00:00:00`).getFullYear()}`;
}

function metricsFor(sprint: SprintSummary) {
    return sprintMetrics(sprint, props.tasks);
}

function statusTone(
    status: SprintSummary['status'],
): 'primary' | 'neutral' | 'outline' {
    switch (status) {
        case 'active':
            return 'primary';
        case 'completed':
            return 'neutral';
        default:
            return 'outline';
    }
}
</script>

<template>
    <section
        aria-label="Sprints"
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-md flex flex-col rounded-xl"
    >
        <div class="gap-space-md flex flex-wrap items-center justify-between">
            <div class="flex items-baseline gap-2">
                <h2 class="text-headline-sm font-headline-sm text-on-surface">
                    Sprints
                </h2>
                <span
                    class="text-label-sm text-on-surface-variant tabular-nums"
                >
                    {{ project.sprints.length }}
                </span>
            </div>
            <button
                v-if="canWriteSprints"
                type="button"
                class="border-outline-variant/60 text-on-surface hover:bg-surface-container text-label-md font-label-md flex items-center gap-1.5 rounded-lg border px-3 py-1.5 transition-colors"
                @click="emit('create')"
            >
                <AppIcon name="add" :size="16" />
                Planificar sprint
            </button>
        </div>

        <div
            v-if="project.sprints.length > 0"
            class="gap-space-xs flex flex-wrap"
        >
            <button
                v-for="chip in chips"
                :key="chip.key"
                type="button"
                :aria-pressed="filter === chip.key"
                class="text-label-sm font-label-sm rounded-lg px-3 py-1.5 transition-colors"
                :class="
                    filter === chip.key
                        ? 'bg-primary-fixed text-on-primary-fixed'
                        : 'text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                "
                @click="filter = chip.key"
            >
                {{ chipLabel(chip) }}
            </button>
        </div>

        <div
            v-if="project.sprints.length === 0"
            class="gap-space-sm py-space-md flex flex-col items-start"
        >
            <p class="text-body-sm font-body-sm text-on-surface-variant">
                Sin sprints todavía. Planifica el primero para organizar el
                trabajo por iteraciones.
            </p>
        </div>

        <template v-else>
            <article
                v-if="active"
                class="gap-space-md p-space-md flex flex-col rounded-xl border border-(--project-accent)/30 bg-(--project-accent-soft)"
            >
                <header class="gap-space-sm flex flex-wrap items-start">
                    <div class="min-w-0 flex-1 basis-full sm:basis-0">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <Link
                                :href="showSprint.url([project.id, active.id])"
                                class="text-on-surface hover:text-primary text-headline-sm font-headline-sm truncate transition-colors"
                            >
                                {{ active.name }}
                            </Link>
                            <UiBadge label="Activo" tone="primary" dot />
                        </div>
                        <p class="text-label-sm text-on-surface-variant mt-0.5">
                            {{ formatRange(active) }} ·
                            {{
                                activeDaysRemaining === 1
                                    ? '1 día restante'
                                    : `${activeDaysRemaining} días restantes`
                            }}
                        </p>
                    </div>
                    <div class="gap-space-xs flex items-center">
                        <button
                            v-if="canWriteSprints"
                            type="button"
                            class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface text-label-md font-label-md inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                            @click="emit('complete')"
                        >
                            <AppIcon name="check_circle" :size="16" />
                            Completar
                        </button>
                        <Link
                            :href="showSprint.url([project.id, active.id])"
                            class="bg-primary/10 text-primary hover:bg-primary/15 text-label-md font-label-md inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition-colors"
                        >
                            <AppIcon name="dashboard" :size="16" />
                            Ver tablero
                        </Link>
                    </div>
                </header>

                <p
                    v-if="active.goal"
                    class="text-body-md font-body-md text-on-surface line-clamp-3 max-w-3xl"
                >
                    <span class="text-on-surface-variant">Meta:</span>
                    {{ active.goal }}
                </p>

                <div class="gap-space-xs flex flex-col">
                    <div class="flex items-baseline justify-between gap-2">
                        <span
                            class="text-label-md font-label-md text-on-surface font-semibold tabular-nums"
                        >
                            {{ activeMetrics.coverage }}%
                        </span>
                        <span
                            class="text-label-sm text-on-surface-variant tabular-nums"
                        >
                            {{ activeMetrics.done }} de
                            {{ activeMetrics.total }} tareas
                        </span>
                    </div>
                    <div
                        class="bg-surface-container-high h-1.5 overflow-hidden rounded-full"
                        role="progressbar"
                        :aria-valuenow="activeMetrics.coverage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`Progreso de ${active.name}`"
                    >
                        <div
                            class="h-full rounded-full bg-(--project-accent) transition-all"
                            :style="{ width: `${activeMetrics.coverage}%` }"
                        />
                    </div>
                </div>
            </article>

            <div
                v-if="
                    counts.active + counts.planned + counts.completed >
                    (active ? 1 : 0)
                "
                class="gap-space-xs flex flex-col"
            >
                <div class="flex items-center justify-between gap-2">
                    <h3
                        class="text-label-md font-label-md text-on-surface font-semibold"
                    >
                        Otros sprints
                    </h3>
                    <button
                        type="button"
                        class="text-label-xs font-label-xs text-on-surface-variant hover:text-on-surface inline-flex items-center gap-1 transition-colors"
                        :aria-expanded="!othersCollapsed"
                        @click="othersCollapsed = !othersCollapsed"
                    >
                        <AppIcon
                            :name="
                                othersCollapsed ? 'expand_more' : 'expand_less'
                            "
                            :size="14"
                        />
                        {{ othersCollapsed ? 'Mostrar' : 'Ocultar' }}
                    </button>
                </div>

                <ul
                    v-show="!othersCollapsed"
                    class="border-surface-container flex max-h-80 flex-col overflow-y-auto rounded-lg border"
                >
                    <li
                        v-for="sprint in others"
                        :key="sprint.id"
                        class="border-surface-container gap-space-md px-space-sm py-space-sm flex items-center border-b last:border-b-0"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-center gap-2">
                                <Link
                                    :href="
                                        showSprint.url([project.id, sprint.id])
                                    "
                                    class="text-on-surface hover:text-primary text-body-md font-body-md truncate font-medium transition-colors"
                                >
                                    {{ sprint.name }}
                                </Link>
                                <UiBadge
                                    :label="sprint.statusLabel"
                                    :tone="statusTone(sprint.status)"
                                />
                            </div>
                            <p
                                v-if="sprint.goal"
                                class="text-label-sm text-on-surface-variant truncate"
                                :title="sprint.goal"
                            >
                                {{ sprint.goal }}
                            </p>
                        </div>

                        <span
                            class="text-label-sm text-on-surface-variant hidden w-44 shrink-0 md:inline"
                        >
                            {{ formatRange(sprint) }}
                        </span>

                        <span
                            class="hidden w-32 shrink-0 items-center gap-2 sm:flex"
                            :title="
                                sprint.status === 'planned'
                                    ? `${metricsFor(sprint).total} tareas planificadas`
                                    : `${metricsFor(sprint).done} de ${metricsFor(sprint).total} tareas`
                            "
                        >
                            <span
                                class="bg-surface-container-high h-1.5 flex-1 overflow-hidden rounded-full"
                            >
                                <span
                                    class="block h-full rounded-full bg-(--project-accent)"
                                    :style="{
                                        width: `${metricsFor(sprint).coverage}%`,
                                    }"
                                />
                            </span>
                            <span
                                class="text-label-xs font-label-xs text-on-surface-variant w-9 text-right tabular-nums"
                            >
                                {{ metricsFor(sprint).coverage }}%
                            </span>
                        </span>

                        <span class="flex w-16 shrink-0 justify-end gap-0.5">
                            <template
                                v-if="
                                    sprint.status === 'planned' &&
                                    canWriteSprints
                                "
                            >
                                <button
                                    type="button"
                                    :aria-label="`Editar ${sprint.name}`"
                                    title="Editar"
                                    class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                                    @click="emit('edit', sprint)"
                                >
                                    <AppIcon name="edit" :size="16" />
                                </button>
                                <button
                                    type="button"
                                    :aria-label="`Eliminar ${sprint.name}`"
                                    title="Eliminar"
                                    class="text-on-surface-variant hover:bg-error-container/50 hover:text-error flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                                    @click="emit('destroy', sprint)"
                                >
                                    <AppIcon name="delete" :size="16" />
                                </button>
                            </template>
                            <Link
                                v-else
                                :href="showSprint.url([project.id, sprint.id])"
                                :aria-label="`Ver ${sprint.name}`"
                                title="Ver resumen"
                                class="text-on-surface-variant hover:bg-surface-container hover:text-on-surface flex h-7 w-7 items-center justify-center rounded-lg transition-colors"
                            >
                                <AppIcon name="arrow_forward" :size="16" />
                            </Link>
                        </span>
                    </li>
                    <li
                        v-if="others.length === 0"
                        class="text-body-sm font-body-sm text-on-surface-variant px-space-sm py-space-sm"
                    >
                        {{ emptyFilterMessage }}
                    </li>
                </ul>
            </div>
        </template>
    </section>
</template>
