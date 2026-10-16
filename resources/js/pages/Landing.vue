<script setup lang="ts">
import LandingCta from '@/Components/Landing/LandingCta.vue';
import LandingFeatures from '@/Components/Landing/LandingFeatures.vue';
import LandingFooter from '@/Components/Landing/LandingFooter.vue';
import LandingHeader from '@/Components/Landing/LandingHeader.vue';
import LandingHero from '@/Components/Landing/LandingHero.vue';
import LandingWorkflow from '@/Components/Landing/LandingWorkflow.vue';
import { Head } from '@inertiajs/vue3';

const description =
    'Eunox organiza proyectos, sprints, backlog y tablero Kanban en un solo lugar, sin ruido.';
</script>

<template>
    <Head title="Gestión de proyectos ágil para equipos">
        <meta name="description" :content="description" />
        <meta
            property="og:title"
            content="Eunox — Gestión de proyectos ágil para equipos"
        />
        <meta property="og:description" :content="description" />
        <meta property="og:type" content="website" />
    </Head>

    <div
        id="top"
        class="friday-focus-scope bg-surface font-inter text-on-surface selection:bg-primary-container selection:text-on-primary-container min-h-screen"
    >
        <LandingHeader />
        <main>
            <LandingHero />
            <LandingFeatures />
            <LandingWorkflow />
            <LandingCta />
        </main>
        <LandingFooter />
    </div>
</template>
