<script setup lang="ts">
import { Download, FileText } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface Option {
    id: number;
    name: string;
    code?: string;
}

const props = defineProps<{
    type: string;
    filters: Record<string, string | undefined>;
    academicYears: Option[];
    classOfferings: { id: number; subject?: { code: string }; section?: { name: string } }[];
    students: { id: number; student_number: string; first_name: string; last_name: string }[];
    payPeriods: Option[];
    data: Record<string, unknown>[];
}>();

const type = ref(props.type);
const academicYearId = ref(props.filters.academic_year_id ?? '');
const classOfferingId = ref(props.filters.class_offering_id ?? '');
const studentId = ref(props.filters.student_id ?? '');
const payPeriodId = ref(props.filters.pay_period_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

const filters = computed(() => ({
    type: type.value,
    academic_year_id: academicYearId.value,
    class_offering_id: classOfferingId.value,
    student_id: studentId.value,
    pay_period_id: payPeriodId.value,
    from: from.value,
    to: to.value,
}));

const columns = computed(() => (props.data[0] ? Object.keys(props.data[0]) : []));

function apply(): void {
    router.get('/finance/reports', filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function exportReport(): void {
    window.location.href = `/finance/reports/export?${new URLSearchParams(filters.value as Record<string, string>).toString()}`;
}

function label(key: string): string {
    return key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Reports">
                <template #actions>
                    <button type="button" class="btn btn-secondary" :disabled="data.length === 0" @click="exportReport">
                        <Download class="size-4" /> Export CSV
                    </button>
                </template>
            </PageHeader>

            <section class="card p-4">
                <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="apply">
                    <div>
                        <label for="report-type" class="label">Report</label>
                        <select id="report-type" v-model="type" class="input" @change="apply">
                            <option value="enrollment">Enrollment summary</option>
                            <option value="collections">Collections</option>
                            <option value="class_list">Class list</option>
                            <option value="ledger">Student ledger</option>
                            <option value="payroll">Payroll register</option>
                        </select>
                    </div>
                    <div>
                        <label for="report-year" class="label">Academic year</label>
                        <select id="report-year" v-model="academicYearId" class="input">
                            <option value="">Active year</option>
                            <option v-for="year in props.academicYears" :key="year.id" :value="String(year.id)">{{ year.name }}</option>
                        </select>
                    </div>

                    <div v-if="type === 'collections'" class="grid grid-cols-2 gap-2 lg:col-span-2">
                        <div><label class="label" for="report-from">From</label><input id="report-from" v-model="from" type="date" class="input" /></div>
                        <div><label class="label" for="report-to">To</label><input id="report-to" v-model="to" type="date" class="input" /></div>
                    </div>
                    <div v-else-if="type === 'class_list'">
                        <label for="report-class" class="label">Class</label>
                        <select id="report-class" v-model="classOfferingId" class="input">
                            <option value="">Select class</option>
                            <option v-for="offering in props.classOfferings" :key="offering.id" :value="String(offering.id)">
                                {{ offering.subject?.code }} · {{ offering.section?.name }}
                            </option>
                        </select>
                    </div>
                    <div v-else-if="type === 'ledger'">
                        <label for="report-student" class="label">Student</label>
                        <select id="report-student" v-model="studentId" class="input">
                            <option value="">Select student</option>
                            <option v-for="student in props.students" :key="student.id" :value="String(student.id)">
                                {{ student.first_name }} {{ student.last_name }} ({{ student.student_number }})
                            </option>
                        </select>
                    </div>
                    <div v-else-if="type === 'payroll'">
                        <label for="report-period" class="label">Pay period</label>
                        <select id="report-period" v-model="payPeriodId" class="input">
                            <option value="">Select period</option>
                            <option v-for="period in props.payPeriods" :key="period.id" :value="String(period.id)">{{ period.name }}</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary w-full">Generate</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <FileText class="size-4 text-navy-500" />
                        <h2 class="card-title">Results</h2>
                    </div>
                    <span class="text-xs text-navy-400">{{ data.length }} rows</span>
                </div>

                <div v-if="data.length" class="table-shell">
                    <table class="data-table min-w-[56rem]">
                        <thead><tr><th v-for="column in columns" :key="column">{{ label(column) }}</th></tr></thead>
                        <tbody>
                            <tr v-for="(row, index) in data" :key="index">
                                <td v-for="column in columns" :key="column" class="text-xs">{{ row[column] ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="empty-state">
                    <FileText class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No report data</p>
                    <p class="text-xs text-navy-400">Choose a report and apply the filters.</p>
                </div>
            </section>
        </div>
    </AppShell>
</template>
