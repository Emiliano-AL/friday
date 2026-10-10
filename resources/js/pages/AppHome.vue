<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HomeCanvas from '@/Components/AppShell/HomeCanvas.vue';
import ActivityDashboard from '@/Components/Home/ActivityDashboard.vue';
import type { Dashboard } from '@/types/dashboard';

defineProps<{
    dashboard: Dashboard | null;
}>();
</script>

<template>
    <AuthenticatedLayout>
        <ActivityDashboard v-if="dashboard" :dashboard="dashboard" />
        <HomeCanvas v-else />
    </AuthenticatedLayout>
</template>
