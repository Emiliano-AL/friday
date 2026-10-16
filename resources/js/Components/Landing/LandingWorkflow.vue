<script setup lang="ts">
const steps = [
    {
        title: 'Crea un proyecto',
        description:
            'Dale nombre, color y fecha objetivo, e invita a tu equipo en un par de clics.',
    },
    {
        title: 'Planea el sprint',
        description:
            'Mueve tareas del backlog al sprint, asigna responsables y define prioridades.',
    },
    {
        title: 'Avanza en el tablero',
        description:
            'Sigue el trabajo en Kanban y observa cómo crecen las métricas del proyecto.',
    },
];
</script>

<template>
    <section id="como-funciona" class="scroll-mt-16">
        <div
            class="px-margin-mobile lg:px-margin mx-auto max-w-6xl py-20 md:py-24"
        >
            <div class="mx-auto max-w-2xl text-center">
                <p
                    class="text-label-sm text-primary font-semibold tracking-wider uppercase"
                >
                    Cómo funciona
                </p>
                <h2
                    class="mt-space-sm text-headline-xl-mobile md:text-headline-xl text-on-surface"
                >
                    De la idea a «hecho» en tres pasos
                </h2>
            </div>

            <ol class="mt-space-3xl gap-space-xl grid md:grid-cols-3">
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="gap-space-md flex md:flex-col"
                >
                    <span
                        class="border-primary-container/30 bg-primary-fixed/40 text-label-md text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-full border font-semibold"
                    >
                        {{ index + 1 }}
                    </span>
                    <div>
                        <h3
                            class="text-headline-sm text-on-surface font-semibold"
                        >
                            {{ step.title }}
                        </h3>
                        <p
                            class="mt-space-xs text-body-md text-on-surface-variant"
                        >
                            {{ step.description }}
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </section>
</template>
