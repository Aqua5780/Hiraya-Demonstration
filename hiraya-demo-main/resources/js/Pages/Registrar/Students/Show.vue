<script setup lang="ts">
import { ArrowLeft, BookOpen, CreditCard, Pencil, UserRound, Wallet } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { formatCurrency, formatDate, formatDateTime, titleCase } from '../../../lib/format';

interface StudentProfile {
    id: number;
    student_number: string;
    name: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    birth_date: string | null;
    gender: string | null;
    status: string;
    program: string | null;
    program_name: string | null;
    year_level: string | null;
    guardian_name: string | null;
    guardian_phone: string | null;
    account_status: string | null;
    last_login_at: string | null;
}

interface EnrollmentInfo {
    id: number;
    status: string;
    section: string | null;
    program: string | null;
    year_level: string | null;
    units: number;
    assessed_amount: number;
    balance: number;
    classes: {
        id: number;
        subject: string;
        subject_name: string;
        faculty: string | null;
        midterm: number | null;
        final: number | null;
        final_rating: number | null;
        remarks: string | null;
    }[];
}

interface PaymentRow {
    id: number;
    amount: number;
    method: string;
    reference_number: string;
    status: string;
    receipt_number: string | null;
    created_at: string;
}

interface LedgerRow {
    id: number;
    type: string;
    description: string;
    debit: number;
    credit: number;
    balance_after: number;
    created_at: string;
}

defineProps<{
    student: StudentProfile;
    currentYear: { id: number; name: string } | null;
    enrollment: EnrollmentInfo | null;
    payments: PaymentRow[];
    ledger: LedgerRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <Link href="/registrar/students" class="btn btn-ghost btn-sm">
                    <ArrowLeft class="size-4" /> Back
                </Link>
            </div>

            <PageHeader :title="student.name" :subtitle="`${student.student_number} · ${student.program ?? 'No program'} · ${student.year_level ?? 'No year level'}`">
                <template #actions>
                    <Link :href="`/registrar/students/${student.id}/edit`" class="btn btn-primary">
                        <Pencil class="size-4" /> Edit record
                    </Link>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <section class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                                <UserRound class="size-4" />
                            </span>
                            <h2 class="card-title">Student information</h2>
                        </div>
                        <Badge :tone="student.account_status === 'active' ? 'green' : student.account_status === 'pending' ? 'gold' : 'red'">
                            {{ student.account_status ? titleCase(student.account_status) : 'No account' }}
                        </Badge>
                    </div>

                    <dl class="grid gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                        <div><dt class="label">Email</dt><dd class="text-sm">{{ student.email ?? '—' }}</dd></div>
                        <div><dt class="label">Phone</dt><dd class="text-sm">{{ student.phone ?? '—' }}</dd></div>
                        <div><dt class="label">Birth date</dt><dd class="text-sm">{{ student.birth_date ? formatDate(student.birth_date) : '—' }}</dd></div>
                        <div><dt class="label">Gender</dt><dd class="text-sm">{{ student.gender ?? '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="label">Address</dt><dd class="text-sm">{{ student.address ?? '—' }}</dd></div>
                        <div><dt class="label">Guardian</dt><dd class="text-sm">{{ student.guardian_name ?? '—' }}</dd></div>
                        <div><dt class="label">Guardian phone</dt><dd class="text-sm">{{ student.guardian_phone ?? '—' }}</dd></div>
                        <div><dt class="label">Record status</dt><dd><Badge :tone="student.status === 'active' ? 'green' : 'gray'">{{ titleCase(student.status) }}</Badge></dd></div>
                        <div><dt class="label">Last login</dt><dd class="text-sm">{{ student.last_login_at ? formatDateTime(student.last_login_at) : 'Never' }}</dd></div>
                    </dl>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-md bg-gold-100 text-gold-800">
                                <Wallet class="size-4" />
                            </span>
                            <h2 class="card-title">Account summary</h2>
                        </div>
                        <Badge tone="navy">{{ currentYear?.name ?? 'No active year' }}</Badge>
                    </div>

                    <div v-if="enrollment" class="space-y-4 p-5">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-md bg-navy-50 p-3">
                                <p class="eyebrow">Section</p>
                                <p class="mt-1 text-sm font-bold">{{ enrollment.section ?? '—' }}</p>
                            </div>
                            <div class="rounded-md bg-navy-50 p-3">
                                <p class="eyebrow">Units</p>
                                <p class="mt-1 text-sm font-bold">{{ enrollment.units }}</p>
                            </div>
                            <div class="rounded-md bg-gold-50 p-3">
                                <p class="eyebrow">Balance</p>
                                <p class="mt-1 text-sm font-bold">{{ formatCurrency(enrollment.balance) }}</p>
                            </div>
                        </div>
                        <div class="text-xs text-navy-400">
                            Assessed {{ formatCurrency(enrollment.assessed_amount) }} · Status {{ titleCase(enrollment.status) }}
                        </div>
                    </div>

                    <EmptyState v-else title="Not enrolled" description="No enrollment record for the active academic year.">
                        <template #icon><Wallet class="size-6" /></template>
                    </EmptyState>
                </section>
            </div>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                            <BookOpen class="size-4" />
                        </span>
                        <h2 class="card-title">Classes and grades</h2>
                    </div>
                    <Badge tone="navy">{{ enrollment?.classes.length ?? 0 }} classes</Badge>
                </div>

                <div v-if="enrollment?.classes.length" class="table-shell">
                    <table class="data-table">
                        <thead><tr><th>Subject</th><th>Faculty</th><th>Midterm</th><th>Final</th><th>Final rating</th><th>Remarks</th></tr></thead>
                        <tbody>
                            <tr v-for="item in enrollment.classes" :key="item.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                </td>
                                <td class="text-xs">{{ item.faculty ?? '—' }}</td>
                                <td>{{ item.midterm ?? '—' }}</td>
                                <td>{{ item.final ?? '—' }}</td>
                                <td>{{ item.final_rating ?? '—' }}</td>
                                <td>
                                    <Badge v-if="item.remarks" :tone="item.remarks === 'Passed' ? 'green' : 'red'">{{ item.remarks }}</Badge>
                                    <span v-else class="text-xs text-navy-300">Pending</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-else title="No classes" description="Classes will appear after the student enrolls.">
                    <template #icon><BookOpen class="size-6" /></template>
                </EmptyState>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                                <CreditCard class="size-4" />
                            </span>
                            <h2 class="card-title">Recent payments</h2>
                        </div>
                    </div>

                    <div v-if="payments.length" class="divide-y divide-navy-50 px-5">
                        <div v-for="payment in payments" :key="payment.id" class="flex items-center justify-between gap-3 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-navy-800">{{ formatCurrency(payment.amount) }}</p>
                                <p class="mt-0.5 text-xs text-navy-400">{{ payment.method }} · {{ payment.reference_number }}</p>
                            </div>
                            <div class="text-right">
                                <Badge :tone="payment.status === 'verified' ? 'green' : payment.status === 'rejected' ? 'red' : 'gold'">{{ titleCase(payment.status) }}</Badge>
                                <p class="mt-1 text-[0.68rem] text-navy-400">{{ formatDateTime(payment.created_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <EmptyState v-else title="No payments" description="Payment records will appear here.">
                        <template #icon><CreditCard class="size-6" /></template>
                    </EmptyState>
                </section>

                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Ledger</h2>
                    </div>

                    <div v-if="ledger.length" class="table-shell">
                        <table class="data-table min-w-[32rem]">
                            <thead><tr><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                            <tbody>
                                <tr v-for="entry in ledger" :key="entry.id">
                                    <td>
                                        <p class="text-xs font-semibold text-navy-700">{{ entry.description }}</p>
                                        <p class="text-[0.68rem] text-navy-400">{{ formatDateTime(entry.created_at) }}</p>
                                    </td>
                                    <td class="text-xs">{{ formatCurrency(entry.debit) }}</td>
                                    <td class="text-xs">{{ formatCurrency(entry.credit) }}</td>
                                    <td class="text-xs font-semibold">{{ formatCurrency(entry.balance_after) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState v-else title="No ledger entries" description="Assessments and verified payments will appear here.">
                        <template #icon><Wallet class="size-6" /></template>
                    </EmptyState>
                </section>
            </div>
        </div>
    </AppShell>
</template>
