<script setup lang="ts">
import { BookOpen, ClipboardList, Eye } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface ClassRow {
    id: number;
    offering_id: number;
    subject: string;
    subject_name: string;
    units: number;
    section: string;
    faculty: string | null;
    schedule: string;
    room: string | null;
    grade: number | null;
    remarks: string | null;
}

defineProps<{
    year: { id: number; name: string } | null;
    enrollment: { section: string | null; units: number } | null;
    classes: ClassRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="My classes" />

            <div v-if="enrollment" class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-navy-500">{{ year?.name }}</p>
                    <p class="font-display mt-1 text-lg font-bold text-navy-900">{{ enrollment.section ?? 'Enrolled' }}</p>
                </div>
                <p class="text-sm text-navy-500">{{ classes.length }} classes · {{ enrollment.units }} units</p>
            </div>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Subject</th><th>Faculty</th><th>Schedule</th><th>Room</th><th>Units</th><th>Grade</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="item in classes" :key="item.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                </td>
                                <td class="text-xs">{{ item.faculty ?? '—' }}</td>
                                <td class="text-xs">{{ item.schedule || 'Not scheduled' }}</td>
                                <td class="text-xs">{{ item.room ?? '—' }}</td>
                                <td>{{ item.units }}</td>
                                <td>
                                    <Badge v-if="item.grade !== null" :tone="item.grade >= 75 ? 'green' : 'red'">{{ item.grade.toFixed(2) }}</Badge>
                                    <span v-else class="text-xs text-navy-300">Pending</span>
                                </td>
                                <td>
                                    <div class="flex justify-end">
                                        <Link :href="`/student/classes/${item.offering_id}`" class="btn btn-secondary btn-sm">
                                            <Eye class="size-3.5" /> View class
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-if="classes.length === 0" title="No classes yet" description="Your classes will appear after enrollment.">
                    <template #icon><BookOpen class="size-6" /></template>
                </EmptyState>
            </section>

            <Link href="/student/enrollment" class="inline-flex items-center gap-1 text-xs font-semibold text-navy-500 hover:text-navy-800">
                <ClipboardList class="size-3.5" /> View enrollment
            </Link>
        </div>
    </AppShell>
</template>
