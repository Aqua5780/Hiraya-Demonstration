<script setup lang="ts">
import { ArrowRight, BookOpenCheck } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface ClassRow {
    id: number;
    subject: string;
    subject_name: string;
    section: string;
    program: string;
    year_level: string;
    students: number;
    graded: number;
    pending: number;
}

defineProps<{
    year: { id: number; name: string } | null;
    classes: ClassRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Grade sheet" />

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Subject</th><th>Section</th><th>Students</th><th>Graded</th><th>Pending</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="item in classes" :key="item.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                </td>
                                <td>
                                    <p>{{ item.program }} {{ item.year_level }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.section }}</p>
                                </td>
                                <td>{{ item.students }}</td>
                                <td><Badge tone="green">{{ item.graded }}</Badge></td>
                                <td><Badge :tone="item.pending > 0 ? 'gold' : 'gray'">{{ item.pending }}</Badge></td>
                                <td>
                                    <div class="flex justify-end">
                                        <Link :href="`/faculty/grades/${item.id}`" class="btn btn-secondary btn-sm">
                                            Open <ArrowRight class="size-3.5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-if="classes.length === 0" title="No classes assigned" description="Grade sheets will appear for your assigned classes.">
                    <template #icon><BookOpenCheck class="size-6" /></template>
                </EmptyState>
            </section>
        </div>
    </AppShell>
</template>
