<script setup lang="ts">
import { ArrowLeft, Check, RotateCcw, Save, Send } from '@lucide/vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatCurrency, formatDateTime, titleCase } from '../../../lib/format';

const props = defineProps<{
    payroll: {
        id: number;
        status: string;
        total_amount: number;
        submitted_at: string | null;
        approved_at: string | null;
        remarks: string | null;
        pay_period: { name: string; academic_year?: { name: string } } | null;
        prepared_by: { name: string } | null;
        approved_by: { name: string } | null;
        items: {
            id: number;
            teaching_units: number;
            rate: number;
            allowance: number;
            deduction: number;
            gross_amount: number;
            net_amount: number;
            faculty?: { user?: { name: string } };
        }[];
    };
}>();

const remarks = ref('');
const showReturn = ref(false);

function submit(): void {
    router.post(`/finance/payroll/${props.payroll.id}/submit`, {}, { preserveScroll: true });
}

function approve(): void {
    showConfirm(
        {
            title: 'Approve payroll',
            message: 'Approve this payroll?',
            confirmLabel: 'Approve',
        },
        () => router.post(`/finance/payroll/${props.payroll.id}/approve`, { remarks: remarks.value }, { preserveScroll: true }),
    );
}

function post(): void {
    showConfirm(
        {
            title: 'Post payroll',
            message: 'Post this payroll to the register?',
            confirmLabel: 'Post',
        },
        () => router.post(`/finance/payroll/${props.payroll.id}/post`, {}, { preserveScroll: true }),
    );
}

function returnToPreparer(): void {
    router.post(`/finance/payroll/${props.payroll.id}/return`, { remarks: remarks.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showReturn.value = false;
            remarks.value = '';
        },
    });
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <Link href="/finance/payroll" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to payroll
            </Link>

            <PageHeader
                :title="props.payroll.pay_period?.name ?? 'Payroll'"
                :subtitle="props.payroll.pay_period?.academic_year?.name ?? ''"
            >
                <template #actions>
                    <button v-if="['draft', 'returned'].includes(props.payroll.status)" type="button" class="btn btn-primary" @click="submit">
                        <Send class="size-4" /> Submit
                    </button>
                    <button v-if="props.payroll.status === 'submitted'" type="button" class="btn btn-gold" @click="approve">
                        <Check class="size-4" /> Approve
                    </button>
                    <button v-if="props.payroll.status === 'approved'" type="button" class="btn btn-primary" @click="post">
                        <Save class="size-4" /> Post
                    </button>
                    <button v-if="props.payroll.status === 'submitted'" type="button" class="btn btn-secondary" @click="showReturn = !showReturn">
                        <RotateCcw class="size-4" /> Return
                    </button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-4">
                <div class="card p-4"><p class="eyebrow">Status</p><Badge :tone="props.payroll.status === 'posted' ? 'green' : 'gold'">{{ titleCase(props.payroll.status) }}</Badge></div>
                <div class="card p-4"><p class="eyebrow">Total</p><p class="font-display mt-1 text-xl font-bold">{{ formatCurrency(props.payroll.total_amount) }}</p></div>
                <div class="card p-4"><p class="eyebrow">Prepared by</p><p class="mt-1 text-sm">{{ props.payroll.prepared_by?.name ?? '—' }}</p></div>
                <div class="card p-4"><p class="eyebrow">Approved by</p><p class="mt-1 text-sm">{{ props.payroll.approved_by?.name ?? '—' }}</p></div>
            </div>

            <section v-if="showReturn" class="card p-5">
                <label for="return-remarks" class="label">Return remarks</label>
                <textarea id="return-remarks" v-model="remarks" rows="3" class="input" />
                <div class="mt-3 flex justify-end">
                    <button type="button" class="btn btn-danger" @click="returnToPreparer">Return payroll</button>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Payroll items</h2>
                    <Badge tone="navy">{{ props.payroll.items.length }} faculty</Badge>
                </div>
                <div class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Faculty</th><th>Units</th><th>Rate</th><th>Allowance</th><th>Deduction</th><th>Gross</th><th>Net</th></tr></thead>
                        <tbody>
                            <tr v-for="item in props.payroll.items" :key="item.id">
                                <td class="font-semibold">{{ item.faculty?.user?.name ?? '—' }}</td>
                                <td>{{ item.teaching_units }}</td>
                                <td>{{ formatCurrency(item.rate) }}</td>
                                <td>{{ formatCurrency(item.allowance) }}</td>
                                <td>{{ formatCurrency(item.deduction) }}</td>
                                <td>{{ formatCurrency(item.gross_amount) }}</td>
                                <td class="font-semibold">{{ formatCurrency(item.net_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <p v-if="props.payroll.remarks" class="text-xs text-navy-500">Remarks: {{ props.payroll.remarks }}</p>
            <p v-if="props.payroll.approved_at" class="text-xs text-navy-400">Approved {{ formatDateTime(props.payroll.approved_at) }}</p>
        </div>
    </AppShell>
</template>
