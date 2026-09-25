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
        class="group gap-space-sm flex cursor-pointer items-center select-none"
    >
        <span class="relative grid place-items-center">
            <input
                :id="id"
                v-model="model"
                type="checkbox"
                class="peer sr-only"
            />
            <span
                class="bg-surface-container-low peer-checked:bg-primary-container col-start-1 row-start-1 flex h-4 w-4 items-center justify-center rounded shadow-sm transition-colors"
            />
            <AuthIcon
                name="check"
                :size="12"
                :stroke-width="3"
                class="text-on-primary col-start-1 row-start-1 opacity-0 transition-opacity peer-checked:opacity-100"
            />
        </span>
        <span
            class="text-label-sm text-on-surface-variant group-hover:text-on-surface transition-colors"
        >
            {{ label }}
        </span>
    </label>
</template>
