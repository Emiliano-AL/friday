<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import type { NavItem } from '@/types/shell';

const props = defineProps<{
    items: NavItem[];
    footerItems: NavItem[];
}>();

const page = usePage();

const sections = computed(() => [
    { key: 'main', items: props.items, divider: false },
    { key: 'footer', items: props.footerItems, divider: true },
]);

function isActive(item: NavItem): boolean {
    if (item.match === '/') {
        return page.url === '/';
    }

    return item.match !== '' && page.url.startsWith(item.match);
}
</script>

<template>
    <nav
        class="gap-space-2xs px-space-md py-space-sm flex flex-1 flex-col overflow-y-auto"
        aria-label="Secciones"
    >
        <template v-for="section in sections" :key="section.key">
            <div
                v-if="section.divider"
                class="mt-space-sm border-surface-container-highest/60 pt-space-md border-t"
            />
            <template v-for="item in section.items" :key="item.label">
                <Link
                    v-if="item.to !== null && !item.disabled"
                    :href="item.to"
                    :aria-current="isActive(item) ? 'page' : undefined"
                    class="group gap-space-sm px-space-md py-space-sm text-label-md flex items-center rounded-lg transition-colors"
                    :class="
                        isActive(item)
                            ? 'bg-surface-container-lowest text-on-surface shadow-card font-semibold'
                            : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
                    "
                >
                    <AppIcon :name="item.icon" />
                    <span class="flex-1 truncate">{{ item.label }}</span>
                    <span
                        v-if="item.badge !== undefined"
                        class="bg-surface-container-highest text-label-xs text-on-surface-variant rounded px-1.5 py-0.5"
                        >{{ item.badge }}</span
                    >
                </Link>
                <span
                    v-else
                    :title="`${item.label} (próximamente)`"
                    class="gap-space-sm px-space-md py-space-sm text-label-md text-on-surface-variant/60 flex cursor-not-allowed items-center rounded-lg"
                >
                    <AppIcon :name="item.icon" />
                    <span class="flex-1 truncate">{{ item.label }}</span>
                    <span
                        class="bg-surface-container-high text-label-xs rounded px-1.5 py-0.5"
                        >Próximamente</span
                    >
                </span>
            </template>
        </template>
    </nav>
</template>
