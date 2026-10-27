<script setup lang="ts">
import { CreditCard, FileText, Paperclip, Upload, Wallet } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
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
    verified_at: string | null;
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

const props = defineProps<{
    year: { id: number; name: string } | null;
    enrollment: { section: string | null; assessed_amount: number; balance: number } | null;
    balance: number;
    payments: PaymentRow[];
    ledger: LedgerRow[];
    methods: { value: string; label: string }[];
}>();

const form = useForm({
    amount: '',
    method: 'gcash',
    reference_number: '',
    proof: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const pendingCount = computed(() => props.payments.filter((payment) => payment.status === 'pending').length);

function onFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.proof = input.files?.[0] ?? null;
}

function submit(): void {
    form.post('/student/payments', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.method = 'gcash';

            if (fileInput.value) {
                fileInput.value.value = '';
            }
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
            <PageHeader title="Payments" />

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="card p-5">
                    <p class="eyebrow">Assessment</p>
                    <p class="font-display mt-2 text-2xl font-bold text-navy-900">{{ formatCurrency(enrollment?.assessed_amount ?? 0) }}</p>
                </div>
                <div class="card p-5">
                    <p class="eyebrow">Remaining balance</p>
                    <p class="font-display mt-2 text-2xl font-bold text-navy-900">{{ formatCurrency(balance) }}</p>
                </div>
                <div class="card p-5">
                    <p class="eyebrow">Pending submissions</p>
                    <p class="font-display mt-2 text-2xl font-bold text-navy-900">{{ pendingCount }}</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
                <section class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-md bg-gold-100 text-gold-800">
                                <Upload class="size-4" />
                            </span>
                            <h2 class="card-title">Submit payment</h2>
                        </div>
                    </div>

                    <form v-if="balance > 0 && year" class="space-y-5 p-5" @submit.prevent="submit">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="payment-amount" class="label">Amount</label>
                                <input
                                    id="payment-amount"
                                    v-model="form.amount"
                                    type="number"
                                    min="0.01"
                                    :max="balance"
                                    step="0.01"
                                    class="input"
                                    placeholder="0.00"
                                />
                                <p v-if="form.errors.amount" class="error-text">{{ form.errors.amount }}</p>
                            </div>
                            <div>
                                <label for="payment-method" class="label">Method</label>
                                <select id="payment-method" v-model="form.method" class="input">
                                    <option v-for="method in props.methods" :key="method.value" :value="method.value">{{ method.label }}</option>
                                </select>
                                <p v-if="form.errors.method" class="error-text">{{ form.errors.method }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="payment-reference" class="label">Reference number</label>
                            <input
                                id="payment-reference"
                                v-model="form.reference_number"
                                type="text"
                                class="input"
                                placeholder="GCash, bank, or receipt reference"
                            />
                            <p v-if="form.errors.reference_number" class="error-text">{{ form.errors.reference_number }}</p>
                        </div>

                        <div>
                            <label for="payment-proof" class="label">Proof of payment</label>
                            <input
                                id="payment-proof"
                                ref="fileInput"
                                type="file"
                                class="input py-2 text-sm"
                                accept=".pdf,.jpg,.jpeg,.png"
                                @input="onFile"
                            />
                            <p class="hint">PDF, JPG, or PNG. Max 5 MB.</p>
                            <p v-if="form.errors.proof" class="error-text">{{ form.errors.proof }}</p>
                        </div>

                        <p class="rounded-md border border-navy-100 bg-navy-50/50 p-3 text-xs leading-5 text-navy-500">
                            Your balance will update only after the finance office verifies the payment.
                        </p>

                        <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                            {{ form.processing ? 'Submitting…' : 'Submit payment' }}
                        </button>
                    </form>

                    <div v-else class="empty-state">
                        <Wallet class="size-6 text-navy-300" />
                        <p class="text-sm font-semibold text-navy-800">
                            {{ balance > 0 ? 'No active academic year' : 'No outstanding balance' }}
                        </p>
                        <p class="text-xs text-navy-400">
                            {{ balance > 0 ? 'An active academic year is required to post a payment.' : 'Your account is fully settled for the active year.' }}
                        </p>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                                <FileText class="size-4" />
                            </span>
                            <h2 class="card-title">Payment history</h2>
                        </div>
                        <Badge tone="navy">{{ payments.length }} records</Badge>
                    </div>

                    <div v-if="payments.length" class="table-shell">
                        <table class="data-table min-w-[36rem]">
                            <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Status</th><th class="text-right">Documents</th></tr></thead>
                            <tbody>
                                <tr v-for="payment in payments" :key="payment.id">
                                    <td class="whitespace-nowrap text-xs">{{ formatDateTime(payment.created_at) }}</td>
                                    <td class="font-semibold">{{ formatCurrency(payment.amount) }}</td>
                                    <td class="text-xs">{{ payment.method }}</td>
                                    <td class="font-mono text-[0.68rem]">{{ payment.reference_number }}</td>
                                    <td>
                                        <Badge :tone="statusTone(payment.status)">{{ titleCase(payment.status) }}</Badge>
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

                    <div v-else class="empty-state">
                        <CreditCard class="size-6 text-navy-300" />
                        <p class="text-sm font-semibold text-navy-800">No payments yet</p>
                        <p class="text-xs text-navy-400">Your submitted payments and receipts will appear here.</p>
                    </div>
                </section>
            </div>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                            <Wallet class="size-4" />
                        </span>
                        <h2 class="card-title">Account ledger</h2>
                    </div>
                    <Badge tone="navy">{{ year?.name ?? 'No active year' }}</Badge>
                </div>

                <div v-if="ledger.length" class="table-shell">
                    <table class="data-table min-w-[40rem]">
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

                <div v-else class="empty-state">
                    <Wallet class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No ledger entries</p>
                    <p class="text-xs text-navy-400">Assessments and verified payments will appear here.</p>
                </div>
            </section>
        </div>
    </AppShell>
</template>
