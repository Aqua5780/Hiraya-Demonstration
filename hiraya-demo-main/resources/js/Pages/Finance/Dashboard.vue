<script setup lang="ts">
import { ArrowRight, BadgeCheck, Banknote, CircleCheck, CircleDollarSign, ReceiptText, Users } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/AppShell.vue';
import Badge from '../../Components/Badge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import { formatCurrency, formatDateTime, timeAgo } from '../../lib/format';

interface PendingPayment {
    id: number;
    student: string;
    student_number: string;
    amount: number;
    method: string;
    reference_number: string;
    submitted_at: string;
}

interface VerifiedPayment {
    id: number;
    student: string;
    amount: number;
    receipt_number: string;
    verified_at: string;
}

defineProps<{
    year: { id: number; name: string } | null;
    stats: {
        pending: number;
        verified_today: number;
        collections: number;
        with_balance: number;
    };
    pendingPayments: PendingPayment[];
    recentVerified: VerifiedPayment[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader
                title="Finance"
            >
                <template #actions>
                    <Link href="/finance/payments/create" class="btn btn-secondary">
                        <ReceiptText class="size-4" />
                        Post walk-in payment
                    </Link>
                    <Link href="/finance/payments" class="btn btn-gold">
                        <BadgeCheck class="size-4" />
                        Review payments
                    </Link>
                </template>
            </PageHeader>

            <div class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-navy-500">Active academic year</p>
                    <p class="font-display mt-1 text-lg font-bold text-navy-900">{{ year?.name ?? 'Not configured' }}</p>
                </div>
                <p class="max-w-xl text-xs leading-5 text-navy-400">
                    Student payment submissions are pending until a finance user verifies the reference number and
                    proof. Verified payments generate a receipt and update the student ledger.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Pending verification" :value="stats.pending" hint="Student submissions awaiting review" tone="gold">
                    <template #icon><ReceiptText class="size-5" /></template>
                </StatCard>
                <StatCard label="Verified today" :value="formatCurrency(stats.verified_today)" hint="Payments posted today" tone="green">
                    <template #icon><CircleCheck class="size-5" /></template>
                </StatCard>
                <StatCard label="Total collections" :value="formatCurrency(stats.collections)" hint="Verified payments for the active year" tone="navy">
                    <template #icon><Banknote class="size-5" /></template>
                </StatCard>
                <StatCard label="Students with balance" :value="stats.with_balance" hint="Accounts with an outstanding balance" tone="red">
                    <template #icon><Users class="size-5" /></template>
                </StatCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Payment verification queue</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Student-submitted reference numbers and proofs</p>
                        </div>
                        <Badge tone="gold">{{ stats.pending }} pending</Badge>
                    </div>

                    <div v-if="pendingPayments.length" class="table-shell">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in pendingPayments" :key="payment.id">
                                    <td>
                                        <p class="font-semibold text-navy-800">{{ payment.student }}</p>
                                        <p class="mt-0.5 text-xs text-navy-400">{{ payment.student_number }}</p>
                                    </td>
                                    <td class="font-semibold text-navy-900">{{ formatCurrency(payment.amount) }}</td>
                                    <td>{{ payment.method }}</td>
                                    <td class="font-mono text-xs">{{ payment.reference_number }}</td>
                                    <td class="text-xs">{{ timeAgo(payment.submitted_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState
                        v-else
                        title="No pending payments"
                        description="Student payment submissions will appear here for verification."
                    >
                        <template #icon><BadgeCheck class="size-6" /></template>
                    </EmptyState>

                    <div class="border-t border-navy-50 px-5 py-3">
                        <Link href="/finance/payments" class="btn btn-ghost btn-sm w-full">
                            Review pending payments
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Recently verified</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Latest posted payments</p>
                        </div>
                        <Badge tone="green">Posted</Badge>
                    </div>

                    <div class="divide-y divide-navy-50 px-5">
                        <div v-for="payment in recentVerified" :key="payment.id" class="flex items-start gap-3 py-4">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-laurel-100 text-laurel-700">
                                <CircleDollarSign class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-navy-800">{{ payment.student }}</p>
                                <p class="mt-0.5 text-xs text-navy-400">
                                    {{ payment.receipt_number }} · {{ formatDateTime(payment.verified_at) }}
                                </p>
                            </div>
                            <Link :href="`/payments/${payment.id}/receipt`" class="font-display text-sm font-bold text-navy-900 underline-offset-4 hover:underline">
                                {{ formatCurrency(payment.amount) }}
                            </Link>
                        </div>

                        <EmptyState
                            v-if="recentVerified.length === 0"
                            title="No verified payments yet"
                            description="Payments posted by the finance office will appear here."
                        >
                            <template #icon><CircleCheck class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>
            </div>

            <div class="rounded-lg border border-navy-100 bg-white p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-navy-800">Deferred finance features</p>
                        <p class="mt-1 text-xs leading-5 text-navy-400">
                            Transaction reversal and payroll draft/approval are part of the paper but are deferred until
                            after the Core MVP.
                        </p>
                    </div>
                    <Badge tone="gray">Post-MVP</Badge>
                </div>
            </div>
        </div>
    </AppShell>
</template>
