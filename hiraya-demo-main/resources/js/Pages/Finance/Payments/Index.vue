<script setup lang="ts">
import { BadgeCheck, Paperclip, ReceiptText, Search, X } from '@lucide/vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatCurrency, formatDateTime, titleCase } from '../../../lib/format';

interface PaymentRow {
    id: number;
    student_number: string;
    student_name: string;
    amount: number;
    method: string;
    reference_number: string;
    status: string;
    proof_name: string | null;
    remarks: string | null;
    receipt_number: string | null;
    created_at: string;
    verified_at: string | null;
    verified_by: string | null;
    year: string | null;
}

const props = defineProps<{
    payments: {
        data: PaymentRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | undefined>;
    pendingCount: number;
}>();

const filters = ref({
    status: props.filters.status ?? 'pending',
    q: props.filters.q ?? '',
});

const rejecting = ref<PaymentRow | null>(null);
const rejectForm = useForm({ remarks: '' });

const showRejectModal = computed(() => rejecting.value !== null);

function applyFilters(): void {
    router.get('/finance/payments', filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function verify(payment: PaymentRow): void {
    showConfirm(
        {
            title: 'Verify payment',
            message: `Verify ${formatCurrency(payment.amount)} from ${payment.student_name}?`,
            confirmLabel: 'Verify',
        },
        () => router.post(`/finance/payments/${payment.id}/verify`, {}, { preserveScroll: true }),
    );
}

function openReject(payment: PaymentRow): void {
    rejecting.value = payment;
    rejectForm.reset();
    rejectForm.clearErrors();
}

function reject(): void {
    if (! rejecting.value) {
        return;
    }

    rejectForm.post(`/finance/payments/${rejecting.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            rejecting.value = null;
            rejectForm.reset();
        },
    });
}

function statusTone(status: string): 'green' | 'gold' | 'red' {
    if (status === 'verified') return 'green';
    if (status === 'pending') return 'gold';
    return 'red';
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Payment verification">
                <template #actions>
                    <Link href="/finance/payments/create" class="btn btn-primary">
                        <ReceiptText class="size-4" /> Post payment
                    </Link>
                </template>
            </PageHeader>

            <div class="card p-4">
                <form class="grid gap-3 sm:grid-cols-[1fr_0.45fr_auto]" @submit.prevent="applyFilters">
                    <div>
                        <label for="payment-search" class="label">Search student</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                            <input id="payment-search" v-model="filters.q" type="search" class="input pl-10" placeholder="Name or student number" />
                        </div>
                    </div>
                    <div>
                        <label for="payment-status-filter" class="label">Status</label>
                        <select id="payment-status-filter" v-model="filters.status" class="input">
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-secondary w-full">Filter</button>
                    </div>
                </form>
            </div>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Payments</h2>
                    <Badge tone="gold">{{ props.pendingCount }} pending</Badge>
                </div>

                <div class="table-shell">
                    <table class="data-table min-w-[68rem]">
                        <thead><tr><th>Student</th><th>Amount</th><th>Method</th><th>Reference</th><th>Submitted</th><th>Status</th><th>Proof</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="payment in props.payments.data" :key="payment.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ payment.student_name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ payment.student_number }} · {{ payment.year }}</p>
                                </td>
                                <td class="font-semibold">{{ formatCurrency(payment.amount) }}</td>
                                <td class="text-xs">{{ payment.method }}</td>
                                <td class="font-mono text-[0.68rem]">{{ payment.reference_number }}</td>
                                <td class="whitespace-nowrap text-xs">{{ formatDateTime(payment.created_at) }}</td>
                                <td>
                                    <Badge :tone="statusTone(payment.status)">{{ titleCase(payment.status) }}</Badge>
                                    <p v-if="payment.remarks" class="mt-1 max-w-[12rem] text-[0.68rem] text-navy-400">{{ payment.remarks }}</p>
                                </td>
                                <td>
                                    <a
                                        v-if="payment.proof_name"
                                        :href="`/payments/${payment.id}/proof`"
                                        :title="payment.proof_name"
                                        download
                                        class="inline-flex max-w-[10rem] items-center gap-1 text-xs font-semibold text-navy-600 hover:text-navy-900"
                                    >
                                        <Paperclip class="size-3.5 shrink-0" />
                                        <span class="truncate">{{ payment.proof_name }}</span>
                                    </a>
                                    <span v-else class="text-xs text-navy-300">None</span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <template v-if="payment.status === 'pending'">
                                            <button type="button" class="btn btn-gold btn-sm" @click="verify(payment)">
                                                <BadgeCheck class="size-3.5" /> Verify
                                            </button>
                                            <button type="button" class="btn btn-ghost btn-sm text-red-600" @click="openReject(payment)">
                                                <X class="size-3.5" /> Reject
                                            </button>
                                        </template>
                                        <a v-else-if="payment.receipt_number" :href="`/payments/${payment.id}/receipt`" class="btn btn-secondary btn-sm">
                                            Receipt
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-if="props.payments.data.length === 0" title="No payments found" description="Submitted student payments will appear here.">
                    <template #icon><BadgeCheck class="size-6" /></template>
                </EmptyState>

                <Pagination
                    :links="props.payments.links"
                    :from="props.payments.from"
                    :to="props.payments.to"
                    :total="props.payments.total"
                />
            </section>
        </div>

        <Modal :show="showRejectModal" title="Reject payment" max-width="md" @close="rejecting = null">
            <form id="reject-payment-form" class="space-y-4 p-5" @submit.prevent="reject">
                <p class="text-sm text-navy-600">
                    {{ rejecting?.student_name }} · {{ formatCurrency(rejecting?.amount ?? 0) }}
                </p>
                <div>
                    <label for="reject-remarks" class="label">Reason</label>
                    <textarea id="reject-remarks" v-model="rejectForm.remarks" rows="3" class="input" placeholder="Explain why the payment cannot be verified." />
                    <p v-if="rejectForm.errors.remarks" class="error-text">{{ rejectForm.errors.remarks }}</p>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="rejecting = null">Cancel</button>
                    <button type="submit" form="reject-payment-form" class="btn btn-danger" :disabled="rejectForm.processing">
                        {{ rejectForm.processing ? 'Rejecting…' : 'Reject payment' }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
