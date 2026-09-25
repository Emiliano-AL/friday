<script setup lang="ts">
import AuthFooter from '@/Components/Auth/AuthFooter.vue';
import AuthHeader from '@/Components/Auth/AuthHeader.vue';
</script>

<template>
    <div
        class="friday-focus-scope flex min-h-screen flex-col bg-surface font-inter text-on-surface selection:bg-primary-container selection:text-on-primary-container"
    >
        <AuthHeader />

        <main
            class="flex w-full flex-1 flex-col items-center justify-center px-margin-mobile py-space-xl lg:px-margin"
        >
            <div class="relative flex w-full flex-col items-center justify-center">
                <div
                    class="pointer-events-none absolute -top-24 h-[500px] w-[500px] rounded-full bg-primary-fixed/30 blur-3xl select-none"
                    aria-hidden="true"
                />
                <slot />
                <div
                    class="mt-space-xl flex flex-wrap items-center justify-center gap-space-md text-label-sm text-on-surface-variant select-none"
                >
                    <div
                        class="flex items-center gap-space-xs rounded-full bg-surface-container-low px-space-md py-space-xs"
                    >
                        <span
                            class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#10b981]"
                        />
                        <span class="text-on-surface">
                            Todos los sistemas operativos
                        </span>
                    </div>
                    <span class="text-surface-variant" aria-hidden="true">•</span>
                    <a
                        href="#"
                        class="inline-flex min-h-6 items-center transition-colors hover:text-on-surface"
                    >
                        Soporte
                    </a>
                    <span class="text-surface-variant" aria-hidden="true">•</span>
                    <a
                        href="#"
                        class="inline-flex min-h-6 items-center transition-colors hover:text-on-surface"
                    >
                        Seguridad corporativa
                    </a>
                </div>
            </div>
        </main>

        <AuthFooter />
    </div>
</template>
