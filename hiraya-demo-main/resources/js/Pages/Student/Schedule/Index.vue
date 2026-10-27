<script setup lang="ts">
import { CalendarDays, MapPin } from '@lucide/vue';
import AppShell from '../../../Components/AppShell.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface ScheduleClass {
    subject: string;
    subject_name: string;
    section: string;
    time: string;
    room: string | null;
}

interface ScheduleDay {
    day: string;
    classes: ScheduleClass[];
}

defineProps<{
    year: { id: number; name: string } | null;
    enrollment: { section: string | null; units: number } | null;
    schedule: ScheduleDay[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="My schedule" />

            <div v-if="enrollment" class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-navy-500">{{ year?.name }}</p>
                    <p class="font-display mt-1 text-lg font-bold text-navy-900">{{ enrollment.section ?? 'Enrolled' }}</p>
                </div>
                <p class="text-sm text-navy-500">{{ enrollment.units }} units</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <section v-for="day in schedule" :key="day.day" class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ day.day }}</h2>
                        <span class="text-xs text-navy-400">{{ day.classes.length }} class{{ day.classes.length === 1 ? '' : 'es' }}</span>
                    </div>

                    <div v-if="day.classes.length" class="divide-y divide-navy-50">
                        <div v-for="(item, index) in day.classes" :key="`${item.subject}-${index}`" class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                </div>
                                <span class="text-xs font-semibold text-navy-500">{{ item.time }}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-navy-400">
                                <span>{{ item.section }}</span>
                                <span class="inline-flex items-center gap-1"><MapPin class="size-3.5" />{{ item.room ?? 'Room TBA' }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="px-5 py-8 text-center">
                        <CalendarDays class="mx-auto size-5 text-navy-200" />
                        <p class="mt-2 text-xs text-navy-400">No classes</p>
                    </div>
                </section>
            </div>

            <div v-if="!enrollment" class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900">
                No enrollment record for the active academic year.
            </div>
        </div>
    </AppShell>
</template>
