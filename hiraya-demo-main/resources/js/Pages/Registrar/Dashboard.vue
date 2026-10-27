<script setup lang="ts">
import { ArrowRight, ClipboardList, GraduationCap, Upload, Users } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/AppShell.vue';
import Badge from '../../Components/Badge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import { formatCurrency, formatDate, titleCase } from '../../lib/format';
import type { StudentSummary } from '../../types';

interface EnrollmentSummary {
    id: number;
    student: string;
    student_number: string;
    section: string | null;
    units: number;
    balance: number;
    enrolled_at: string;
}

defineProps<{
    year: { id: number; name: string } | null;
    stats: {
        students: number;
        enrolled: number;
        programs: number;
        unassigned: number;
    };
    recentStudents: StudentSummary[];
    recentEnrollments: EnrollmentSummary[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader
                title="Student records"
            >
                <template #actions>
                    <Link href="/registrar/import" class="btn btn-secondary">
                        <Upload class="size-4" />
                        Import records
                    </Link>
                    <Link href="/registrar/students" class="btn btn-gold">
                        <Users class="size-4" />
                        Student records
                    </Link>
                </template>
            </PageHeader>

            <div class="rounded-lg border border-navy-100 bg-white p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-navy-500">Active academic year</p>
                        <p class="font-display mt-1 text-lg font-bold text-navy-900">{{ year?.name ?? 'Not configured' }}</p>
                    </div>
                    <p class="max-w-xl text-xs leading-5 text-navy-400">
                        Student records are the source of truth for enrollment, class rosters, grades, and finance.
                        Changes made here will be reflected across every module.
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Student records" :value="stats.students" hint="All students in the institution" tone="navy">
                    <template #icon><Users class="size-5" /></template>
                </StatCard>
                <StatCard label="Enrolled" :value="stats.enrolled" hint="Students enrolled for the active year" tone="green">
                    <template #icon><GraduationCap class="size-5" /></template>
                </StatCard>
                <StatCard label="Programs" :value="stats.programs" hint="Active academic programs" tone="gold">
                    <template #icon><ClipboardList class="size-5" /></template>
                </StatCard>
                <StatCard label="Unassigned" :value="stats.unassigned" hint="Records without a program" tone="red">
                    <template #icon><Users class="size-5" /></template>
                </StatCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Recently added students</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Newest student records in the database</p>
                        </div>
                        <Badge tone="navy">{{ stats.students }} records</Badge>
                    </div>

                    <div v-if="recentStudents.length" class="table-shell">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Program</th>
                                    <th>Year level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in recentStudents" :key="student.id">
                                    <td>
                                        <p class="font-semibold text-navy-800">{{ student.name }}</p>
                                        <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }}</p>
                                    </td>
                                    <td>{{ student.program ?? '—' }}</td>
                                    <td>{{ student.year_level ?? '—' }}</td>
                                    <td>
                                        <Badge :tone="student.status === 'active' ? 'green' : 'gray'">
                                            {{ titleCase(student.status) }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState
                        v-else
                        title="No student records yet"
                        description="Create a student record or import a CSV file to begin."
                    >
                        <template #icon><Users class="size-6" /></template>
                    </EmptyState>

                    <div class="border-t border-navy-50 px-5 py-3">
                        <Link href="/registrar/students" class="btn btn-ghost btn-sm w-full">
                            Open student records
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Recent enrollments</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Latest student submissions for the active year</p>
                        </div>
                        <Badge tone="gold">{{ stats.enrolled }} enrolled</Badge>
                    </div>

                    <div class="divide-y divide-navy-50 px-5">
                        <div v-for="enrollment in recentEnrollments" :key="enrollment.id" class="py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-navy-800">{{ enrollment.student }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">
                                        {{ enrollment.student_number }} · {{ enrollment.section ?? 'No section' }}
                                    </p>
                                </div>
                                <p class="font-display text-sm font-bold text-navy-900">{{ enrollment.units }} units</p>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-navy-400">
                                <span>Balance: {{ formatCurrency(enrollment.balance) }}</span>
                                <span>{{ formatDate(enrollment.enrolled_at) }}</span>
                            </div>
                        </div>

                        <EmptyState
                            v-if="recentEnrollments.length === 0"
                            title="No enrollments yet"
                            description="Enrollment submissions will appear here once students enroll."
                        >
                            <template #icon><GraduationCap class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>
            </div>

        </div>
    </AppShell>
</template>
