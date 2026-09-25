<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
</script>

<template>
    <div
        data-shell-popover
        role="dialog"
        aria-label="Notificaciones"
        class="bg-surface-container-lowest p-space-md shadow-popover fixed top-16 right-20 z-50 w-80 rounded-xl"
    >
        <div class="pb-space-sm mb-space-xs flex items-center justify-between">
            <span class="text-headline-sm text-on-surface">Notificaciones</span>
        </div>
        <div
            class="gap-space-sm py-space-lg flex flex-col items-center text-center"
        >
            <span
                class="bg-surface-container text-on-surface-variant flex h-10 w-10 items-center justify-center rounded-full"
            >
                <AppIcon name="notifications" :size="20" />
            </span>
            <p class="text-label-md text-on-surface">Sin notificaciones</p>
            <p class="text-body-sm text-on-surface-variant">
                Cuando tengas novedades, aparecerán aquí.
            </p>
        </div>
    </div>
</template>
