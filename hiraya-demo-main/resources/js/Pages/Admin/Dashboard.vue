<script setup lang="ts">
import {
    ArrowRight,
    CalendarDays,
    CalendarRange,
    Megaphone,
    ReceiptText,
    Users,
} from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/AppShell.vue';
import Badge from '../../Components/Badge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import { formatCurrency, formatDate, timeAgo, titleCase } from '../../lib/format';

interface PendingPayment {
    id: number;
    student: string;
    student_number: string;
    amount: number;
    method: string;
    created_at: string;
}

interface AnnouncementSummary {
    id: number;
    title: string;
    author: string | null;
    audience: string;
    posted_at: string;
}

defineProps<{
    year: {
        id: number;
        name: string;
        status: string;
        enrollment_start: string;
        enrollment_end: string;
        is_active: boolean;
    } | null;
    enrollmentState: string | null;
    stats: {
        students: number;
        faculty: number;
        sections: number;
        enrolled: number;
        pending_payments: number;
        announcements: number;
    };
    announcements: AnnouncementSummary[];
    pendingPayments: PendingPayment[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader
                title="Institutional overview"
            >
                <template #actions>
                    <Link href="/admin/academic-years" class="btn btn-secondary">
                        <CalendarDays class="size-4" />
                        New academic year
                    </Link>
                    <Link href="/admin/accounts" class="btn btn-gold">
                        <Users class="size-4" />
                        Invite account
                    </Link>
                </template>
            </PageHeader>

            <div
                v-if="!year"
                class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900"
            >
                No academic year is active. Create and activate an academic year before opening classes or enrollment.
            </div>

            <div v-else class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-md bg-gold-100 text-gold-800">
                        <CalendarRange class="size-5" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-display text-lg font-bold text-navy-900">{{ year.name }}</p>
                            <Badge :tone="enrollmentState === 'open' ? 'green' : enrollmentState === 'upcoming' ? 'gold' : 'red'">
                                {{ titleCase(enrollmentState) }}
                            </Badge>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-navy-500">
                            Enrollment window: {{ formatDate(year.enrollment_start) }} – {{ formatDate(year.enrollment_end) }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-6 sm:text-right">
                    <div>
                        <p class="eyebrow">Enrolled</p>
                        <p class="font-display text-xl font-bold text-navy-900">{{ stats.enrolled }}</p>
                    </div>
                    <div>
                        <p class="eyebrow">Sections</p>
                        <p class="font-display text-xl font-bold text-navy-900">{{ stats.sections }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Active students" :value="stats.students" hint="Student records in the institution" tone="navy">
                    <template #icon><Users class="size-5" /></template>
                </StatCard>
                <StatCard label="Faculty" :value="stats.faculty" hint="Faculty profiles with teaching assignments" tone="green">
                    <template #icon><Users class="size-5" /></template>
                </StatCard>
                <StatCard label="Class sections" :value="stats.sections" hint="Sections for the active academic year" tone="gold">
                    <template #icon><CalendarRange class="size-5" /></template>
                </StatCard>
                <StatCard label="Pending payments" :value="stats.pending_payments" hint="Student submissions awaiting finance review" tone="red">
                    <template #icon><ReceiptText class="size-5" /></template>
                </StatCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Recent announcements</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Published institution-wide and class updates</p>
                        </div>
                        <Badge tone="navy">{{ stats.announcements }} total</Badge>
                    </div>
                    <div class="divide-y divide-navy-50 px-5">
                        <div
                            v-for="announcement in announcements"
                            :key="announcement.id"
                            class="flex items-start gap-3 py-4"
                        >
                            <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                                <Megaphone class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-navy-800">{{ announcement.title }}</p>
                                <p class="mt-0.5 text-xs text-navy-400">
                                    {{ announcement.author ?? 'System' }} · {{ timeAgo(announcement.posted_at) }}
                                </p>
                            </div>
                            <Badge :tone="announcement.audience === 'global' ? 'navy' : 'gold'">
                                {{ titleCase(announcement.audience) }}
                            </Badge>
                        </div>
                        <EmptyState
                            v-if="announcements.length === 0"
                            title="No announcements yet"
                            description="Global and class announcements will appear here once published."
                        >
                            <template #icon><Megaphone class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Finance queue</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Latest student payment submissions</p>
                        </div>
                        <Badge tone="gold">{{ stats.pending_payments }} pending</Badge>
                    </div>
                    <div class="divide-y divide-navy-50 px-5">
                        <div v-for="payment in pendingPayments" :key="payment.id" class="flex items-center gap-3 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-navy-800">{{ payment.student }}</p>
                                <p class="mt-0.5 text-xs text-navy-400">
                                    {{ payment.student_number }} · {{ payment.method }} · {{ timeAgo(payment.created_at) }}
                                </p>
                            </div>
                            <p class="font-display text-sm font-bold text-navy-900">{{ formatCurrency(payment.amount) }}</p>
                        </div>
                        <EmptyState
                            v-if="pendingPayments.length === 0"
                            title="No pending payments"
                            description="Student payment submissions will appear here for finance verification."
                        >
                            <template #icon><ReceiptText class="size-6" /></template>
                        </EmptyState>
                    </div>
                    <div class="border-t border-navy-50 px-5 py-3">
                        <button type="button" class="btn btn-ghost btn-sm w-full" disabled>
                            Open finance queue
                            <span class="badge badge-gray ml-1">CP2</span>
                            <ArrowRight class="size-3.5" />
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AppShell>
</template>
