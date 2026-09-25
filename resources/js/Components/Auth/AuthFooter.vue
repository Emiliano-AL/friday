<script setup lang="ts">
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
</script>

<template>
    <footer class="px-margin-mobile py-space-lg lg:px-margin w-full">
        <div
            class="gap-space-md text-label-sm text-on-surface-variant mx-auto flex max-w-7xl flex-col items-center justify-between sm:flex-row"
        >
            <div class="gap-space-md flex items-center">
                <a
                    href="#"
                    class="hover:text-on-surface inline-flex min-h-6 items-center transition-colors"
                >
                    Privacidad
                </a>
                <span class="text-outline-variant" aria-hidden="true">•</span>
                <a
                    href="#"
                    class="hover:text-on-surface inline-flex min-h-6 items-center transition-colors"
                >
                    Términos
                </a>
                <span class="text-outline-variant" aria-hidden="true">•</span>
                <a
                    href="#"
                    class="hover:text-on-surface inline-flex min-h-6 items-center transition-colors"
                >
                    Soporte
                </a>
            </div>
            <div class="gap-space-xs flex items-center">
                <AuthIcon name="globe" :size="16" />
                <span class="font-medium">Idioma: Español (ES)</span>
            </div>
        </div>
    </footer>
</template>
