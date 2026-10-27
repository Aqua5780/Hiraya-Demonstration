<script setup lang="ts">
import { Check, RotateCcw, X } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatCurrency, formatDateTime, titleCase } from '../../../lib/format';

interface PaymentRow {
    id: number;
    student_number: string;
    student: string;
    amount: number;
    receipt_number: string;
    reference_number: string;
    verified_at: string | null;
}

interface ReversalRow {
    id: number;
    amount: number;
    reason: string;
    status: string;
    reference: string | null;
    created_at: string;
    payment?: { student?: { student_number: string; first_name: string; last_name: string } };
    reversed_by?: { name: string };
    approved_by?: { name: string } | null;
}

defineProps<{
    payments: PaymentRow[];
    reversals: ReversalRow[];
}>();

const selectedPayment = ref<PaymentRow | null>(null);
const reason = ref('');

function requestReversal(payment: PaymentRow): void {
    selectedPayment.value = payment;
    reason.value = '';
}

function submitReversal(): void {
    if (! selectedPayment.value) return;

    router.post('/finance/reversals', {
        payment_id: selectedPayment.value.id,
        reason: reason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedPayment.value = null;
            reason.value = '';
        },
    });
}

function approve(reversal: ReversalRow): void {
    showConfirm(
        {
            title: 'Approve reversal',
            message: 'Approve this reversal? This will post a debit to the student ledger.',
            confirmLabel: 'Approve',
        },
        () => router.post(`/finance/reversals/${reversal.id}/approve`, {}, { preserveScroll: true }),
    );
}

function reject(reversal: ReversalRow): void {
    showConfirm(
        {
            title: 'Reject reversal',
            message: 'Provide a reason for rejecting this reversal.',
            inputLabel: 'Reason',
            inputRequired: true,
            confirmLabel: 'Reject reversal',
            tone: 'danger',
        },
        (remarks) => {
            if (! remarks) {
                return;
            }

            router.post(`/finance/reversals/${reversal.id}/reject`, { remarks }, { preserveScroll: true });
        },
    );
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Transaction reversal" />

            <section class="card">
                <div class="card-header"><h2 class="card-title">Verified payments</h2></div>
                <div class="table-shell">
                    <table class="data-table min-w-[56rem]">
                        <thead><tr><th>Student</th><th>Receipt</th><th>Amount</th><th>Verified</th><th class="text-right">Action</th></tr></thead>
                        <tbody>
                            <tr v-for="payment in payments" :key="payment.id">
                                <td>
                                    <p class="font-semibold">{{ payment.student }}</p>
                                    <p class="text-xs text-navy-400">{{ payment.student_number }} · {{ payment.reference_number }}</p>
                                </td>
                                <td class="font-mono text-xs">{{ payment.receipt_number }}</td>
                                <td>{{ formatCurrency(payment.amount) }}</td>
                                <td class="text-xs">{{ payment.verified_at ? formatDateTime(payment.verified_at) : '—' }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="requestReversal(payment)">
                                            <RotateCcw class="size-3.5" /> Reverse
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <EmptyState v-if="payments.length === 0" title="No verified payments" description="Verified payments will appear here for reversal.">
                    <template #icon><RotateCcw class="size-6" /></template>
                </EmptyState>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Reversal requests</h2></div>
                <div class="table-shell">
                    <table class="data-table min-w-[56rem]">
                        <thead><tr><th>Student</th><th>Amount</th><th>Reason</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="reversal in reversals" :key="reversal.id">
                                <td>{{ reversal.payment?.student?.first_name }} {{ reversal.payment?.student?.last_name }}</td>
                                <td>{{ formatCurrency(reversal.amount) }}</td>
                                <td class="max-w-[18rem] text-xs">{{ reversal.reason }}</td>
                                <td><Badge :tone="reversal.status === 'approved' ? 'green' : reversal.status === 'rejected' ? 'red' : 'gold'">{{ titleCase(reversal.status) }}</Badge></td>
                                <td>
                                    <div v-if="reversal.status === 'pending'" class="flex justify-end gap-2">
                                        <button type="button" class="btn btn-gold btn-sm" @click="approve(reversal)"><Check class="size-3.5" /> Approve</button>
                                        <button type="button" class="btn btn-ghost btn-sm text-red-600" @click="reject(reversal)"><X class="size-3.5" /> Reject</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div v-if="selectedPayment" class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/60 p-5">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-lift">
                <h2 class="font-display text-xl font-bold">Reverse payment</h2>
                <p class="mt-1 text-sm text-navy-500">
                    {{ selectedPayment.student }} · {{ formatCurrency(selectedPayment.amount) }}
                </p>
                <label for="reversal-reason" class="label mt-4">Reason</label>
                <textarea id="reversal-reason" v-model="reason" rows="3" class="input" />
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="selectedPayment = null">Cancel</button>
                    <button type="button" class="btn btn-danger" @click="submitReversal">Request reversal</button>
                </div>
            </div>
        </div>
    </AppShell>
</template>
