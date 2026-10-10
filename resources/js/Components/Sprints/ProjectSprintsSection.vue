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
                    class="text-label-sm font-label-sm text-on-surface-variant"
                >
                    {{ project.sprints.length }} en total
                </span>
            </div>
            <button
                v-if="canWriteSprints"
                type="button"
                class="bg-primary-container hover:bg-primary flex items-center gap-1.5 rounded-lg px-3 py-2 text-white shadow-md transition-colors"
                @click="emit('create')"
            >
                <AppIcon name="add" :size="15" />
                Planificar un sprint
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
                class="text-label-sm font-label-sm rounded-full border px-3 py-1 transition-colors"
                :class="
                    filter === chip.key
                        ? 'border-primary-container bg-primary-container text-white'
                        : 'border-outline-variant/40 bg-surface-container-low text-on-surface-variant hover:text-on-surface'
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
            <div
                v-if="active"
                class="border-outline-variant/40 bg-surface-container-low/40 gap-space-md p-space-md flex flex-col rounded-xl border"
            >
                <div
                    class="gap-space-sm flex flex-wrap items-center justify-between"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <UiBadge label="Sprint Activo" tone="primary" dot />
                        <Link
                            :href="showSprint.url([project.id, active.id])"
                            class="text-primary text-headline-sm font-headline-sm truncate font-medium hover:underline"
                        >
                            {{ active.name }}
                        </Link>
                    </div>
                    <span
                        class="bg-surface-container text-label-xs font-label-xs text-on-surface-variant shrink-0 rounded-full px-2 py-0.5"
                    >
                        {{
                            activeDaysRemaining === 1
                                ? '1 día restante'
                                : `${activeDaysRemaining} días restantes`
                        }}
                    </span>
                </div>

                <p class="text-label-sm font-label-sm text-on-surface-variant">
                    {{ formatRange(active) }}
                </p>

                <div v-if="active.goal" class="gap-space-xs flex items-start">
                    <AppIcon
                        name="track_changes"
                        :size="15"
                        class="text-on-surface-variant mt-0.5 shrink-0"
                    />
                    <p
                        class="text-body-sm font-body-sm text-on-surface line-clamp-3 min-w-0 leading-relaxed"
                    >
                        <span class="font-medium">Meta del Sprint:</span>
                        {{ active.goal }}
                    </p>
                </div>

                <div class="gap-space-xs flex flex-col">
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-label-sm font-label-sm text-on-surface font-medium"
                        >
                            Progreso de tareas
                        </span>
                        <span
                            class="text-label-sm font-label-sm text-on-surface-variant"
                        >
                            {{ activeMetrics.done }} de
                            {{ activeMetrics.total }} tareas
                        </span>
                    </div>
                    <div
                        class="bg-surface-container-high h-2 overflow-hidden rounded-full"
                        role="progressbar"
                        :aria-valuenow="activeMetrics.coverage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`Progreso de ${active.name}`"
                    >
                        <div
                            class="bg-primary h-full rounded-full transition-all"
                            :style="{ width: `${activeMetrics.coverage}%` }"
                        />
                    </div>
                </div>

                <div
                    class="border-outline-variant/40 bg-surface-container-low/50 gap-space-sm p-space-sm flex items-center justify-between rounded-lg border"
                    aria-disabled="true"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <AppIcon
                            name="lock"
                            :size="14"
                            class="text-on-surface-variant shrink-0"
                        />
                        <div class="min-w-0">
                            <p
                                class="text-label-sm font-label-sm text-on-surface font-medium"
                            >
                                Puntos de historia
                            </p>
                            <p
                                class="text-label-xs font-label-xs text-on-surface-variant"
                            >
                                Estimación en puntos y velocidad del equipo.
                            </p>
                        </div>
                    </div>
                    <UiBadge label="Próximamente" tone="outline" />
                </div>

                <div class="gap-space-sm flex flex-wrap items-center">
                    <Link
                        :href="showSprint.url([project.id, active.id])"
                        class="bg-primary-container hover:bg-primary inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-white shadow-md transition-colors"
                    >
                        <AppIcon name="dashboard" :size="15" />
                        <span class="text-label-md font-label-md">
                            Ver tablero
                        </span>
                    </Link>
                    <button
                        v-if="canWriteSprints"
                        type="button"
                        class="border-outline-variant/40 bg-surface-container-lowest text-on-surface hover:bg-surface-container inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 shadow-sm transition-colors"
                        @click="emit('complete')"
                    >
                        <AppIcon name="check_circle" :size="15" />
                        <span class="text-label-md font-label-md">
                            Completar sprint
                        </span>
                    </button>
                </div>
            </div>

            <div
                v-if="
                    counts.active + counts.planned + counts.completed >
                    (active ? 1 : 0)
                "
                class="gap-space-sm flex flex-col"
            >
                <div class="flex items-center justify-between gap-2">
                    <h3
                        class="text-label-md font-label-md text-on-surface font-medium"
                    >
                        Otros Sprints
                    </h3>
                    <button
                        type="button"
                        class="text-label-xs font-label-xs text-on-surface-variant hover:text-on-surface inline-flex items-center gap-1 transition-colors"
                        @click="othersCollapsed = !othersCollapsed"
                    >
                        <AppIcon
                            :name="
                                othersCollapsed ? 'expand_more' : 'expand_less'
                            "
                            :size="14"
                        />
                        {{ othersCollapsed ? 'Expandir' : 'Colapsar todos' }}
                    </button>
                </div>

                <ul
                    v-show="!othersCollapsed"
                    class="gap-space-xs pr-space-xs flex max-h-80 flex-col overflow-y-auto"
                >
                    <li
                        v-for="sprint in others"
                        :key="sprint.id"
                        class="gap-space-sm p-space-sm bg-surface-container-low/50 flex flex-wrap items-center rounded-lg"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex min-w-0 items-center gap-2">
                                <Link
                                    :href="
                                        showSprint.url([project.id, sprint.id])
                                    "
                                    class="text-primary text-body-sm font-body-sm truncate font-medium hover:underline"
                                >
                                    {{ sprint.name }}
                                </Link>
                                <UiBadge
                                    :label="sprint.statusLabel"
                                    :tone="statusTone(sprint.status)"
                                />
                            </div>
                            <p
                                class="text-label-xs font-label-xs text-on-surface-variant truncate"
                            >
                                {{ formatRange(sprint) }}
                                <template v-if="sprint.status === 'planned'">
                                    · {{ metricsFor(sprint).total }} tareas
                                    planificadas
                                </template>
                                <template
                                    v-else-if="sprint.status === 'completed'"
                                >
                                    · {{ metricsFor(sprint).done }} de
                                    {{ metricsFor(sprint).total }} tareas
                                    concluidas
                                </template>
                                <template v-if="sprint.goal">
                                    · {{ sprint.goal }}
                                </template>
                            </p>
                        </div>
                        <span
                            class="text-label-xs font-label-xs text-on-surface-variant w-16 shrink-0 text-right"
                        >
                            {{ metricsFor(sprint).coverage }}% completado
                        </span>
                        <span
                            v-if="
                                sprint.status === 'planned' && canWriteSprints
                            "
                            class="flex shrink-0 gap-3"
                        >
                            <button
                                type="button"
                                class="text-label-xs font-label-xs text-on-surface-variant hover:text-on-surface hover:underline"
                                @click="emit('edit', sprint)"
                            >
                                Editar
                            </button>
                            <button
                                type="button"
                                class="text-label-xs font-label-xs text-error hover:underline"
                                @click="emit('destroy', sprint)"
                            >
                                Eliminar
                            </button>
                        </span>
                        <Link
                            v-else-if="sprint.status === 'completed'"
                            :href="showSprint.url([project.id, sprint.id])"
                            class="text-label-xs font-label-xs text-primary shrink-0 hover:underline"
                        >
                            Ver resumen
                        </Link>
                    </li>
                    <li
                        v-if="others.length === 0"
                        class="text-body-sm font-body-sm text-on-surface-variant"
                    >
                        {{ emptyFilterMessage }}
                    </li>
                </ul>
            </div>
        </template>
    </section>
</template>
