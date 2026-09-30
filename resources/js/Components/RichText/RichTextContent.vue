<script setup lang="ts">
import { blocksToHtml, normalizeToBlocks } from '@/utils/blocks';
import { computed } from 'vue';

const props = defineProps<{
    content: string | null;
}>();

const blocks = computed(() => normalizeToBlocks(props.content));

const html = computed(() => blocksToHtml(blocks.value));

const isEmpty = computed(() => blocks.value.blocks.length === 0);
</script>

<template>
    <!-- El HTML proviene de blocksToHtml: escape total + whitelist inline (contrato §4) -->
    <div
        v-if="!isEmpty"
        class="text-body-sm font-body-sm text-on-surface rich-text-content [&_h2]:text-headline-sm [&_h2]:font-headline-sm [&_h3]:text-body-md [&_h4]:text-body-md [&_blockquote]:border-outline-variant [&_blockquote]:text-on-surface-variant [&_pre]:bg-surface-container-low [&_code]:text-body-sm [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:pl-3 [&_blockquote]:italic [&_h3]:font-semibold [&_h4]:font-semibold [&_li]:ml-4 [&_ol]:list-decimal [&_p]:mb-2 [&_pre]:rounded-lg [&_pre]:p-2 [&_ul]:list-disc"
        v-html="html"
    />
</template>
