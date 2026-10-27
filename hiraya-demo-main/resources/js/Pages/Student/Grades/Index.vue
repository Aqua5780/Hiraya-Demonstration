<script setup lang="ts">
import { GraduationCap } from '@lucide/vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface GradeRow {
    id: number;
    subject: string;
    subject_name: string;
    units: number;
    faculty: string | null;
    midterm: number | null;
    final: number | null;
    final_rating: number | null;
    remarks: string | null;
}

defineProps<{
    year: { id: number; name: string } | null;
    enrollment: { section: string | null; units: number } | null;
    grades: GradeRow[];
    average: number | null;
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="My grades" />

            <div v-if="enrollment" class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-navy-500">{{ year?.name }}</p>
                    <p class="font-display mt-1 text-lg font-bold text-navy-900">{{ enrollment.section ?? 'Enrolled' }}</p>
                </div>
                <div class="flex gap-6">
                    <div>
                        <p class="eyebrow">Units</p>
                        <p class="font-display mt-1 text-lg font-bold">{{ enrollment.units }}</p>
                    </div>
                    <div>
                        <p class="eyebrow">Average</p>
                        <p class="font-display mt-1 text-lg font-bold">{{ average !== null ? average.toFixed(2) : '—' }}</p>
                    </div>
                </div>
            </div>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[48rem]">
                        <thead><tr><th>Subject</th><th>Faculty</th><th>Units</th><th>Midterm</th><th>Final</th><th>Final rating</th><th>Remarks</th></tr></thead>
                        <tbody>
                            <tr v-for="grade in grades" :key="grade.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ grade.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ grade.subject_name }}</p>
                                </td>
                                <td class="text-xs">{{ grade.faculty ?? '—' }}</td>
                                <td>{{ grade.units }}</td>
                                <td>{{ grade.midterm ?? '—' }}</td>
                                <td>{{ grade.final ?? '—' }}</td>
                                <td class="font-semibold">{{ grade.final_rating ?? '—' }}</td>
                                <td>
                                    <Badge v-if="grade.remarks" :tone="grade.remarks === 'Passed' ? 'green' : 'red'">{{ grade.remarks }}</Badge>
                                    <span v-else class="text-xs text-navy-300">Pending</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-if="grades.length === 0" title="No grades yet" description="Grades appear after your faculty submits them.">
                    <template #icon><GraduationCap class="size-6" /></template>
                </EmptyState>
            </section>
        </div>
    </AppShell>
</template>
