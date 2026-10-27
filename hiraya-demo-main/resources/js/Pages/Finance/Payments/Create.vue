<script setup lang="ts">
import { ArrowRight, ReceiptText, Search, Wallet } from '@lucide/vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { formatCurrency } from '../../../lib/format';

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    program: string | null;
    section: string | null;
    balance: number;
}

interface SelectedStudent {
    id: number;
    student_number: string;
    name: string;
    program: string | null;
    section: string | null;
    balance: number;
}

const props = defineProps<{
    year: { id: number; name: string } | null;
    students: StudentRow[];
    selectedStudent: SelectedStudent | null;
    filters: Record<string, string | undefined>;
    methods: { value: string; label: string }[];
}>();

const search = ref(props.filters.q ?? '');

const form = useForm({
    student_id: props.selectedStudent?.id ?? 0,
    amount: props.selectedStudent?.balance ? String(props.selectedStudent.balance) : '',
    method: 'cash',
    reference_number: '',
    remarks: '',
});

watch(
    () => props.selectedStudent,
    (student) => {
        form.student_id = student?.id ?? 0;
        form.amount = student?.balance ? String(student.balance) : '';
        form.reference_number = '';
        form.remarks = '';
        form.clearErrors();
    },
);

function applySearch(): void {
    router.get('/finance/payments/create', { q: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function submit(): void {
    form.post('/finance/payments', {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Post payment">
                <template #actions>
                    <Link href="/finance/payments" class="btn btn-secondary">
                        <ReceiptText class="size-4" /> Payment verification
                    </Link>
                </template>
            </PageHeader>

            <div v-if="!year" class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900">
                No academic year is active.
            </div>

            <div v-else class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Find student</h2>
                        <Badge tone="navy">{{ year.name }}</Badge>
                    </div>
                    <div class="p-4">
                        <form class="flex gap-2" @submit.prevent="applySearch">
                            <div class="relative flex-1">
                                <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                                <input v-model="search" type="search" class="input pl-10" placeholder="Name or student number" />
                            </div>
                            <button type="submit" class="btn btn-secondary">Search</button>
                        </form>

                        <div v-if="props.students.length" class="mt-4 divide-y divide-navy-50">
                            <Link
                                v-for="student in props.students"
                                :key="student.id"
                                :href="`/finance/payments/create?student_id=${student.id}`"
                                class="flex items-center justify-between gap-3 py-3 transition hover:bg-cream-50"
                            >
                                <div>
                                    <p class="text-sm font-semibold text-navy-800">{{ student.name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }} · {{ student.program }} {{ student.section }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-navy-900">{{ formatCurrency(student.balance) }}</p>
                                    <p class="text-[0.68rem] text-navy-400">Balance</p>
                                </div>
                            </Link>
                        </div>

                        <EmptyState v-else title="No students with balances" description="Students with an outstanding balance will appear here.">
                            <template #icon><Wallet class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Payment details</h2>
                    </div>

                    <div v-if="props.selectedStudent" class="space-y-5 p-5">
                        <div class="rounded-md bg-navy-50 p-4">
                            <p class="text-sm font-bold text-navy-900">{{ props.selectedStudent.name }}</p>
                            <p class="mt-0.5 text-xs text-navy-400">{{ props.selectedStudent.student_number }} · {{ props.selectedStudent.program }} {{ props.selectedStudent.section }}</p>
                            <p class="font-display mt-3 text-2xl font-bold text-navy-900">{{ formatCurrency(props.selectedStudent.balance) }}</p>
                            <p class="text-xs text-navy-400">Outstanding balance</p>
                        </div>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <label for="post-amount" class="label">Amount</label>
                                <input id="post-amount" v-model="form.amount" type="number" min="0.01" :max="props.selectedStudent.balance" step="0.01" class="input" />
                                <p v-if="form.errors.amount" class="error-text">{{ form.errors.amount }}</p>
                            </div>
                            <div>
                                <label for="post-method" class="label">Method</label>
                                <select id="post-method" v-model="form.method" class="input">
                                    <option v-for="method in props.methods" :key="method.value" :value="method.value">{{ method.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="post-reference" class="label">Reference number</label>
                                <input id="post-reference" v-model="form.reference_number" type="text" class="input" placeholder="Optional for cash" />
                                <p v-if="form.errors.reference_number" class="error-text">{{ form.errors.reference_number }}</p>
                            </div>
                            <div>
                                <label for="post-remarks" class="label">Remarks</label>
                                <textarea id="post-remarks" v-model="form.remarks" rows="2" class="input" placeholder="Optional" />
                            </div>
                            <p v-if="form.errors.student_id" class="error-text">{{ form.errors.student_id }}</p>
                            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                                {{ form.processing ? 'Posting…' : 'Post payment' }}
                                <ArrowRight v-if="!form.processing" class="size-4" />
                            </button>
                        </form>
                    </div>

                    <EmptyState v-else title="Select a student" description="Choose a student from the list to post a payment.">
                        <template #icon><Wallet class="size-6" /></template>
                    </EmptyState>
                </section>
            </div>
        </div>
    </AppShell>
</template>
