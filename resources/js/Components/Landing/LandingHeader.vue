<script setup lang="ts">
import AppLogo from '@/Components/AppShell/AppLogo.vue';
import { login, register } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <header
        class="border-outline-variant/40 bg-surface/80 sticky top-0 z-20 border-b backdrop-blur"
    >
        <div
            class="px-margin-mobile lg:px-margin mx-auto flex h-16 max-w-6xl items-center justify-between"
        >
            <a href="#top" class="gap-space-sm flex items-center">
                <AppLogo />
                <span class="text-headline-sm text-on-surface font-semibold">
                    {{ $page.props.name }}
                </span>
            </a>

            <nav
                aria-label="Principal"
                class="gap-space-xl text-label-md text-on-surface-variant hidden items-center md:flex"
            >
                <a
                    href="#caracteristicas"
                    class="hover:text-on-surface transition-colors"
                    >Características</a
                >
                <a
                    href="#como-funciona"
                    class="hover:text-on-surface transition-colors"
                    >Cómo funciona</a
                >
            </nav>

            <div class="gap-space-sm flex items-center">
                <Link
                    :href="login()"
                    class="px-space-sm sm:px-space-md text-label-md text-on-surface-variant hover:text-on-surface sm:border-outline-variant/60 sm:hover:bg-surface-container-low rounded-lg py-1.5 transition-colors sm:border"
                >
                    Iniciar sesión
                </Link>
                <Link
                    :href="register()"
                    class="bg-primary-container px-space-md text-label-md text-on-primary shadow-card hover:bg-primary rounded-lg py-1.5 transition-colors"
                >
                    Comenzar gratis
                </Link>
            </div>
        </div>
    </header>
</template>
