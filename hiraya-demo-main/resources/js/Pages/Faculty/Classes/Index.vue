<script setup lang="ts">
import { ArrowRight, BookOpenCheck, ClipboardList, Users } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface ClassRow {
    id: number;
    subject: string;
    subject_name: string;
    section: string;
    program: string;
    year_level: string;
    schedule: string;
    room: string | null;
    students: number;
    status: string;
}

defineProps<{
    year: { id: number; name: string } | null;
    classes: ClassRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Class list" />

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[56rem]">
                        <thead>
                            <tr><th>Subject</th><th>Section</th><th>Schedule</th><th>Room</th><th>Students</th><th class="text-right">Actions</th></tr>
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
                                <td class="text-xs">{{ item.schedule || 'Not scheduled' }}</td>
                                <td class="text-xs">{{ item.room ?? '—' }}</td>
                                <td>
                                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold">
                                        <Users class="size-4 text-navy-300" />
                                        {{ item.students }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <Link :href="`/faculty/classes/${item.id}`" class="btn btn-secondary btn-sm">
                                            <ClipboardList class="size-3.5" /> Roster
                                        </Link>
                                        <Link :href="`/faculty/grades/${item.id}`" class="btn btn-ghost btn-sm">
                                            <BookOpenCheck class="size-3.5" /> Grades
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="classes.length === 0" class="empty-state">
                    <ClipboardList class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No classes assigned</p>
                    <p class="text-xs text-navy-400">Class offerings assigned to you will appear here.</p>
                </div>
            </section>

            <Link href="/faculty/schedule" class="inline-flex items-center gap-1 text-xs font-semibold text-navy-500 hover:text-navy-800">
                View weekly schedule <ArrowRight class="size-3.5" />
            </Link>
        </div>
    </AppShell>
</template>
