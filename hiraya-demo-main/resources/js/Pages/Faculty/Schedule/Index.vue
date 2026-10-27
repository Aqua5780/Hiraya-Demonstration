<script setup lang="ts">
import { CalendarDays, Clock, MapPin } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface ScheduleClass {
    id: number;
    offering_id: number;
    subject: string;
    subject_name: string;
    section: string;
    program: string;
    year_level: string;
    time: string;
    room: string | null;
}

interface ScheduleDay {
    day: string;
    classes: ScheduleClass[];
}

defineProps<{
    year: { id: number; name: string } | null;
    faculty: { employee_number: string; department: string | null };
    schedule: ScheduleDay[];
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Class schedule" />

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <section v-for="day in schedule" :key="day.day" class="card">
                    <div class="card-header">
                        <h2 class="card-title">{{ day.day }}</h2>
                        <span class="text-xs text-navy-400">{{ day.classes.length }} class{{ day.classes.length === 1 ? '' : 'es' }}</span>
                    </div>

                    <div v-if="day.classes.length" class="divide-y divide-navy-50">
                        <Link
                            v-for="item in day.classes"
                            :key="item.id"
                            :href="`/faculty/classes/${item.offering_id}`"
                            class="block px-5 py-4 transition hover:bg-cream-50"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-navy-800">{{ item.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ item.subject_name }}</p>
                                </div>
                                <span class="text-xs font-semibold text-navy-500">{{ item.time }}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-navy-400">
                                <span>{{ item.program }} {{ item.year_level }} {{ item.section }}</span>
                                <span class="inline-flex items-center gap-1"><MapPin class="size-3.5" />{{ item.room ?? 'Room TBA' }}</span>
                            </div>
                        </Link>
                    </div>

                    <div v-else class="px-5 py-8 text-center">
                        <CalendarDays class="mx-auto size-5 text-navy-200" />
                        <p class="mt-2 text-xs text-navy-400">No classes</p>
                    </div>
                </section>
            </div>

            <p v-if="schedule.every((day) => day.classes.length === 0)" class="text-xs text-navy-400">
                No class meetings are assigned for {{ year?.name ?? 'the active year' }}.
            </p>
        </div>
    </AppShell>
</template>
