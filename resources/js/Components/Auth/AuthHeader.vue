<script setup lang="ts">
import AppLogo from '@/Components/AppShell/AppLogo.vue';
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <header
        class="px-margin-mobile py-space-lg lg:px-margin flex w-full items-center justify-between"
    >
        <Link :href="home()" class="gap-space-sm flex items-center">
            <AppLogo />
            <span class="text-headline-sm text-on-surface font-semibold">
                {{ $page.props.name }}
            </span>
        </Link>
        <div
            class="gap-space-sm text-label-xs text-on-surface-variant flex items-center"
        >
            <AuthIcon name="lock" :size="14" class="text-primary" />
            <span>Conexión segura SSL</span>
        </div>
    </header>
</template>
