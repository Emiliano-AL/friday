<script setup lang="ts">
import AuthIcon from '@/Components/Auth/AuthIcon.vue';

const model = defineModel<boolean>({ required: true });

defineProps<{
    id: string;
    label: string;
}>();
</script>

<template>
    <label
        :for="id"
        class="group flex cursor-pointer items-center gap-space-sm select-none"
    >
        <span class="relative grid place-items-center">
            <input
                :id="id"
                v-model="model"
                type="checkbox"
                class="peer sr-only"
            />
            <span
                class="col-start-1 row-start-1 flex h-4 w-4 items-center justify-center rounded bg-surface-container-low shadow-sm transition-colors peer-checked:bg-primary-container"
            />
            <AuthIcon
                name="check"
                :size="12"
                :stroke-width="3"
                class="col-start-1 row-start-1 text-on-primary opacity-0 transition-opacity peer-checked:opacity-100"
            />
        </span>
        <span
            class="text-label-sm text-on-surface-variant transition-colors group-hover:text-on-surface"
        >
            {{ label }}
        </span>
    </label>
</template>
