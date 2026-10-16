<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';

type PreviewTask = {
    title: string;
    type: 'Feature' | 'Bug' | 'Test';
    priority: 'low' | 'medium' | 'high';
    code: string;
};

const columns: { title: string; tasks: PreviewTask[] }[] = [
    {
        title: 'Por hacer',
        tasks: [
            {
                title: 'Definir onboarding',
                type: 'Feature',
                priority: 'medium',
                code: 'WEB-12',
            },
            {
                title: 'Revisar textos legales',
                type: 'Test',
                priority: 'low',
                code: 'WEB-15',
            },
        ],
    },
    {
        title: 'En progreso',
        tasks: [
            {
                title: 'Tablero de métricas',
                type: 'Feature',
                priority: 'high',
                code: 'WEB-9',
            },
            {
                title: 'Error al exportar CSV',
                type: 'Bug',
                priority: 'high',
                code: 'WEB-11',
            },
        ],
    },
    {
        title: 'Hecho',
        tasks: [
            {
                title: 'Invitar colaboradores',
                type: 'Feature',
                priority: 'medium',
                code: 'WEB-4',
            },
            {
                title: 'Login con Google',
                type: 'Feature',
                priority: 'low',
                code: 'WEB-2',
            },
        ],
    },
];

const typeClasses: Record<PreviewTask['type'], string> = {
    Feature: 'bg-primary-fixed/60 text-on-primary-fixed-variant',
    Bug: 'bg-error-container text-on-error-container',
    Test: 'bg-secondary-container text-on-secondary-fixed-variant',
};

const priorityClasses: Record<PreviewTask['priority'], string> = {
    low: 'bg-outline',
    medium: 'bg-tertiary-fixed-dim',
    high: 'bg-tertiary-container',
};
</script>

<template>
    <section class="relative overflow-hidden">
        <div
            class="bg-primary-fixed/30 pointer-events-none absolute -top-40 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full blur-3xl select-none"
            aria-hidden="true"
        />

        <div
            class="px-margin-mobile lg:px-margin relative mx-auto flex max-w-6xl flex-col items-center pt-16 pb-20 text-center md:pt-24"
        >
            <span
                class="gap-space-xs border-outline-variant/50 bg-surface-container-lowest px-space-md text-label-sm text-on-surface-variant shadow-card inline-flex items-center rounded-full border py-1"
            >
                <span class="bg-primary-container h-1.5 w-1.5 rounded-full" />
                Gestión ágil, sin ruido
            </span>

            <h1
                class="mt-space-xl text-headline-xl-mobile text-on-surface max-w-3xl font-semibold tracking-tight md:text-[52px] md:leading-[60px]"
            >
                Planea, ejecuta y termina tus sprints con calma
            </h1>

            <p
                class="mt-space-lg text-body-lg text-on-surface-variant max-w-2xl"
            >
                {{ $page.props.name }} reúne proyectos, sprints, backlog y
                tablero Kanban en un espacio minimalista para que tu equipo se
                enfoque en avanzar, no en administrar herramientas.
            </p>

            <div
                class="mt-space-2xl gap-space-sm flex w-full flex-col sm:w-auto sm:flex-row"
            >
                <Link
                    :href="register()"
                    class="gap-space-xs bg-primary-container px-space-xl text-body-md text-on-primary shadow-card hover:bg-primary inline-flex items-center justify-center rounded-lg py-2.5 font-medium transition-colors"
                >
                    Comenzar gratis
                    <AppIcon name="arrow_forward" :size="16" />
                </Link>
                <Link
                    :href="login()"
                    class="border-outline-variant/60 bg-surface-container-lowest px-space-xl text-body-md text-on-surface hover:bg-surface-container-low inline-flex items-center justify-center rounded-lg border py-2.5 font-medium transition-colors"
                >
                    Iniciar sesión
                </Link>
            </div>

            <div
                class="mt-16 w-full max-w-5xl overflow-hidden md:mt-20"
                aria-hidden="true"
            >
                <div
                    class="border-outline-variant/50 bg-surface-container-lowest shadow-popover rounded-xl border text-left"
                >
                    <div
                        class="border-outline-variant/40 gap-space-xs px-space-lg py-space-md flex items-center border-b"
                    >
                        <span
                            class="bg-outline-variant h-2.5 w-2.5 rounded-full"
                        />
                        <span
                            class="bg-outline-variant h-2.5 w-2.5 rounded-full"
                        />
                        <span
                            class="bg-outline-variant h-2.5 w-2.5 rounded-full"
                        />
                        <span
                            class="ml-space-md text-label-sm text-on-surface-variant"
                            >Sprint 7 · Rediseño web</span
                        >
                    </div>

                    <div
                        class="gap-space-md p-space-lg grid sm:grid-cols-2 md:grid-cols-3"
                    >
                        <div
                            v-for="(column, index) in columns"
                            :key="column.title"
                            class="bg-surface-container-low p-space-sm rounded-lg"
                            :class="{
                                'hidden sm:block': index === 1,
                                'hidden md:block': index === 2,
                            }"
                        >
                            <div
                                class="mb-space-sm px-space-xs text-label-sm text-on-surface-variant flex items-center justify-between"
                            >
                                <span class="font-semibold uppercase">{{
                                    column.title
                                }}</span>
                                <span>{{ column.tasks.length }}</span>
                            </div>
                            <div class="gap-space-sm flex flex-col">
                                <div
                                    v-for="task in column.tasks"
                                    :key="task.code"
                                    class="border-outline-variant/40 bg-surface-container-lowest p-space-md shadow-card rounded-lg border"
                                >
                                    <p
                                        class="text-body-sm text-on-surface font-medium"
                                    >
                                        {{ task.title }}
                                    </p>
                                    <div
                                        class="mt-space-sm flex items-center justify-between"
                                    >
                                        <span
                                            class="px-space-xs text-label-xs rounded py-0.5 font-medium"
                                            :class="typeClasses[task.type]"
                                            >{{ task.type }}</span
                                        >
                                        <span
                                            class="gap-space-xs text-label-xs text-on-surface-variant flex items-center"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="
                                                    priorityClasses[
                                                        task.priority
                                                    ]
                                                "
                                            />
                                            {{ task.code }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
