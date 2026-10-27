<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        light?: boolean;
        compact?: boolean;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        light: false,
        compact: false,
        size: 'md',
    },
);

const sealClass = computed(() => ({
    sm: 'size-9',
    md: 'size-12',
    lg: 'size-16',
}[props.size]));

const titleClass = computed(() => ({
    sm: 'text-lg',
    md: 'text-xl',
    lg: 'text-2xl',
}[props.size]));
</script>

<template>
    <Link href="/" class="inline-flex items-center gap-3">
        <span class="seal-ring shrink-0">
            <img
                src="/images/gstc-seal.jpg"
                alt="Good Shepherd of Taguig Colleges seal"
                class="rounded-full object-cover"
                :class="sealClass"
            />
        </span>
        <span v-if="!compact" class="leading-tight">
            <span
                class="font-display block font-bold tracking-tight"
                :class="[titleClass, light ? 'text-white' : 'text-navy-900']"
            >
                Hiraya
            </span>
        </span>
    </Link>
</template>
