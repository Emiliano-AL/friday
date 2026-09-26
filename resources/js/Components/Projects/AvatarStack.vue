<script setup lang="ts">
import { computed } from 'vue';
import Avatar from '@/Components/AppShell/Avatar.vue';
import AppIcon from '@/Components/AppShell/AppIcon.vue';

const props = withDefaults(
    defineProps<{
        people: { id: number; name: string; avatar: string | null }[];
        max?: number;
    }>(),
    {
        max: 3,
    },
);

const visiblePeople = computed(() => props.people.slice(0, props.max));

const overflowCount = computed(() =>
    Math.max(props.people.length - props.max, 0),
);
</script>

<template>
    <div v-if="people.length > 0" class="flex items-center -space-x-1.5">
        <span
            v-for="person in visiblePeople"
            :key="person.id"
            class="ring-surface-container-lowest block rounded-full ring-2"
        >
            <Avatar :name="person.name" :src="person.avatar" :size="24" />
        </span>
        <div
            v-if="overflowCount > 0"
            class="bg-surface-container text-on-surface-variant font-label-xs ring-surface-container-lowest flex h-6 w-6 items-center justify-center rounded-full text-[10px] ring-2"
        >
            +{{ overflowCount }}
        </div>
    </div>
    <div
        v-else
        class="bg-surface-container text-on-surface-variant flex h-6 w-6 items-center justify-center rounded-full"
    >
        <AppIcon name="person" :size="16" />
    </div>
</template>
