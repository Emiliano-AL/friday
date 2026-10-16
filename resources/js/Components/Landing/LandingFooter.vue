<script setup lang="ts">
import AppLogo from '@/Components/AppShell/AppLogo.vue';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-outline-variant/40 border-t">
        <div
            class="px-margin-mobile lg:px-margin gap-space-md mx-auto flex max-w-6xl flex-col items-center justify-between py-8 sm:flex-row"
        >
            <a href="#top" class="gap-space-sm flex items-center">
                <AppLogo />
                <span class="text-body-md text-on-surface font-semibold">
                    {{ $page.props.name }}
                </span>
            </a>
            <p class="text-body-sm text-on-surface-variant text-center">
                © {{ year }} {{ $page.props.name }}. Todos los derechos
                reservados.
            </p>
        </div>
    </footer>
</template>
