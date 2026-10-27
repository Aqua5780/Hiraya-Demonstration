<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        hint?: string;
        tone?: 'navy' | 'gold' | 'green' | 'red';
    }>(),
    {
        hint: undefined,
        tone: 'navy',
    },
);

const tones = {
    navy: 'text-navy-300',
    gold: 'text-gold-500',
    green: 'text-laurel-500',
    red: 'text-red-400',
};
</script>

<template>
    <div class="stat-card">
        <div class="min-w-0">
            <p class="eyebrow">{{ label }}</p>
            <p class="font-display mt-2 truncate text-2xl font-bold text-navy-900">{{ value }}</p>
            <p v-if="hint" class="mt-1 text-xs leading-5 text-navy-400">{{ hint }}</p>
        </div>
        <div class="stat-icon" :class="tones[tone]">
            <slot name="icon" />
        </div>
    </div>
</template>
