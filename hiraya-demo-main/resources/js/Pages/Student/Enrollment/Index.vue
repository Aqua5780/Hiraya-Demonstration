<script setup lang="ts">
import { AlertTriangle, BookOpen, Check, ClipboardList, Clock, MapPin, Users } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { formatCurrency, formatDate, titleCase } from '../../../lib/format';

interface Schedule {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    room: string | null;
    label: string;
}

interface Offering {
    id: number;
    subject: string;
    subject_name: string;
    units: number;
    section_id: number;
    section: string;
    program: string;
    year_level: string;
    faculty: string | null;
    room: string | null;
    capacity: number;
    enrolled: number;
    available: number;
    schedules: Schedule[];
}

interface EnrollmentInfo {
    id: number;
    status: string;
    section: string | null;
    units: number;
    assessed_amount: number;
    balance: number;
    classes: {
        id: number;
        subject: string;
        subject_name: string;
        units: number;
        schedule: string;
        room: string | null;
        grade: number | null;
    }[];
}

const props = defineProps<{
    year: {
        id: number;
        name: string;
        enrollment_start: string;
        enrollment_end: string;
        status: string;
    } | null;
    enrollmentState: string | null;
    canEnroll: boolean;
    assessment: number;
    balance: number;
    offerings: Offering[];
    enrollment: EnrollmentInfo | null;
}>();

const form = useForm({
    classes: [] as number[],
});

const selectedOfferings = computed(() => props.offerings.filter((offering) => form.classes.includes(offering.id)));
const selectedUnits = computed(() => selectedOfferings.value.reduce((total, offering) => total + offering.units, 0));

const conflicts = computed(() => {
    const messages: string[] = [];

    for (let i = 0; i < selectedOfferings.value.length; i++) {
        for (let j = i + 1; j < selectedOfferings.value.length; j++) {
            const first = selectedOfferings.value[i];
            const second = selectedOfferings.value[j];

            for (const a of first.schedules) {
                for (const b of second.schedules) {
                    if (a.day_of_week === b.day_of_week && a.start_time < b.end_time && a.end_time > b.start_time) {
                        messages.push(`${first.subject} conflicts with ${second.subject} on ${a.day_of_week}.`);
                    }
                }
            }
        }
    }

    return messages;
});

function toggle(id: number): void {
    if (form.classes.includes(id)) {
        form.classes = form.classes.filter((classId) => classId !== id);
    } else {
        form.classes.push(id);
    }
}

function submit(): void {
    form.post('/student/enrollment', {
        preserveScroll: true,
    });
}

function enrollmentMessage(): string {
    if (!props.year) {
        return 'No academic year is active.';
    }

    if (props.enrollmentState === 'open') {
        return `Enrollment is open until ${formatDate(props.year.enrollment_end)}.`;
    }

    if (props.enrollmentState === 'upcoming') {
        return `Enrollment opens on ${formatDate(props.year.enrollment_start)}.`;
    }

    return 'Enrollment is currently closed.';
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Enrollment" />

            <div v-if="enrollment" class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-md bg-laurel-100 text-laurel-700">
                        <Check class="size-5" />
                    </div>
                    <div>
                        <p class="font-display text-lg font-bold text-navy-900">{{ enrollment.section ?? 'Enrolled' }}</p>
                        <p class="mt-1 text-xs text-navy-500">
                            {{ enrollment.units }} units · Assessed {{ formatCurrency(enrollment.assessed_amount) }} ·
                            Balance {{ formatCurrency(enrollment.balance) }}
                        </p>
                    </div>
                </div>
                <Badge tone="green">{{ titleCase(enrollment.status) }}</Badge>
            </div>

            <section v-if="enrollment" class="card">
                <div class="card-header">
                    <h2 class="card-title">Enrolled classes</h2>
                    <Badge tone="navy">{{ enrollment.classes.length }} classes</Badge>
                </div>
                <div class="table-shell">
                    <table class="data-table min-w-[48rem]">
                        <thead><tr><th>Subject</th><th>Schedule</th><th>Room</th><th>Units</th><th>Grade</th></tr></thead>
                        <tbody>
                            <tr v-for="item in enrollment.classes" :key="item.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                </td>
                                <td class="text-xs">{{ item.schedule }}</td>
                                <td class="text-xs">{{ item.room ?? '—' }}</td>
                                <td>{{ item.units }}</td>
                                <td>
                                    <Badge v-if="item.grade !== null" :tone="item.grade >= 75 ? 'green' : 'red'">{{ item.grade.toFixed(2) }}</Badge>
                                    <span v-else class="text-xs text-navy-300">Pending</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap gap-2 border-t border-navy-100 px-5 py-3">
                    <Link href="/student/schedule" class="btn btn-secondary btn-sm">View schedule</Link>
                    <Link href="/student/grades" class="btn btn-secondary btn-sm">View grades</Link>
                    <Link href="/student/payments" class="btn btn-ghost btn-sm">View payments</Link>
                </div>
            </section>

            <template v-else>
                <div class="card p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-navy-500">
                                {{ year?.name ?? 'No active academic year' }}
                            </p>
                            <p class="mt-1 text-sm text-navy-800">{{ enrollmentMessage() }}</p>
                        </div>
                        <div class="flex gap-6 text-sm">
                            <div>
                                <p class="eyebrow">Assessment</p>
                                <p class="font-display mt-1 text-lg font-bold">{{ formatCurrency(assessment) }}</p>
                            </div>
                            <div>
                                <p class="eyebrow">Balance</p>
                                <p class="font-display mt-1 text-lg font-bold">{{ formatCurrency(balance) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form v-if="canEnroll" class="space-y-4" @submit.prevent="submit">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-display text-xl font-bold text-navy-900">Available classes</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Select the classes for {{ year?.name }}.</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-navy-400">Selected units</p>
                            <p class="font-display text-lg font-bold">{{ selectedUnits }}</p>
                        </div>
                    </div>

                    <p v-if="form.errors.classes" class="error-text">{{ form.errors.classes }}</p>

                    <div v-if="conflicts.length" class="rounded-md border border-red-200 bg-red-50 p-4">
                        <div class="flex gap-2">
                            <AlertTriangle class="mt-0.5 size-4 shrink-0 text-red-600" />
                            <div>
                                <p class="text-xs font-bold text-red-800">Schedule conflict</p>
                                <p v-for="message in conflicts" :key="message" class="mt-1 text-xs text-red-700">{{ message }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <label
                            v-for="offering in offerings"
                            :key="offering.id"
                            class="card cursor-pointer p-4 transition"
                            :class="form.classes.includes(offering.id) ? 'border-gold-400 ring-1 ring-gold-300' : 'hover:border-navy-200'"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="mt-1 size-4 rounded border-navy-300 text-gold-500 focus:ring-gold-400"
                                    :checked="form.classes.includes(offering.id)"
                                    @change="toggle(offering.id)"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-bold text-navy-800">{{ offering.subject }}</p>
                                            <p class="mt-0.5 text-xs text-navy-400">{{ offering.subject_name }}</p>
                                        </div>
                                        <Badge tone="navy">{{ offering.units }} units</Badge>
                                    </div>

                                    <div class="mt-3 grid gap-1.5 text-xs text-navy-500">
                                        <p class="inline-flex items-center gap-1.5">
                                            <Users class="size-3.5 text-navy-300" />
                                            {{ offering.program }} {{ offering.year_level }} — {{ offering.section }}
                                        </p>
                                        <p class="inline-flex items-center gap-1.5">
                                            <BookOpen class="size-3.5 text-navy-300" />
                                            {{ offering.faculty ?? 'Faculty not assigned' }}
                                        </p>
                                        <p v-for="schedule in offering.schedules" :key="schedule.id" class="inline-flex items-center gap-1.5">
                                            <Clock class="size-3.5 text-navy-300" />
                                            {{ schedule.label }}
                                            <span v-if="schedule.room || offering.room" class="inline-flex items-center gap-1">
                                                <MapPin class="size-3.5 text-navy-300" />
                                                {{ schedule.room ?? offering.room }}
                                            </span>
                                        </p>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between text-[0.68rem]">
                                        <span class="text-navy-400">{{ offering.available }} slot{{ offering.available === 1 ? '' : 's' }} available</span>
                                        <span v-if="form.classes.includes(offering.id)" class="inline-flex items-center gap-1 font-semibold text-laurel-700">
                                            <Check class="size-3.5" /> Selected
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <EmptyState v-if="offerings.length === 0" title="No available classes" description="There are no open classes for your program and year level yet.">
                        <template #icon><ClipboardList class="size-6" /></template>
                    </EmptyState>

                    <div class="flex flex-col gap-3 rounded-lg border border-navy-100 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-navy-500">
                            {{ form.classes.length }} class{{ form.classes.length === 1 ? '' : 'es' }} selected ·
                            Assessment {{ formatCurrency(assessment) }}
                        </p>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing || form.classes.length === 0 || conflicts.length > 0">
                            {{ form.processing ? 'Submitting…' : 'Submit enrollment' }}
                        </button>
                    </div>
                </form>

                <div v-else-if="offerings.length > 0" class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900">
                    Enrollment is not open for this academic year.
                </div>
            </template>
        </div>
    </AppShell>
</template>
