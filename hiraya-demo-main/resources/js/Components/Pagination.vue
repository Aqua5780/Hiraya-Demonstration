<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number | null;
}>();
</script>

<template>
    <div class="flex flex-col gap-3 border-t border-navy-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-navy-400">
            <template v-if="from && to">
                Showing {{ from }}–{{ to }} of {{ total }}
            </template>
            <template v-else>No records</template>
        </p>

        <div v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="inline-flex min-w-8 items-center justify-center rounded border px-2.5 py-1.5 text-xs font-medium transition"
                    :class="
                        link.active
                            ? 'border-navy-900 bg-navy-900 text-white'
                            : 'border-navy-100 bg-white text-navy-600 hover:border-navy-300 hover:text-navy-900'
                    "
                    v-html="link.label"
                />
                <span
                    v-else
                    class="inline-flex min-w-8 items-center justify-center rounded border border-navy-50 px-2.5 py-1.5 text-xs text-navy-300"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
