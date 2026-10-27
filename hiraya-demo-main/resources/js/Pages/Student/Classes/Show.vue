<script setup lang="ts">
import { ArrowLeft, Clock, MapPin, Users } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface RosterRow {
    id: number;
    student_number: string;
    name: string;
}

defineProps<{
    classOffering: {
        id: number;
        subject: string;
        subject_name: string;
        units: number;
        section: string;
        program: string;
        year_level: string;
        faculty: string | null;
        schedule: string;
        room: string | null;
    };
    roster: RosterRow[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <Link href="/student/classes" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to classes
            </Link>

            <PageHeader
                :title="`${classOffering.subject} · ${classOffering.section}`"
                :subtitle="`${classOffering.subject_name} · ${classOffering.program} ${classOffering.year_level}`"
            />

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="card p-4">
                    <p class="eyebrow">Faculty</p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold"><Users class="size-4 text-navy-300" />{{ classOffering.faculty ?? '—' }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Schedule</p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold"><Clock class="size-4 text-navy-300" />{{ classOffering.schedule || 'Not scheduled' }}</p>
                </div>
                <div class="card p-4">
                    <p class="eyebrow">Room</p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold"><MapPin class="size-4 text-navy-300" />{{ classOffering.room ?? 'TBA' }}</p>
                </div>
            </div>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Class roster</h2>
                    <Badge tone="navy">{{ roster.length }} students</Badge>
                </div>

                <div v-if="roster.length" class="table-shell">
                    <table class="data-table min-w-[32rem]">
                        <thead><tr><th>Student number</th><th>Name</th></tr></thead>
                        <tbody>
                            <tr v-for="student in roster" :key="student.id">
                                <td class="font-mono text-xs">{{ student.student_number }}</td>
                                <td class="font-semibold text-navy-800">{{ student.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-else title="No classmates yet" description="The roster will appear after students enroll.">
                    <template #icon><Users class="size-6" /></template>
                </EmptyState>
            </section>
        </div>
    </AppShell>
</template>
