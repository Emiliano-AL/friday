<script setup lang="ts">
import AppIcon from '@/Components/AppShell/AppIcon.vue';
import { ref, watch } from 'vue';

const props = defineProps<{
    success?: string | null;
    error?: string | null;
}>();

const visible = ref(true);

watch(
    () => [props.success, props.error],
    () => {
        visible.value = true;
    },
);

function dismiss(): void {
    visible.value = false;
}
</script>

<template>
    <div
        v-if="visible && (success || error)"
        class="px-space-xl pt-space-md"
        :class="success ? '' : ''"
    >
        <div
            class="shadow-card gap-space-sm px-space-md py-space-sm mx-auto flex w-full max-w-7xl items-center rounded-xl"
            :class="
                success
                    ? 'bg-primary/10 text-on-surface'
                    : 'bg-error-container/60 text-on-surface'
            "
            :role="success ? 'status' : 'alert'"
        >
            <AppIcon
                :name="success ? 'check_circle' : 'error'"
                :size="18"
                :class="success ? 'text-primary' : 'text-error'"
                class="shrink-0"
            />
            <p class="text-body-sm font-body-sm flex-1">
                {{ success || error }}
            </p>
            <button
                type="button"
                aria-label="Cerrar aviso"
                class="text-on-surface-variant hover:text-on-surface rounded-lg p-1 transition-colors"
                @click="dismiss"
            >
                <AppIcon name="close" :size="16" />
            </button>
        </div>
    </div>
</template>
