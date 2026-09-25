<script setup lang="ts">
import AuthIcon from '@/Components/Auth/AuthIcon.vue';
import type { AuthIconName } from '@/Components/Auth/AuthIcon.vue';
import { onMounted, useTemplateRef } from 'vue';

const model = defineModel<string>({ required: true });

const props = withDefaults(
    defineProps<{
        id: string;
        label: string;
        icon: AuthIconName;
        type?: string;
        placeholder?: string;
        error?: string;
        autocomplete?: string;
        required?: boolean;
        autofocus?: boolean;
    }>(),
    {
        type: 'text',
        placeholder: undefined,
        error: undefined,
        autocomplete: undefined,
        required: false,
        autofocus: false,
    },
);

const input = useTemplateRef<HTMLInputElement>('input');

onMounted(() => {
    if (props.autofocus) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="gap-space-2xs flex flex-col">
        <div class="flex items-center justify-between">
            <label :for="id" class="text-label-sm text-on-surface">
                {{ label }}
            </label>
            <slot name="label-trailing" />
        </div>
        <div
            class="bg-surface-container-low focus-within:bg-surface-container-lowest relative flex items-center rounded-lg transition-all focus-within:shadow-md"
        >
            <AuthIcon
                :name="icon"
                :size="18"
                class="ml-space-md text-on-surface-variant shrink-0"
            />
            <input
                :id="id"
                ref="input"
                v-model="model"
                :type="type"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :required="required"
                :aria-invalid="error ? true : undefined"
                :aria-describedby="error ? `${id}-error` : undefined"
                class="px-space-sm text-body-md text-on-surface placeholder:text-outline h-11 w-full bg-transparent outline-none"
            />
            <slot name="trailing" />
        </div>
        <p v-if="error" :id="`${id}-error`" class="text-body-sm text-error">
            {{ error }}
        </p>
    </div>
</template>
