<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import UiBadge from '@/Components/Projects/UiBadge.vue';
import { show as showSprint } from '@/routes/projects/sprints';
import type { BacklogNextSprint } from '@/types/backlog';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    nextSprint: BacklogNextSprint | null;
}>();

const isActive = computed(() => props.nextSprint?.status === 'active');

const statusLabel = computed(() => (isActive.value ? 'Activo' : 'Planificado'));
</script>

<template>
    <section
        v-if="nextSprint"
        class="bg-surface-container-low gap-space-md p-space-md flex flex-wrap items-center justify-between rounded-xl"
    >
        <div class="gap-space-md flex min-w-0 items-center">
            <div
                class="bg-surface-container text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
            >
                <AppIcon name="event_upcoming" :size="18" />
            </div>
            <div class="gap-space-xs flex min-w-0 flex-col">
                <span
                    class="text-label-md font-label-md text-on-surface-variant font-semibold tracking-wider uppercase"
                >
                    Próximo sprint
                </span>
                <div
                    class="gap-space-xs flex min-w-0 flex-wrap items-center gap-y-1"
                >
                    <span
                        class="text-headline-sm font-headline-sm text-on-surface truncate"
                    >
                        {{ nextSprint.name }}
                    </span>
                    <UiBadge
                        :label="statusLabel"
                        :tone="isActive ? 'primary' : 'outline'"
                        dot
                    />
                </div>
                <p
                    class="text-body-sm font-body-sm text-on-surface-variant truncate"
                >
                    {{ nextSprint.projectTitle }} ·
                    {{ nextSprint.startsAt }} – {{ nextSprint.endsAt }}
                </p>
            </div>
        </div>

        <Link
            :href="showSprint.url([nextSprint.projectId, nextSprint.id])"
            class="text-label-md font-label-md text-primary hover:text-primary-container flex shrink-0 items-center gap-1 transition-colors"
        >
            Abrir planificación
            <AppIcon name="arrow_forward" :size="16" />
        </Link>
    </section>
</template>
