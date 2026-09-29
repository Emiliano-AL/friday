<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';

const items = [
    {
        icon: 'local_fire_department',
        title: 'Story Points y velocidad',
        description: 'Burn-up, ritmo y capacidad del equipo.',
    },
    {
        icon: 'auto_graph',
        title: 'Burndown',
        description: 'Línea ideal contra avance real.',
    },
    {
        icon: 'campaign',
        title: 'Notas del Daily',
        description: 'Seguimiento diario del equipo.',
    },
    {
        icon: 'history',
        title: 'Bitácora del Sprint',
        description: 'Actividad reciente de las tareas.',
    },
    {
        icon: 'group',
        title: 'Carga por Miembro',
        description: 'Distribución del trabajo del equipo.',
    },
    {
        icon: 'error',
        title: 'Bloqueadas',
        description: 'Tareas bloqueadas y su motivo.',
    },
    {
        icon: 'tag',
        title: 'Clave legible y progreso',
        description: 'Prefijo-folio y avance por tarea.',
    },
];
</script>

<template>
    <section
        class="bg-surface-container-lowest shadow-card gap-space-md p-space-lg flex flex-col rounded-xl"
        aria-label="Próximamente"
    >
        <span
            class="text-label-xs font-label-xs text-on-surface-variant flex items-center gap-1.5 font-semibold tracking-wider uppercase"
        >
            En camino
        </span>

        <ul class="gap-space-sm flex flex-col">
            <li
                v-for="item in items"
                :key="item.title"
                class="border-surface-container-low p-space-sm flex items-center justify-between gap-3 rounded-lg border"
                aria-disabled="true"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <AppIcon
                        name="lock"
                        :size="16"
                        class="text-on-surface-variant shrink-0"
                    />
                    <div class="min-w-0">
                        <p
                            class="text-body-sm font-body-sm text-on-surface-variant truncate font-medium"
                        >
                            {{ item.title }}
                        </p>
                        <p
                            class="text-label-xs font-label-xs text-on-surface-variant/70 truncate"
                        >
                            {{ item.description }}
                        </p>
                    </div>
                </div>
                <UiBadge label="Próximamente" tone="outline" />
            </li>
        </ul>
    </section>
</template>
