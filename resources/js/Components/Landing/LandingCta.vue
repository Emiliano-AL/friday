<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { register } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="px-margin-mobile lg:px-margin pb-20 md:pb-24">
        <div
            class="bg-inverse-surface px-space-xl relative mx-auto max-w-6xl overflow-hidden rounded-xl py-16 text-center"
        >
            <div
                class="bg-primary-container/40 pointer-events-none absolute -bottom-32 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full blur-3xl select-none"
                aria-hidden="true"
            />
            <div class="relative">
                <h2
                    class="text-headline-xl-mobile md:text-headline-xl text-inverse-on-surface"
                >
                    Empieza hoy, es gratis
                </h2>
                <p
                    class="mt-space-md text-body-lg text-inverse-on-surface/70 mx-auto max-w-xl"
                >
                    Crea tu cuenta con correo o con Google y arma tu primer
                    sprint en minutos.
                </p>
                <Link
                    :href="register()"
                    class="mt-space-2xl gap-space-xs bg-primary-container px-space-xl text-body-md text-on-primary hover:bg-primary inline-flex items-center justify-center rounded-lg py-2.5 font-medium transition-colors"
                >
                    Comenzar gratis
                    <AppIcon name="arrow_forward" :size="16" />
                </Link>
            </div>
        </div>
    </section>
</template>
