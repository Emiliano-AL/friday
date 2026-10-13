<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { show as showSprint } from '@/routes/projects/sprints';
import type { BacklogNextSprint } from '@/types/backlog';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    nextSprint: BacklogNextSprint | null;
}>();

const isActive = computed(() => props.nextSprint?.status === 'active');

const statusLabel = computed(() =>
    isActive.value ? 'Sprint activo' : 'Próximo sprint',
);
</script>

<template>
    <Link
        v-if="nextSprint"
        :href="showSprint.url([nextSprint.projectId, nextSprint.id])"
        class="group bg-surface-container-lowest shadow-card hover:shadow-popover gap-space-sm px-space-md flex max-w-full min-w-0 items-center rounded-lg py-2 transition-shadow"
        :title="`${nextSprint.projectTitle} · ${nextSprint.startsAt} – ${nextSprint.endsAt}`"
    >
        <span
            class="h-2 w-2 shrink-0 rounded-full"
            :class="isActive ? 'bg-primary' : 'bg-outline-variant'"
            aria-hidden="true"
        />
        <span class="flex min-w-0 flex-col">
            <span class="text-label-xs font-label-xs text-on-surface-variant">
                {{ statusLabel }} · {{ nextSprint.projectTitle }}
            </span>
            <span
                class="text-label-md font-label-md text-on-surface group-hover:text-primary truncate transition-colors"
            >
                {{ nextSprint.name }}
            </span>
        </span>
        <span
            class="text-label-sm font-label-sm text-primary ml-space-sm flex shrink-0 items-center gap-1"
        >
            Planificar
            <AppIcon name="arrow_forward" :size="14" />
        </span>
    </Link>
</template>
