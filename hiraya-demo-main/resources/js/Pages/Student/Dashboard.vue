<script setup lang="ts">
import {
    ArrowRight,
    BookOpen,
    CalendarDays,
    CircleAlert,
    CircleCheck,
    ClipboardList,
    Clock,
    GraduationCap,
    MapPin,
    Megaphone,
    ReceiptText,
    Wallet,
} from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/AppShell.vue';
import Badge from '../../Components/Badge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import { formatCurrency, formatDate, timeAgo } from '../../lib/format';

interface StudentClass {
    id: number;
    subject: string;
    subject_name: string;
    schedule: string;
    room: string;
    grade: number | null;
}

interface EnrollmentInfo {
    id: number;
    status: string;
    section: string | null;
    units: number;
    assessed_amount: number;
    balance: number;
    classes: StudentClass[];
}

interface ScheduleItem {
    subject: string;
    day: string;
    time: string;
    room: string;
}

interface AnnouncementItem {
    id: number;
    title: string;
    content: string;
    author: string | null;
    audience: string;
    posted_at: string;
}

defineProps<{
    year: {
        id: number;
        name: string;
        enrollment_start: string;
        enrollment_end: string;
        status: string;
    } | null;
    enrollmentState: string | null;
    isPreview: boolean;
    student: {
        student_number: string;
        name: string;
        program: string | null;
        year_level: string | null;
    };
    enrollment: EnrollmentInfo | null;
    stats: {
        units: number;
        balance: number;
        graded_subjects: number;
        pending_payments: number;
    };
    announcements: AnnouncementItem[];
    schedule: ScheduleItem[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader
                :eyebrow="student.student_number"
                :title="`Hello, ${student.name.split(' ')[0]}`"
                :subtitle="`${student.program ?? 'Program not set'} · ${student.year_level ?? 'Year level not set'} · ${year?.name ?? 'No active academic year'}`"
            >
                <template #actions>
                    <Link href="/student/enrollment" class="btn btn-secondary">
                        <ClipboardList class="size-4" />
                        Enroll now
                    </Link>
                    <Link href="/student/payments" class="btn btn-gold">
                        <Wallet class="size-4" />
                        Submit payment
                    </Link>
                </template>
            </PageHeader>

            <div
                v-if="isPreview"
                class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-xs leading-6 text-gold-900"
            >
                <span class="font-bold">Administrator preview:</span>
                this account has no student profile, so the dashboard is showing the student view without enrollment
                data. Student records can be linked from the Registrar module in Checkpoint 1.
            </div>

            <div
                v-if="enrollment"
                class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-md bg-laurel-100 text-laurel-700">
                        <CircleCheck class="size-5" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-display text-lg font-bold text-navy-900">Enrolled for {{ year?.name }}</p>
                            <Badge tone="green">Enrolled</Badge>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-navy-500">
                            {{ enrollment.section ?? 'Section not assigned' }} · {{ enrollment.units }} units ·
                            Assessed {{ formatCurrency(enrollment.assessed_amount) }}
                        </p>
                    </div>
                </div>
                <div class="rounded-md bg-navy-50 px-4 py-3 text-right">
                    <p class="eyebrow">Remaining balance</p>
                    <p class="font-display mt-1 text-xl font-bold text-navy-900">{{ formatCurrency(enrollment.balance) }}</p>
                </div>
            </div>

            <div
                v-else
                class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-md bg-gold-100 text-gold-800">
                        <CircleAlert class="size-5" />
                    </div>
                    <div>
                        <p class="font-display text-lg font-bold text-navy-900">You are not enrolled yet</p>
                        <p class="mt-1 max-w-2xl text-xs leading-5 text-navy-500">
                            <template v-if="enrollmentState === 'open'">
                                Enrollment is open until {{ formatDate(year?.enrollment_end) }}. The online enrollment form
                                will be available in Checkpoint 2.
                            </template>
                            <template v-else-if="enrollmentState === 'upcoming'">
                                Enrollment opens on {{ formatDate(year?.enrollment_start) }}. Check back when the window opens.
                            </template>
                            <template v-else>
                                Enrollment is currently closed. Please contact the registrar for assistance.
                            </template>
                        </p>
                    </div>
                </div>
                <Badge :tone="enrollmentState === 'open' ? 'green' : enrollmentState === 'upcoming' ? 'gold' : 'red'">
                    {{ enrollmentState === 'open' ? 'Enrollment open' : enrollmentState === 'upcoming' ? 'Opening soon' : 'Enrollment closed' }}
                </Badge>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Enrolled units" :value="stats.units" hint="Total units for the active year" tone="navy">
                    <template #icon><BookOpen class="size-5" /></template>
                </StatCard>
                <StatCard label="Balance" :value="formatCurrency(stats.balance)" hint="Assessment minus verified payments" tone="gold">
                    <template #icon><Wallet class="size-5" /></template>
                </StatCard>
                <StatCard label="Graded subjects" :value="stats.graded_subjects" hint="Subjects with submitted grades" tone="green">
                    <template #icon><GraduationCap class="size-5" /></template>
                </StatCard>
                <StatCard label="Pending payments" :value="stats.pending_payments" hint="Submissions awaiting finance review" tone="red">
                    <template #icon><ReceiptText class="size-5" /></template>
                </StatCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">My classes</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Subjects in your current enrollment</p>
                        </div>
                        <Badge tone="navy">{{ enrollment?.classes.length ?? 0 }} classes</Badge>
                    </div>

                    <div v-if="enrollment?.classes.length" class="table-shell">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Schedule</th>
                                    <th>Room</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in enrollment.classes" :key="item.id">
                                    <td>
                                        <p class="font-semibold text-navy-800">{{ item.subject }}</p>
                                        <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                    </td>
                                    <td class="text-xs">{{ item.schedule || 'Not scheduled' }}</td>
                                    <td class="text-xs">{{ item.room ?? '—' }}</td>
                                    <td>
                                        <Badge v-if="item.grade !== null" :tone="Number(item.grade) >= 75 ? 'green' : 'red'">
                                            {{ Number(item.grade).toFixed(2) }}
                                        </Badge>
                                        <span v-else class="text-xs text-navy-300">Pending</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState
                        v-else
                        title="No classes to display"
                        description="Once you submit an enrollment, your subjects and schedules will appear here."
                    >
                        <template #icon><BookOpen class="size-6" /></template>
                    </EmptyState>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Weekly schedule</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Your classes arranged by day</p>
                        </div>
                        <Badge tone="gold">{{ schedule.length }} meetings</Badge>
                    </div>

                    <div class="divide-y divide-navy-50 px-5">
                        <div v-for="item in schedule" :key="`${item.subject}-${item.day}-${item.time}`" class="flex items-start gap-3 py-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-xs font-bold text-navy-700">
                                {{ item.day.slice(0, 3) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-navy-800">{{ item.subject }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-navy-400">
                                    <span class="inline-flex items-center gap-1"><Clock class="size-3.5" />{{ item.time }}</span>
                                    <span class="inline-flex items-center gap-1"><MapPin class="size-3.5" />{{ item.room ?? 'Room TBA' }}</span>
                                </div>
                            </div>
                        </div>

                        <EmptyState
                            v-if="schedule.length === 0"
                            title="No schedule yet"
                            description="Your weekly schedule will appear after enrollment."
                        >
                            <template #icon><CalendarDays class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Announcements</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Institution-wide and class updates</p>
                        </div>
                        <Badge tone="navy">{{ announcements.length }} recent</Badge>
                    </div>

                    <div class="divide-y divide-navy-50 px-5">
                        <article v-for="announcement in announcements" :key="announcement.id" class="py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-navy-800">{{ announcement.title }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">
                                        {{ announcement.author ?? 'System' }} · {{ timeAgo(announcement.posted_at) }}
                                    </p>
                                </div>
                                <Badge :tone="announcement.audience === 'global' ? 'navy' : 'gold'">
                                    {{ announcement.audience === 'global' ? 'Global' : 'Class' }}
                                </Badge>
                            </div>
                            <p class="mt-2 line-clamp-3 text-xs leading-6 text-navy-500">{{ announcement.content }}</p>
                        </article>

                        <EmptyState
                            v-if="announcements.length === 0"
                            title="No announcements yet"
                            description="Official school and class announcements will appear here."
                        >
                            <template #icon><Megaphone class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>

                <section class="card overflow-hidden">
                    <div class="bg-navy-950 p-5 text-white">
                        <p class="eyebrow text-gold-300">Student account</p>
                        <p class="font-display mt-2 text-2xl font-bold">{{ formatCurrency(stats.balance) }}</p>
                        <p class="mt-1 text-xs leading-5 text-navy-300">Outstanding balance for {{ year?.name }}</p>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gold-100 text-gold-800">
                                <ReceiptText class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-navy-800">Payment submissions</p>
                                <p class="mt-1 text-xs leading-5 text-navy-400">
                                    Submit a reference number and proof of payment for finance verification.
                                </p>
                            </div>
                        </div>
                        <Link href="/student/payments" class="btn btn-gold w-full">
                            Open payments
                            <ArrowRight class="size-4" />
                        </Link>
                        <p class="text-center text-[0.68rem] leading-5 text-navy-400">
                            Your balance only changes after finance verifies a payment.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </AppShell>
</template>
