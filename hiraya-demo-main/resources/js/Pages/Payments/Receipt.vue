<script setup lang="ts">
import { ArrowLeft, Check, Printer } from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency, formatDateTime } from '../../lib/format';
import type { SharedPageProps } from '../../types';

interface ReceiptPayment {
    id: number;
    receipt_number: string | null;
    student_number: string | null;
    student_name: string | null;
    amount: number;
    method: string;
    reference_number: string;
    verified_by: string | null;
    verified_at: string | null;
    balance_after: number | null;
    academic_year: string | null;
    remarks: string | null;
}

const props = defineProps<{
    payment: ReceiptPayment;
}>();

const page = usePage<SharedPageProps>();
const role = computed(() => page.props.auth.user?.role ?? 'student');

const backHref = computed(() => {
    if (role.value === 'admin' || role.value === 'finance') {
        return '/finance/payments';
    }

    if (role.value === 'student') {
        return '/student/payments';
    }

    return '/';
});

function printReceipt(): void {
    window.print();
}
</script>

<template>
    <div class="min-h-screen bg-white px-5 py-8 sm:px-8">
        <div class="mx-auto max-w-2xl">
            <div class="no-print mb-6 flex items-center justify-between gap-3">
                <Link :href="backHref" class="btn btn-ghost btn-sm">
                    <ArrowLeft class="size-4" /> Back
                </Link>
                <button type="button" class="btn btn-primary btn-sm" @click="printReceipt">
                    <Printer class="size-4" /> Print receipt
                </button>
            </div>

            <div class="border border-navy-200 p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-navy-100 pb-5">
                    <div class="flex items-center gap-3">
                        <img src="/images/gstc-seal.jpg" alt="GSTC seal" class="size-14 rounded-full object-cover" />
                        <div>
                            <p class="font-display text-lg font-bold text-navy-900">Good Shepherd of Taguig Colleges, Inc.</p>
                            <p class="text-[0.68rem] text-navy-400">Central Bicutan, Taguig City</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[0.68rem] font-bold uppercase tracking-wider text-navy-400">Official receipt</p>
                        <p class="mt-1 font-mono text-sm font-bold text-navy-900">{{ payment.receipt_number }}</p>
                    </div>
                </div>

                <div class="grid gap-4 py-5 sm:grid-cols-2">
                    <div>
                        <p class="label">Student</p>
                        <p class="text-sm font-semibold">{{ payment.student_name }}</p>
                        <p class="text-xs text-navy-400">{{ payment.student_number }}</p>
                    </div>
                    <div>
                        <p class="label">Academic year</p>
                        <p class="text-sm font-semibold">{{ payment.academic_year ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="label">Payment date</p>
                        <p class="text-sm">{{ payment.verified_at ? formatDateTime(payment.verified_at) : '—' }}</p>
                    </div>
                    <div>
                        <p class="label">Received by</p>
                        <p class="text-sm">{{ payment.verified_by ?? 'Finance Office' }}</p>
                    </div>
                </div>

                <div class="my-4 flex items-center justify-between border-y border-navy-100 py-4">
                    <div>
                        <p class="label">Amount paid</p>
                        <p class="font-display text-3xl font-bold text-navy-900">{{ formatCurrency(payment.amount) }}</p>
                    </div>
                    <div class="flex items-center gap-2 rounded-md border border-laurel-200 bg-laurel-50 px-3 py-2 text-xs font-semibold text-laurel-700">
                        <Check class="size-4" /> Verified
                    </div>
                </div>

                <div class="grid gap-3 text-xs sm:grid-cols-2">
                    <p><span class="font-semibold text-navy-600">Method:</span> {{ payment.method }}</p>
                    <p><span class="font-semibold text-navy-600">Reference:</span> {{ payment.reference_number }}</p>
                    <p><span class="font-semibold text-navy-600">Remaining balance:</span> {{ payment.balance_after !== null ? formatCurrency(payment.balance_after) : '—' }}</p>
                    <p v-if="payment.remarks"><span class="font-semibold text-navy-600">Remarks:</span> {{ payment.remarks }}</p>
                </div>

                <p class="mt-6 border-t border-navy-100 pt-4 text-center text-[0.68rem] text-navy-400">
                    This is a system-generated receipt from Hiraya. Keep this for your records.
                </p>
            </div>
        </div>
    </div>
</template>
