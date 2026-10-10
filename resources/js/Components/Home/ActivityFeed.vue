<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Avatar from '@/Components/AppShell/Avatar.vue';
import type { ActivityFeedPayload, ActivityItem } from '@/types/dashboard';
import { relativeTime } from '@/utils/relativeTime';
import { show as showTask } from '@/routes/tasks';

defineProps<{
    feed: ActivityFeedPayload;
}>();

function subject(item: ActivityItem): string | null {
    return item.task?.title ?? item.sprint?.name ?? item.project.title;
}
</script>

<template>
    <section
        aria-label="Actividad del equipo"
        class="bg-surface-container-lowest shadow-card flex flex-col rounded-xl"
    >
        <header
            class="px-space-lg pt-space-lg pb-space-md gap-space-md flex items-center justify-between"
        >
            <h2 class="text-headline-sm font-headline-sm text-on-surface">
                Actividad del equipo
            </h2>
        </header>

        <ul v-if="feed.items.length > 0" class="flex flex-col">
            <li
                v-for="item in feed.items"
                :key="item.id"
                class="border-surface-container-low hover:bg-surface-container-low gap-space-md px-space-lg py-space-sm flex items-center border-b transition-colors"
            >
                <Avatar
                    :name="item.user.name"
                    :src="item.user.avatar"
                    :size="32"
                />
                <p
                    class="text-body-sm font-body-sm text-on-surface min-w-0 flex-1 truncate"
                >
                    <span class="font-semibold">{{ item.user.name }}</span>
                    {{ item.typeLabel }}
                    <Link
                        v-if="item.task"
                        :href="showTask.url(item.task.id)"
                        class="text-primary hover:text-primary-container font-medium"
                    >
                        {{ item.task.title }}
                    </Link>
                    <template v-else>{{ subject(item) }}</template>
                </p>
                <span
                    class="text-label-xs font-label-xs text-on-surface-variant shrink-0"
                >
                    {{ relativeTime(item.createdAt) }}
                </span>
            </li>
        </ul>
        <p
            v-else
            class="text-body-sm font-body-sm text-on-surface-variant px-space-lg pb-space-lg italic"
        >
            Sin actividad reciente.
        </p>
    </section>
</template>
