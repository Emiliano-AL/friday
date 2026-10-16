<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';

const features = [
    {
        icon: 'monitoring',
        title: 'Proyectos con métricas de avance',
        description:
            'Cada proyecto muestra su progreso real: tareas completadas, sprint activo y fecha objetivo de un vistazo.',
    },
    {
        icon: 'sprint',
        title: 'Sprints con activación y cierre',
        description:
            'Planea iteraciones, actívalas cuando estés listo y ciérralas moviendo lo pendiente al siguiente sprint.',
    },
    {
        icon: 'view_kanban',
        title: 'Tablero Kanban',
        description:
            'Arrastra tareas entre columnas y mantén visible en qué está trabajando cada persona.',
    },
    {
        icon: 'inbox',
        title: 'Backlog con captura rápida',
        description:
            'Anota ideas y pendientes en segundos y priorízalos después, sin perder nada en el camino.',
    },
    {
        icon: 'forum',
        title: 'Tareas con comentarios',
        description:
            'Tipo, prioridad, responsable y conversación en un mismo lugar para que el contexto no se disperse.',
    },
    {
        icon: 'group_add',
        title: 'Colaboradores e invitaciones',
        description:
            'Invita a tu equipo por correo o con un enlace y define qué puede hacer cada quien en el proyecto.',
    },
];
</script>

<template>
    <section
        id="caracteristicas"
        class="border-outline-variant/40 bg-surface-container-lowest scroll-mt-16 border-y"
    >
        <div
            class="px-margin-mobile lg:px-margin mx-auto max-w-6xl py-20 md:py-24"
        >
            <div class="mx-auto max-w-2xl text-center">
                <p
                    class="text-label-sm text-primary font-semibold tracking-wider uppercase"
                >
                    Características
                </p>
                <h2
                    class="mt-space-sm text-headline-xl-mobile md:text-headline-xl text-on-surface"
                >
                    Todo lo que tu equipo necesita, nada que sobre
                </h2>
                <p class="mt-space-md text-body-lg text-on-surface-variant">
                    Herramientas enfocadas en el ciclo de trabajo de equipos
                    pequeños: del backlog al sprint y del sprint a «hecho».
                </p>
            </div>

            <div
                class="mt-space-3xl gap-space-lg grid sm:grid-cols-2 lg:grid-cols-3"
            >
                <article
                    v-for="feature in features"
                    :key="feature.title"
                    class="border-outline-variant/50 bg-surface p-space-xl hover:border-primary-container/40 rounded-xl border transition-colors"
                >
                    <span
                        class="bg-primary-fixed/50 text-primary flex h-9 w-9 items-center justify-center rounded-lg"
                    >
                        <AppIcon :name="feature.icon" :size="20" />
                    </span>
                    <h3
                        class="mt-space-lg text-headline-sm text-on-surface font-semibold"
                    >
                        {{ feature.title }}
                    </h3>
                    <p class="mt-space-xs text-body-md text-on-surface-variant">
                        {{ feature.description }}
                    </p>
                </article>
            </div>
        </div>
    </section>
</template>
