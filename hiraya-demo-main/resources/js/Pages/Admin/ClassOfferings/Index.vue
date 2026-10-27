<script setup lang="ts">
import { CalendarRange, Pencil, Plus, Trash2 } from '@lucide/vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { titleCase } from '../../../lib/format';

interface Schedule {
    id?: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    room: string | null;
    label?: string;
}

interface Offering {
    id: number;
    academic_year_id: number;
    academic_year: string;
    section_id: number;
    section: string;
    program: string;
    year_level: string;
    subject_id: number;
    subject: string;
    subject_name: string;
    units: number;
    faculty_id: number | null;
    faculty: string | null;
    room: string | null;
    capacity: number;
    enrolled_count: number;
    status: string;
    schedules: Schedule[];
    schedule_label: string;
}

interface Option {
    id: number;
    name: string;
}

interface SectionOption {
    id: number;
    academic_year_id: number;
    program: string;
    year_level: string;
    name: string;
}

interface SubjectOption {
    id: number;
    code: string;
    name: string;
    units: string | number;
}

interface FacultyOption {
    id: number;
    employee_number: string;
    name: string | null;
}

const props = defineProps<{
    offerings: {
        data: Offering[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | undefined>;
    academicYears: (Option & { is_active: boolean })[];
    sections: SectionOption[];
    subjects: SubjectOption[];
    faculty: FacultyOption[];
}>();

const filter = ref({
    q: props.filters.q ?? '',
    academic_year_id: props.filters.academic_year_id ?? String(props.academicYears.find((year) => year.is_active)?.id ?? ''),
    section_id: props.filters.section_id ?? '',
    faculty_id: props.filters.faculty_id ?? '',
    status: props.filters.status ?? '',
});

const showModal = ref(false);
const editing = ref<Offering | null>(null);

const form = useForm({
    academic_year_id: props.academicYears.find((year) => year.is_active)?.id ?? 0,
    section_id: 0,
    subject_id: 0,
    faculty_id: '' as number | string,
    room: '',
    capacity: 40,
    status: 'open',
    schedules: [
        { day_of_week: 'Monday', start_time: '08:00', end_time: '09:30', room: '' },
    ],
});

const availableSections = computed(() =>
    props.sections.filter((section) => section.academic_year_id === Number(form.academic_year_id)),
);

const selectedSubject = computed(() => props.subjects.find((subject) => subject.id === Number(form.subject_id)));

function applyFilters(): void {
    router.get('/admin/class-offerings', filter.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.academic_year_id = props.academicYears.find((year) => year.is_active)?.id ?? props.academicYears[0]?.id ?? 0;
    form.section_id = 0;
    form.subject_id = 0;
    form.faculty_id = '';
    form.status = 'open';
    form.schedules = [{ day_of_week: 'Monday', start_time: '08:00', end_time: '09:30', room: '' }];
    showModal.value = true;
}

function openEdit(offering: Offering): void {
    editing.value = offering;
    form.clearErrors();
    form.academic_year_id = offering.academic_year_id;
    form.section_id = offering.section_id;
    form.subject_id = offering.subject_id;
    form.faculty_id = offering.faculty_id ?? '';
    form.room = offering.room ?? '';
    form.capacity = offering.capacity;
    form.status = offering.status;
    form.schedules = offering.schedules.map((schedule) => ({
        day_of_week: schedule.day_of_week,
        start_time: schedule.start_time.slice(0, 5),
        end_time: schedule.end_time.slice(0, 5),
        room: schedule.room ?? '',
    }));
    showModal.value = true;
}

function addSchedule(): void {
    form.schedules.push({ day_of_week: 'Monday', start_time: '08:00', end_time: '09:30', room: '' });
}

function removeSchedule(index: number): void {
    if (form.schedules.length > 1) {
        form.schedules.splice(index, 1);
    }
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    };

    if (editing.value) {
        form.patch(`/admin/class-offerings/${editing.value.id}`, options);
    } else {
        form.post('/admin/class-offerings', options);
    }
}

function statusTone(status: string): 'green' | 'gray' {
    return status === 'open' ? 'green' : 'gray';
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Class offerings">
                <template #actions>
                    <button type="button" class="btn btn-primary" @click="openCreate">
                        <Plus class="size-4" />
                        New class offering
                    </button>
                </template>
            </PageHeader>

            <section class="card p-4">
                <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
                    <div class="lg:col-span-2">
                        <label for="offering-search" class="label">Search</label>
                        <input id="offering-search" v-model="filter.q" type="search" class="input" placeholder="Subject or section" />
                    </div>
                    <div>
                        <label for="offering-year" class="label">School year</label>
                        <select id="offering-year" v-model="filter.academic_year_id" class="input" @change="applyFilters">
                            <option value="">All years</option>
                            <option v-for="year in props.academicYears" :key="year.id" :value="String(year.id)">{{ year.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="offering-section" class="label">Section</label>
                        <select id="offering-section" v-model="filter.section_id" class="input">
                            <option value="">All sections</option>
                            <option v-for="section in props.sections" :key="section.id" :value="String(section.id)">
                                {{ section.program }} {{ section.year_level }} {{ section.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="offering-faculty" class="label">Faculty</label>
                        <select id="offering-faculty" v-model="filter.faculty_id" class="input">
                            <option value="">All faculty</option>
                            <option v-for="member in props.faculty" :key="member.id" :value="String(member.id)">{{ member.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label for="offering-status" class="label">Status</label>
                            <select id="offering-status" v-model="filter.status" class="input">
                                <option value="">All</option>
                                <option value="open">Open</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-secondary">Filter</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[64rem]">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Section</th>
                                <th>Faculty</th>
                                <th>Schedule</th>
                                <th>Room</th>
                                <th>Enrolled</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="offering in props.offerings.data" :key="offering.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ offering.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ offering.subject_name }}</p>
                                </td>
                                <td>
                                    <p>{{ offering.program }} {{ offering.year_level }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ offering.section }}</p>
                                </td>
                                <td class="text-xs">{{ offering.faculty ?? 'Unassigned' }}</td>
                                <td class="text-xs">
                                    <p v-for="schedule in offering.schedules" :key="schedule.id" class="whitespace-nowrap">
                                        {{ schedule.day_of_week.slice(0, 3) }} {{ schedule.start_time.slice(0, 5) }}–{{ schedule.end_time.slice(0, 5) }}
                                    </p>
                                </td>
                                <td class="text-xs">{{ offering.room ?? '—' }}</td>
                                <td>
                                    <span class="font-semibold">{{ offering.enrolled_count }}</span>
                                    <span class="text-xs text-navy-400"> / {{ offering.capacity }}</span>
                                </td>
                                <td><Badge :tone="statusTone(offering.status)">{{ titleCase(offering.status) }}</Badge></td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openEdit(offering)">
                                            <Pencil class="size-3.5" /> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.offerings.data.length === 0" class="empty-state">
                    <CalendarRange class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No class offerings found</p>
                    <p class="text-xs text-navy-400">Adjust the filters or create a new offering.</p>
                </div>

                <Pagination
                    :links="props.offerings.links"
                    :from="props.offerings.from"
                    :to="props.offerings.to"
                    :total="props.offerings.total"
                />
            </section>
        </div>

        <Modal :show="showModal" :title="editing ? 'Edit class offering' : 'New class offering'" max-width="xl" @close="showModal = false">
            <form id="offering-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="offering-form-year" class="label">School year</label>
                        <select id="offering-form-year" v-model="form.academic_year_id" class="input">
                            <option v-for="year in props.academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                        <p v-if="form.errors.academic_year_id" class="error-text">{{ form.errors.academic_year_id }}</p>
                    </div>
                    <div>
                        <label for="offering-form-section" class="label">Section</label>
                        <select id="offering-form-section" v-model="form.section_id" class="input">
                            <option :value="0" disabled>Select section</option>
                            <option v-for="section in availableSections" :key="section.id" :value="section.id">
                                {{ section.program }} {{ section.year_level }} — {{ section.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.section_id" class="error-text">{{ form.errors.section_id }}</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="offering-form-subject" class="label">Subject</label>
                        <select id="offering-form-subject" v-model="form.subject_id" class="input">
                            <option :value="0" disabled>Select subject</option>
                            <option v-for="subject in props.subjects" :key="subject.id" :value="subject.id">{{ subject.code }} — {{ subject.name }}</option>
                        </select>
                        <p v-if="selectedSubject" class="hint">{{ selectedSubject.units }} units</p>
                        <p v-if="form.errors.subject_id" class="error-text">{{ form.errors.subject_id }}</p>
                    </div>
                    <div>
                        <label for="offering-form-faculty" class="label">Faculty</label>
                        <select id="offering-form-faculty" v-model="form.faculty_id" class="input">
                            <option value="">Unassigned</option>
                            <option v-for="member in props.faculty" :key="member.id" :value="member.id">{{ member.name }}</option>
                        </select>
                        <p v-if="form.errors.faculty_id" class="error-text">{{ form.errors.faculty_id }}</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="offering-form-room" class="label">Room</label>
                        <input id="offering-form-room" v-model="form.room" type="text" class="input" placeholder="Room 301" />
                    </div>
                    <div>
                        <label for="offering-form-capacity" class="label">Capacity</label>
                        <input id="offering-form-capacity" v-model.number="form.capacity" type="number" min="1" class="input" />
                        <p v-if="form.errors.capacity" class="error-text">{{ form.errors.capacity }}</p>
                    </div>
                    <div>
                        <label for="offering-form-status" class="label">Status</label>
                        <select id="offering-form-status" v-model="form.status" class="input">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <span class="label mb-0">Schedule</span>
                        <button type="button" class="btn btn-secondary btn-sm" @click="addSchedule">
                            <Plus class="size-3.5" /> Add meeting
                        </button>
                    </div>
                    <p v-if="form.errors.schedules" class="error-text">{{ form.errors.schedules }}</p>

                    <div class="mt-2 space-y-2">
                        <div v-for="(schedule, index) in form.schedules" :key="index" class="grid gap-2 rounded-md border border-navy-100 bg-navy-50/40 p-3 sm:grid-cols-[1.2fr_0.8fr_0.8fr_1fr_auto]">
                            <div>
                                <label :for="`day-${index}`" class="label">Day</label>
                                <select :id="`day-${index}`" v-model="schedule.day_of_week" class="input input-sm">
                                    <option v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']" :key="day" :value="day">{{ day }}</option>
                                </select>
                            </div>
                            <div>
                                <label :for="`start-${index}`" class="label">Start</label>
                                <input :id="`start-${index}`" v-model="schedule.start_time" type="time" class="input input-sm" />
                            </div>
                            <div>
                                <label :for="`end-${index}`" class="label">End</label>
                                <input :id="`end-${index}`" v-model="schedule.end_time" type="time" class="input input-sm" />
                            </div>
                            <div>
                                <label :for="`room-${index}`" class="label">Room</label>
                                <input :id="`room-${index}`" v-model="schedule.room" type="text" class="input input-sm" placeholder="Optional" />
                            </div>
                            <div class="flex items-end">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm text-red-600"
                                    :disabled="form.schedules.length === 1"
                                    @click="removeSchedule(index)"
                                >
                                    <Trash2 class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Cancel</button>
                    <button type="submit" form="offering-form" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save offering' }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
