<script setup lang="ts">
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
</script>

<template>
    <footer class="w-full px-margin-mobile py-space-lg lg:px-margin">
        <div
            class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-space-md text-label-sm text-on-surface-variant sm:flex-row"
        >
            <div class="flex items-center gap-space-md">
                <a
                    href="#"
                    class="inline-flex min-h-6 items-center transition-colors hover:text-on-surface"
                >
                    Privacidad
                </a>
                <span class="text-outline-variant" aria-hidden="true">•</span>
                <a
                    href="#"
                    class="inline-flex min-h-6 items-center transition-colors hover:text-on-surface"
                >
                    Términos
                </a>
                <span class="text-outline-variant" aria-hidden="true">•</span>
                <a
                    href="#"
                    class="inline-flex min-h-6 items-center transition-colors hover:text-on-surface"
                >
                    Soporte
                </a>
            </div>
            <div class="flex items-center gap-space-xs">
                <AuthIcon name="globe" :size="16" />
                <span class="font-medium">Idioma: Español (ES)</span>
            </div>
        </div>
    </footer>
</template>
