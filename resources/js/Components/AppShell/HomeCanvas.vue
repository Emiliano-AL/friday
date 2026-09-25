<script setup lang="ts">
import { inject } from 'vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';

const openPalette = inject<() => void>('friday:open-palette', () => {});
</script>

<template>
    <div class="pb-space-2xl flex flex-col">
        <!-- Context bar -->
        <div class="mb-space-md py-space-sm flex items-center justify-between">
            <div
                class="gap-space-sm text-label-md text-on-surface-variant flex items-center"
            >
                <span class="hover:text-on-surface transition-colors"
                    >Mi Espacio</span
                >
                <span
                    class="text-label-xs text-outline-variant"
                    aria-hidden="true"
                    >/</span
                >
                <span class="text-on-surface font-semibold"
                    >Lienzo en Blanco</span
                >
                <span
                    class="ml-space-xs bg-surface-container px-space-xs text-label-xs text-on-surface-variant inline-flex items-center rounded-full py-0.5"
                    >Borrador</span
                >
            </div>
            <div class="gap-space-sm flex items-center">
                <button
                    type="button"
                    class="gap-space-xs bg-surface-container-lowest px-space-md text-label-md text-on-surface-variant shadow-card hover:bg-surface-container-high hover:text-on-surface flex items-center rounded-lg py-1.5 transition-all"
                    @click="openPalette"
                >
                    <AppIcon
                        name="keyboard_command_key"
                        :size="16"
                        class="text-primary-container"
                    />
                    <span>Comandos rápidos</span>
                    <kbd
                        class="ml-space-xs bg-surface-container text-label-xs text-on-surface-variant rounded px-1.5 py-0.5"
                        >⌘K</kbd
                    >
                </button>
                <button
                    type="button"
                    class="gap-space-xs bg-primary-container px-space-md text-label-md text-on-primary shadow-card hover:bg-primary flex items-center rounded-lg py-1.5 transition-all"
                >
                    <AppIcon name="add" />
                    <span>Añadir bloque</span>
                </button>
            </div>
        </div>

        <!-- Canvas -->
        <div
            class="bg-surface-container-lowest p-space-xl shadow-card relative flex min-h-[580px] flex-1 flex-col items-center justify-center overflow-hidden rounded-xl"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(#c7c4d6_1px,transparent_1px)] [background-size:24px_24px] opacity-40"
                aria-hidden="true"
            />
            <div
                class="from-primary-fixed/30 via-surface-container-high/40 pointer-events-none absolute -top-12 -right-12 h-[480px] w-[480px] rounded-full bg-gradient-to-tr to-transparent blur-3xl"
                aria-hidden="true"
            />
            <div
                class="from-secondary-container/30 pointer-events-none absolute -bottom-16 -left-12 h-[420px] w-[420px] rounded-full bg-gradient-to-bl to-transparent blur-3xl"
                aria-hidden="true"
            />

            <div
                class="px-space-md relative z-10 flex max-w-lg flex-col items-center text-center"
            >
                <div
                    class="mb-space-lg bg-surface-container-low text-primary-container shadow-card flex h-14 w-14 items-center justify-center rounded-2xl"
                >
                    <AppIcon name="space_dashboard" :size="28" filled />
                </div>
                <h1 class="mb-space-xs text-headline-lg text-on-surface">
                    Espacio listo para crear
                </h1>
                <p
                    class="mb-space-xl text-body-md text-on-surface-variant max-w-sm leading-relaxed"
                >
                    Comienza con un lienzo limpio. Organiza sprints, documenta
                    especificaciones o crea tareas con atajos de teclado
                    fluidos.
                </p>

                <div
                    class="mb-space-xl gap-space-sm grid w-full grid-cols-1 sm:grid-cols-3"
                >
                    <button
                        type="button"
                        class="group bg-surface-container-low p-space-md hover:bg-surface-container hover:shadow-card flex flex-col items-center rounded-lg text-left transition-all"
                        @click="openPalette"
                    >
                        <span
                            class="mb-space-xs bg-surface-container-lowest text-on-surface-variant shadow-card group-hover:text-primary-container flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
                        >
                            <AppIcon name="add_task" />
                        </span>
                        <span
                            class="text-label-md text-on-surface group-hover:text-primary-container transition-colors"
                            >Nueva tarea</span
                        >
                        <span
                            class="text-label-xs text-on-surface-variant mt-0.5"
                            >Presiona "C"</span
                        >
                    </button>
                    <button
                        type="button"
                        disabled
                        title="Planear sprint (próximamente)"
                        class="bg-surface-container-low p-space-md flex cursor-not-allowed flex-col items-center rounded-lg text-left opacity-60"
                    >
                        <span
                            class="mb-space-xs bg-surface-container-lowest text-on-surface-variant shadow-card flex h-8 w-8 items-center justify-center rounded-lg"
                        >
                            <AppIcon name="view_timeline" />
                        </span>
                        <span class="text-label-md text-on-surface"
                            >Planear sprint</span
                        >
                        <span
                            class="text-label-xs text-on-surface-variant mt-0.5"
                            >Asignar metas</span
                        >
                    </button>
                    <button
                        type="button"
                        disabled
                        title="Integrar git (próximamente)"
                        class="bg-surface-container-low p-space-md flex cursor-not-allowed flex-col items-center rounded-lg text-left opacity-60"
                    >
                        <span
                            class="mb-space-xs bg-surface-container-lowest text-on-surface-variant shadow-card flex h-8 w-8 items-center justify-center rounded-lg"
                        >
                            <AppIcon name="terminal" />
                        </span>
                        <span class="text-label-md text-on-surface"
                            >Integrar git</span
                        >
                        <span
                            class="text-label-xs text-on-surface-variant mt-0.5"
                            >Ramas y PRs</span
                        >
                    </button>
                </div>

                <div
                    class="gap-space-xs bg-surface-container-low px-space-md py-space-xs text-label-sm text-on-surface-variant flex items-center rounded-full"
                >
                    <span>Presiona</span>
                    <kbd
                        class="bg-surface-container-lowest text-label-xs text-on-surface shadow-card rounded px-1.5 py-0.5 font-semibold"
                        >⌘</kbd
                    >
                    <kbd
                        class="bg-surface-container-lowest text-label-xs text-on-surface shadow-card rounded px-1.5 py-0.5 font-semibold"
                        >K</kbd
                    >
                    <span>para buscar o disparar cualquier acción</span>
                </div>
            </div>

            <div
                class="gap-space-xs text-label-xs text-on-surface-variant pointer-events-none absolute right-5 bottom-4 flex items-center opacity-80"
            >
                <span class="h-2 w-2 rounded-full bg-emerald-500" />
                <span>Sincronizado en la nube</span>
                <span class="text-outline-variant" aria-hidden="true">•</span>
                <span>Friday v2.4</span>
            </div>
        </div>
    </div>
</template>
