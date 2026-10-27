<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    eyebrow?: string;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
            <h1 class="page-title" :class="{ 'mt-1': eyebrow }">{{ title }}</h1>
            <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
