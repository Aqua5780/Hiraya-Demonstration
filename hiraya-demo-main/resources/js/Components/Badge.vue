<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        tone?: 'navy' | 'gold' | 'green' | 'red' | 'gray';
        label?: string;
    }>(),
    {
        tone: 'navy',
        label: undefined,
    },
);

const toneClass = computed(() => ({
    navy: 'badge-navy',
    gold: 'badge-gold',
    green: 'badge-green',
    red: 'badge-red',
    gray: 'badge-gray',
}[props.tone]));
</script>

<template>
    <span class="badge" :class="toneClass">
        <slot>{{ label }}</slot>
    </span>
</template>
