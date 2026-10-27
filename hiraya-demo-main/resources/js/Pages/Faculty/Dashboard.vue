<script setup lang="ts">
import { ArrowRight, BookOpenCheck, CalendarDays, ClipboardList, Clock, MapPin, Megaphone, Users } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../Components/AppShell.vue';
import Badge from '../../Components/Badge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import { timeAgo } from '../../lib/format';

interface ClassSummary {
    id: number;
    subject: string;
    subject_name: string;
    section: string;
    program: string;
    year_level: string;
    schedule: string;
    room: string;
    students: number;
}

interface TodayClass {
    id: number;
    subject: string;
    section: string;
    time: string;
    room: string;
    students: number;
}

interface AnnouncementSummary {
    id: number;
    title: string;
    posted_at: string;
}

defineProps<{
    year: { id: number; name: string } | null;
    faculty: { employee_number: string; department: string | null };
    isPreview: boolean;
    stats: {
        classes: number;
        students: number;
        pending_grades: number;
        announcements: number;
    };
    todayClasses: TodayClass[];
    classes: ClassSummary[];
    announcements: AnnouncementSummary[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader
                title="Teaching overview"
            >
                <template #actions>
                    <Link href="/faculty/classes" class="btn btn-secondary">
                        <ClipboardList class="size-4" />
                        Class list
                    </Link>
                    <Link href="/faculty/grades" class="btn btn-gold">
                        <BookOpenCheck class="size-4" />
                        Open grade sheet
                    </Link>
                </template>
            </PageHeader>

            <div
                v-if="isPreview"
                class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-xs leading-6 text-gold-900"
            >
                <span class="font-bold">Administrator preview:</span>
                this account has no faculty profile, so no classes are assigned. Assign a faculty profile from the
                Accounts module in Checkpoint 1 to preview a real teaching dashboard.
            </div>

            <div class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex size-11 items-center justify-center rounded-md bg-navy-900 text-gold-300">
                        <Users class="size-5" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-navy-900">{{ faculty.employee_number }}</p>
                        <p class="text-xs text-navy-400">{{ faculty.department ?? 'Faculty' }} · {{ year?.name ?? 'No active year' }}</p>
                    </div>
                </div>
                <p class="text-xs text-navy-400">Classes shown below are assigned to your faculty profile.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Assigned classes" :value="stats.classes" hint="Class offerings for the active year" tone="navy">
                    <template #icon><CalendarDays class="size-5" /></template>
                </StatCard>
                <StatCard label="Students" :value="stats.students" hint="Students in your class rosters" tone="green">
                    <template #icon><Users class="size-5" /></template>
                </StatCard>
                <StatCard label="Pending grades" :value="stats.pending_grades" hint="Enrolled students without encoded grades" tone="gold">
                    <template #icon><BookOpenCheck class="size-5" /></template>
                </StatCard>
                <StatCard label="Class announcements" :value="stats.announcements" hint="Announcements you have posted" tone="navy">
                    <template #icon><Megaphone class="size-5" /></template>
                </StatCard>
            </div>

            <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Today’s classes</h2>
                            <p class="mt-0.5 text-xs text-navy-400">{{ new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' }) }}</p>
                        </div>
                        <Badge tone="gold">{{ todayClasses.length }} scheduled</Badge>
                    </div>

                    <div class="divide-y divide-navy-50 px-5">
                        <div v-for="item in todayClasses" :key="item.id" class="py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.section }} · {{ item.students }} students</p>
                                </div>
                                <Badge tone="navy">{{ item.time }}</Badge>
                            </div>
                            <div class="mt-2 flex items-center gap-2 text-xs text-navy-400">
                                <MapPin class="size-3.5" />
                                {{ item.room ?? 'Room not assigned' }}
                            </div>
                        </div>

                        <EmptyState
                            v-if="todayClasses.length === 0"
                            title="No classes today"
                            description="Your scheduled classes for today will appear here."
                        >
                            <template #icon><CalendarDays class="size-6" /></template>
                        </EmptyState>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">My classes</h2>
                            <p class="mt-0.5 text-xs text-navy-400">Class offerings assigned to you</p>
                        </div>
                        <Badge tone="navy">{{ classes.length }} classes</Badge>
                    </div>

                    <div v-if="classes.length" class="table-shell">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Section</th>
                                    <th>Schedule</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in classes" :key="item.id">
                                    <td>
                                        <p class="font-semibold text-navy-800">{{ item.subject }}</p>
                                        <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                    </td>
                                    <td>
                                        <p>{{ item.program }} {{ item.year_level }}</p>
                                        <p class="mt-0.5 text-xs text-navy-400">{{ item.section }}</p>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <Clock class="size-3.5 text-navy-300" />
                                            {{ item.schedule || 'Not scheduled' }}
                                        </div>
                                        <p class="mt-1 text-xs text-navy-400">{{ item.room }}</p>
                                    </td>
                                    <td>{{ item.students }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState
                        v-else
                        title="No classes assigned"
                        description="Ask the administrator to assign class offerings to your faculty profile."
                    >
                        <template #icon><BookOpenCheck class="size-6" /></template>
                    </EmptyState>

                    <div class="border-t border-navy-50 px-5 py-3">
                        <Link href="/faculty/classes" class="btn btn-ghost btn-sm w-full">
                            Open class workspace
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </div>
                </section>
            </div>

            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Your latest announcements</h2>
                        <p class="mt-0.5 text-xs text-navy-400">Posts visible to the sections you handle</p>
                    </div>
                </div>
                <div class="divide-y divide-navy-50 px-5">
                    <div v-for="announcement in announcements" :key="announcement.id" class="flex items-center gap-3 py-4">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-gold-100 text-gold-800">
                            <Megaphone class="size-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-navy-800">{{ announcement.title }}</p>
                            <p class="text-xs text-navy-400">{{ timeAgo(announcement.posted_at) }}</p>
                        </div>
                    </div>
                    <EmptyState
                        v-if="announcements.length === 0"
                        title="No announcements posted"
                        description="Class announcements you publish will appear here."
                    >
                        <template #icon><Megaphone class="size-6" /></template>
                    </EmptyState>
                </div>
            </section>
        </div>
    </AppShell>
</template>
