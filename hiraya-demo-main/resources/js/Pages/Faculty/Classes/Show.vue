<script setup lang="ts">
import { ArrowLeft, ArrowRight, BookOpenCheck, Clock, MapPin } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface RosterRow {
    id: number;
    student_number: string;
    name: string;
    program: string | null;
    year_level: string | null;
    status: string;
    midterm: number | null;
    final: number | null;
    final_rating: number | null;
    remarks: string | null;
}

defineProps<{
    classOffering: {
        id: number;
        subject: string;
        subject_name: string;
        units: number;
        section: string;
        program: string;
        year_level: string;
        schedule: string;
        room: string | null;
        academic_year: string;
    };
    roster: RosterRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <Link href="/faculty/classes" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to classes
            </Link>

            <PageHeader
                :title="`${classOffering.subject} · ${classOffering.section}`"
                :subtitle="`${classOffering.subject_name} · ${classOffering.program} ${classOffering.year_level} · ${classOffering.academic_year}`"
            >
                <template #actions>
                    <Link :href="`/faculty/grades/${classOffering.id}`" class="btn btn-primary">
                        <BookOpenCheck class="size-4" /> Grade sheet
                    </Link>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="card p-4">
                    <p class="eyebrow">Schedule</p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold"><Clock class="size-4 text-navy-300" />{{ classOffering.schedule || 'Not scheduled' }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Room</p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold"><MapPin class="size-4 text-navy-300" />{{ classOffering.room ?? 'Room TBA' }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Class size</p>
                    <p class="mt-1 text-sm font-semibold">{{ roster.length }} students · {{ classOffering.units }} units</p>
                </div>
            </div>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Class roster</h2>
                    <Badge tone="navy">{{ roster.length }} students</Badge>
                </div>

                <div v-if="roster.length" class="table-shell">
                    <table class="data-table min-w-[48rem]">
                        <thead><tr><th>Student</th><th>Program</th><th>Year level</th><th>Midterm</th><th>Final</th><th>Final rating</th><th>Remarks</th></tr></thead>
                        <tbody>
                            <tr v-for="student in roster" :key="student.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ student.name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }}</p>
                                </td>
                                <td>{{ student.program ?? '—' }}</td>
                                <td>{{ student.year_level ?? '—' }}</td>
                                <td>{{ student.midterm ?? '—' }}</td>
                                <td>{{ student.final ?? '—' }}</td>
                                <td>{{ student.final_rating ?? '—' }}</td>
                                <td>
                                    <Badge v-if="student.remarks" :tone="student.remarks === 'Passed' ? 'green' : 'red'">{{ student.remarks }}</Badge>
                                    <span v-else class="text-xs text-navy-300">Pending</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-else title="No students enrolled" description="Students will appear here after they enroll in this class.">
                    <template #icon><BookOpenCheck class="size-6" /></template>
                </EmptyState>

                <div class="border-t border-navy-50 px-5 py-3">
                    <Link :href="`/faculty/grades/${classOffering.id}`" class="btn btn-ghost btn-sm">
                        Open grade sheet
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>
            </section>
        </div>
    </AppShell>
</template>
