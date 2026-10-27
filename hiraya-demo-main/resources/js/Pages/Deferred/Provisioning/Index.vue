<script setup lang="ts">
import { Check, Mail, UserPlus } from '@lucide/vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatDateTime, titleCase } from '../../../lib/format';
import type { SharedPageProps } from '../../../types';

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    program: string | null;
    year_level: string | null;
    preview_email: string;
}

interface LogRow {
    id: number;
    email: string;
    status: string;
    message: string | null;
    created_at: string;
    student?: { student_number: string; first_name: string; last_name: string };
    created_by?: { name: string } | null;
}

const props = defineProps<{
    students: StudentRow[];
    logs: LogRow[];
}>();

const page = usePage<SharedPageProps>();
const activationUrl = computed(() => page.props.flash.demo_activation_url);

function provision(student: StudentRow): void {
    showConfirm(
        {
            title: 'Create student account',
            message: `Create an account for ${student.name} using ${student.preview_email}?`,
            confirmLabel: 'Create account',
        },
        () => router.post('/admin/provisioning', { student_id: student.id }, { preserveScroll: true }),
    );
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Student accounts" />

            <div v-if="activationUrl" class="rounded-lg border border-gold-200 bg-gold-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-gold-800">Demo activation link</p>
                <a :href="activationUrl" target="_blank" class="mt-1 block break-all text-xs font-semibold text-navy-700 underline">
                    {{ activationUrl }}
                </a>
            </div>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <UserPlus class="size-4 text-navy-500" />
                        <h2 class="card-title">Students without accounts</h2>
                    </div>
                    <Badge tone="navy">{{ props.students.length }} students</Badge>
                </div>

                <div v-if="props.students.length" class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Student</th><th>Program</th><th>Preview email</th><th class="text-right">Action</th></tr></thead>
                        <tbody>
                            <tr v-for="student in props.students" :key="student.id">
                                <td>
                                    <p class="font-semibold">{{ student.name }}</p>
                                    <p class="text-xs text-navy-400">{{ student.student_number }}</p>
                                </td>
                                <td class="text-xs">{{ student.program }} {{ student.year_level }}</td>
                                <td class="font-mono text-xs">{{ student.preview_email }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="provision(student)">
                                            <Mail class="size-3.5" /> Provision
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-else title="All students have accounts" description="New students without accounts will appear here.">
                    <template #icon><Check class="size-6" /></template>
                </EmptyState>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Provisioning log</h2></div>
                <div v-if="props.logs.length" class="table-shell">
                    <table class="data-table min-w-[48rem]">
                        <thead><tr><th>Student</th><th>Email</th><th>Status</th><th>By</th><th>Date</th></tr></thead>
                        <tbody>
                            <tr v-for="log in props.logs" :key="log.id">
                                <td class="text-xs">{{ log.student?.first_name }} {{ log.student?.last_name }}</td>
                                <td class="font-mono text-xs">{{ log.email }}</td>
                                <td><Badge :tone="log.status === 'created' ? 'green' : 'gold'">{{ titleCase(log.status) }}</Badge></td>
                                <td class="text-xs">{{ log.created_by?.name ?? '—' }}</td>
                                <td class="text-xs">{{ formatDateTime(log.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-else title="No provisioning activity" description="Provisioned student accounts will be logged here.">
                    <template #icon><Mail class="size-6" /></template>
                </EmptyState>
            </section>
        </div>
    </AppShell>
</template>
