<script setup lang="ts">
import { ArrowLeft, Paperclip, ReceiptText, Wallet } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { formatCurrency, formatDateTime, titleCase } from '../../../lib/format';

interface PaymentRow {
    id: number;
    amount: number;
    method: string;
    reference_number: string;
    status: string;
    receipt_number: string | null;
    proof_name: string | null;
    remarks: string | null;
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
    year: { id: number; name: string } | null;
    student: {
        id: number;
        student_number: string;
        name: string;
        program: string | null;
        year_level: string | null;
        email: string | null;
        account_status: string | null;
    };
    enrollment: { section: string | null; units: number; assessed_amount: number; balance: number } | null;
    ledger: LedgerRow[];
    payments: PaymentRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <Link href="/finance/accounts" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to accounts
            </Link>

            <PageHeader :title="student.name" :subtitle="`${student.student_number} · ${student.program ?? '—'} · ${student.year_level ?? '—'}`">
                <template #actions>
                    <Link :href="`/finance/payments/create?student_id=${student.id}`" class="btn btn-primary">
                        <ReceiptText class="size-4" /> Post payment
                    </Link>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-4">
                <div class="card p-4">
                    <p class="eyebrow">Section</p>
                    <p class="mt-1 text-sm font-bold">{{ enrollment?.section ?? '—' }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Units</p>
                    <p class="mt-1 text-sm font-bold">{{ enrollment?.units ?? 0 }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Assessed</p>
                    <p class="mt-1 text-sm font-bold">{{ formatCurrency(enrollment?.assessed_amount ?? 0) }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Balance</p>
                    <p class="mt-1 text-sm font-bold">{{ formatCurrency(enrollment?.balance ?? 0) }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Payment history</h2>
                        <Badge tone="navy">{{ payments.length }} records</Badge>
                    </div>

                    <div v-if="payments.length" class="table-shell">
                        <table class="data-table min-w-[36rem]">
                            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th class="text-right">Documents</th></tr></thead>
                            <tbody>
                                <tr v-for="payment in payments" :key="payment.id">
                                    <td class="whitespace-nowrap text-xs">{{ formatDateTime(payment.created_at) }}</td>
                                    <td class="font-semibold">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="text-xs">{{ payment.method }}<br /><span class="font-mono text-[0.68rem] text-navy-400">{{ payment.reference_number }}</span></td>
                                    <td>
                                        <Badge :tone="payment.status === 'verified' ? 'green' : payment.status === 'rejected' ? 'red' : 'gold'">{{ titleCase(payment.status) }}</Badge>
                                        <p v-if="payment.remarks" class="mt-1 max-w-[10rem] text-[0.68rem] text-navy-400">{{ payment.remarks }}</p>
                                    </td>
                                    <td>
                                        <div class="flex flex-col items-end gap-1">
                                            <a
                                                v-if="payment.proof_name"
                                                :href="`/payments/${payment.id}/proof`"
                                                :title="payment.proof_name"
                                                download
                                                class="inline-flex max-w-[9rem] items-center gap-1 text-[0.68rem] font-semibold text-navy-600 hover:text-navy-900"
                                            >
                                                <Paperclip class="size-3 shrink-0" />
                                                <span class="truncate">{{ payment.proof_name }}</span>
                                            </a>
                                            <a v-if="payment.receipt_number" :href="`/payments/${payment.id}/receipt`" class="text-xs font-semibold text-navy-600 hover:text-navy-900">Receipt</a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState v-else title="No payments" description="Payment records will appear here.">
                        <template #icon><ReceiptText class="size-6" /></template>
                    </EmptyState>
                </section>

                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Ledger</h2>
                        <Badge tone="navy">{{ year?.name ?? 'No active year' }}</Badge>
                    </div>

                    <div v-if="ledger.length" class="table-shell">
                        <table class="data-table min-w-[36rem]">
                            <thead><tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                            <tbody>
                                <tr v-for="entry in ledger" :key="entry.id">
                                    <td class="whitespace-nowrap text-xs">{{ formatDateTime(entry.created_at) }}</td>
                                    <td class="text-xs">{{ entry.description }}</td>
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
