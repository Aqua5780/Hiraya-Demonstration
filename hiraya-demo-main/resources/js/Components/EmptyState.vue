<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div class="empty-state">
        <div class="flex size-14 items-center justify-center rounded-lg bg-navy-50 text-navy-400">
            <slot name="icon" />
        </div>
        <div>
            <p class="font-semibold text-navy-800">{{ title }}</p>
            <p v-if="description" class="mx-auto mt-1 max-w-md text-sm leading-6 text-navy-400">{{ description }}</p>
        </div>
        <slot name="action" />
    </div>
</template>
