<script setup lang="ts">
import { CalendarRange, Plus, ReceiptText, Users } from '@lucide/vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatCurrency, formatDate, titleCase } from '../../../lib/format';

interface PayrollRow {
    id: number;
    status: string;
    total_amount: number;
    prepared_by: { name: string } | null;
    approved_by: { name: string } | null;
    items_count: number;
}

interface PeriodRow {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: string;
    academic_year: { name: string } | null;
    payrolls: PayrollRow[];
}

interface RuleRow {
    id: number;
    name: string;
    basis: string;
    rate: number;
    allowance: number;
    deduction: number;
    effective_from: string;
    effective_to: string | null;
    status: string;
    faculty: { user?: { name: string } } | null;
}

const props = defineProps<{
    academicYears: { id: number; name: string }[];
    faculty: { id: number; name: string | null; employee_number: string }[];
    periods: PeriodRow[];
    rules: RuleRow[];
}>();

const showPeriodModal = ref(false);
const showRuleModal = ref(false);

const periodForm = useForm({
    academic_year_id: props.academicYears[0]?.id ?? 0,
    name: '',
    start_date: '',
    end_date: '',
    status: 'open',
});

const ruleForm = useForm({
    faculty_id: '',
    name: '',
    basis: 'hourly',
    rate: 0,
    allowance: 0,
    deduction: 0,
    effective_from: '',
    effective_to: '',
    status: 'active',
});

function createPeriod(): void {
    periodForm.post('/finance/payroll/periods', {
        preserveScroll: true,
        onSuccess: () => {
            showPeriodModal.value = false;
            periodForm.reset();
        },
    });
}

function createRule(): void {
    ruleForm.post('/finance/payroll/rules', {
        preserveScroll: true,
        onSuccess: () => {
            showRuleModal.value = false;
            ruleForm.reset();
        },
    });
}

function generate(period: PeriodRow): void {
    showConfirm(
        {
            title: 'Generate payroll draft',
            message: `Generate a payroll draft for ${period.name}?`,
            confirmLabel: 'Generate draft',
        },
        () => router.post(`/finance/payroll/periods/${period.id}/generate`, {}, { preserveScroll: true }),
    );
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Payroll">
                <template #actions>
                    <button type="button" class="btn btn-secondary" @click="showRuleModal = true">
                        <Plus class="size-4" /> Compensation rule
                    </button>
                    <button type="button" class="btn btn-primary" @click="showPeriodModal = true">
                        <Plus class="size-4" /> Pay period
                    </button>
                </template>
            </PageHeader>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <CalendarRange class="size-4 text-navy-500" />
                        <h2 class="card-title">Pay periods</h2>
                    </div>
                </div>

                <div class="divide-y divide-navy-50">
                    <div v-for="period in props.periods" :key="period.id" class="px-5 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-display text-lg font-bold text-navy-900">{{ period.name }}</p>
                                    <Badge :tone="period.status === 'open' ? 'green' : 'gray'">{{ titleCase(period.status) }}</Badge>
                                </div>
                                <p class="mt-1 text-xs text-navy-400">
                                    {{ period.academic_year?.name }} · {{ formatDate(period.start_date) }} – {{ formatDate(period.end_date) }}
                                </p>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" @click="generate(period)">
                                <ReceiptText class="size-3.5" /> Generate draft
                            </button>
                        </div>

                        <div v-if="period.payrolls.length" class="mt-4 overflow-hidden rounded-md border border-navy-100">
                            <table class="data-table">
                                <thead><tr><th>Prepared by</th><th>Status</th><th>Items</th><th>Total</th><th class="text-right">Open</th></tr></thead>
                                <tbody>
                                    <tr v-for="payroll in period.payrolls" :key="payroll.id">
                                        <td class="text-xs">{{ payroll.prepared_by?.name ?? '—' }}</td>
                                        <td><Badge :tone="payroll.status === 'posted' ? 'green' : payroll.status === 'returned' ? 'red' : 'gold'">{{ titleCase(payroll.status) }}</Badge></td>
                                        <td>{{ payroll.items_count }}</td>
                                        <td class="font-semibold">{{ formatCurrency(payroll.total_amount) }}</td>
                                        <td>
                                            <div class="flex justify-end">
                                                <Link :href="`/finance/payroll/${payroll.id}`" class="btn btn-ghost btn-sm">View</Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="mt-3 text-xs text-navy-400">No payroll draft for this period yet.</p>
                    </div>

                    <EmptyState v-if="props.periods.length === 0" title="No pay periods" description="Create a pay period to begin payroll processing.">
                        <template #icon><CalendarRange class="size-6" /></template>
                    </EmptyState>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <Users class="size-4 text-navy-500" />
                        <h2 class="card-title">Compensation rules</h2>
                    </div>
                </div>
                <div class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Name</th><th>Applies to</th><th>Basis</th><th>Rate</th><th>Allowance</th><th>Deduction</th><th>Effective</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="rule in props.rules" :key="rule.id">
                                <td class="font-semibold">{{ rule.name }}</td>
                                <td class="text-xs">{{ rule.faculty?.user?.name ?? 'All faculty' }}</td>
                                <td class="text-xs">{{ titleCase(rule.basis) }}</td>
                                <td>{{ formatCurrency(rule.rate) }}</td>
                                <td>{{ formatCurrency(rule.allowance) }}</td>
                                <td>{{ formatCurrency(rule.deduction) }}</td>
                                <td class="text-xs">{{ formatDate(rule.effective_from) }} – {{ rule.effective_to ? formatDate(rule.effective_to) : 'Open' }}</td>
                                <td><Badge :tone="rule.status === 'active' ? 'green' : 'gray'">{{ titleCase(rule.status) }}</Badge></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <Modal :show="showPeriodModal" title="New pay period" max-width="md" @close="showPeriodModal = false">
            <form id="pay-period-form" class="space-y-4 p-5" @submit.prevent="createPeriod">
                <div>
                    <label class="label" for="period-year">Academic year</label>
                    <select id="period-year" v-model="periodForm.academic_year_id" class="input">
                        <option v-for="year in props.academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                    </select>
                    <p v-if="periodForm.errors.academic_year_id" class="error-text">{{ periodForm.errors.academic_year_id }}</p>
                </div>
                <div>
                    <label class="label" for="period-name">Name</label>
                    <input id="period-name" v-model="periodForm.name" type="text" class="input" placeholder="1st Semester Payroll" />
                    <p v-if="periodForm.errors.name" class="error-text">{{ periodForm.errors.name }}</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="period-start">Start</label>
                        <input id="period-start" v-model="periodForm.start_date" type="date" class="input" />
                    </div>
                    <div>
                        <label class="label" for="period-end">End</label>
                        <input id="period-end" v-model="periodForm.end_date" type="date" class="input" />
                    </div>
                </div>
                <div>
                    <label class="label" for="period-status">Status</label>
                    <select id="period-status" v-model="periodForm.status" class="input">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showPeriodModal = false">Cancel</button>
                    <button type="submit" form="pay-period-form" class="btn btn-primary">Create period</button>
                </div>
            </template>
        </Modal>

        <Modal :show="showRuleModal" title="New compensation rule" max-width="xl" @close="showRuleModal = false">
            <form id="compensation-form" class="space-y-4 p-5" @submit.prevent="createRule">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="rule-name">Rule name</label>
                        <input id="rule-name" v-model="ruleForm.name" type="text" class="input" />
                    </div>
                    <div>
                        <label class="label" for="rule-faculty">Faculty</label>
                        <select id="rule-faculty" v-model="ruleForm.faculty_id" class="input">
                            <option value="">All faculty</option>
                            <option v-for="member in props.faculty" :key="member.id" :value="member.id">{{ member.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-4">
                    <div>
                        <label class="label" for="rule-basis">Basis</label>
                        <select id="rule-basis" v-model="ruleForm.basis" class="input">
                            <option value="hourly">Hourly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" for="rule-rate">Rate</label>
                        <input id="rule-rate" v-model.number="ruleForm.rate" type="number" min="0" step="0.01" class="input" />
                    </div>
                    <div>
                        <label class="label" for="rule-allowance">Allowance</label>
                        <input id="rule-allowance" v-model.number="ruleForm.allowance" type="number" min="0" step="0.01" class="input" />
                    </div>
                    <div>
                        <label class="label" for="rule-deduction">Deduction</label>
                        <input id="rule-deduction" v-model.number="ruleForm.deduction" type="number" min="0" step="0.01" class="input" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label" for="rule-from">Effective from</label>
                        <input id="rule-from" v-model="ruleForm.effective_from" type="date" class="input" />
                    </div>
                    <div>
                        <label class="label" for="rule-to">Effective to</label>
                        <input id="rule-to" v-model="ruleForm.effective_to" type="date" class="input" />
                    </div>
                    <div>
                        <label class="label" for="rule-status">Status</label>
                        <select id="rule-status" v-model="ruleForm.status" class="input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showRuleModal = false">Cancel</button>
                    <button type="submit" form="compensation-form" class="btn btn-primary">Create rule</button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
