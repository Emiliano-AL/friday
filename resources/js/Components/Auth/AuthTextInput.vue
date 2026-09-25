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
    <div class="flex flex-col gap-space-2xs">
        <div class="flex items-center justify-between">
            <label :for="id" class="text-label-sm text-on-surface">
                {{ label }}
            </label>
            <slot name="label-trailing" />
        </div>
        <div
            class="relative flex items-center rounded-lg bg-surface-container-low transition-all focus-within:bg-surface-container-lowest focus-within:shadow-md"
        >
            <AuthIcon
                :name="icon"
                :size="18"
                class="ml-space-md shrink-0 text-on-surface-variant"
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
                class="h-11 w-full bg-transparent px-space-sm text-body-md text-on-surface outline-none placeholder:text-outline"
            />
            <slot name="trailing" />
        </div>
        <p v-if="error" :id="`${id}-error`" class="text-body-sm text-error">
            {{ error }}
        </p>
    </div>
</template>
