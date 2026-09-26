<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';

const emit = defineEmits<{
    create: [];
}>();
</script>

<template>
    <div
        class="gap-space-md py-space-xl flex flex-col items-center justify-center text-center"
    >
        <div
            class="bg-surface-container text-primary flex h-14 w-14 items-center justify-center rounded-full"
        >
            <AppIcon name="create_new_folder" :size="28" />
        </div>
        <h2 class="font-headline-sm text-headline-sm text-on-surface">
            Todavía no tienes proyectos
        </h2>
        <p class="font-body-sm text-body-sm text-on-surface-variant">
            Crea tu primer proyecto para empezar a organizar el trabajo.
        </p>
        <button
            type="button"
            class="bg-primary text-on-primary text-label-md font-label-md shadow-card hover:bg-primary-container gap-space-xs flex items-center rounded-lg px-4 py-2 transition-colors"
            @click="emit('create')"
        >
            <AppIcon name="add" :size="16" />
            Iniciar un nuevo proyecto
        </button>
    </div>
</template>
