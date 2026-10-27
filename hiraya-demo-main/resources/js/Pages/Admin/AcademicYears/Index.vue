<script setup lang="ts">
import { CalendarDays, Pencil, Plus } from '@lucide/vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatDate, titleCase } from '../../../lib/format';

interface AcademicYearRow {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    enrollment_start: string;
    enrollment_end: string;
    status: string;
    is_active: boolean;
    enrollment_state: string;
    sections_count: number;
    offerings_count: number;
    enrollments_count: number;
}

const props = defineProps<{
    years: AcademicYearRow[];
}>();

const showModal = ref(false);
const editing = ref<AcademicYearRow | null>(null);

const form = useForm({
    name: '',
    start_date: '',
    end_date: '',
    enrollment_start: '',
    enrollment_end: '',
    status: 'upcoming',
});

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'upcoming';
    showModal.value = true;
}

function openEdit(year: AcademicYearRow): void {
    editing.value = year;
    form.clearErrors();
    form.name = year.name;
    form.start_date = year.start_date;
    form.end_date = year.end_date;
    form.enrollment_start = year.enrollment_start;
    form.enrollment_end = year.enrollment_end;
    form.status = year.status;
    showModal.value = true;
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
        form.patch(`/admin/academic-years/${editing.value.id}`, options);
    } else {
        form.post('/admin/academic-years', options);
    }
}

function activate(year: AcademicYearRow): void {
    showConfirm(
        {
            title: 'Activate academic year',
            message: `Make ${year.name} the active academic year?`,
            confirmLabel: 'Activate',
        },
        () => router.post(`/admin/academic-years/${year.id}/activate`, {}, { preserveScroll: true }),
    );
}

function statusTone(status: string): 'green' | 'gold' | 'gray' {
    if (status === 'active') return 'green';
    if (status === 'upcoming') return 'gold';
    return 'gray';
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Academic years">
                <template #actions>
                    <button type="button" class="btn btn-primary" @click="openCreate">
                        <Plus class="size-4" />
                        New academic year
                    </button>
                </template>
            </PageHeader>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>School year</th>
                                <th>Dates</th>
                                <th>Enrollment window</th>
                                <th>Sections</th>
                                <th>Offerings</th>
                                <th>Enrollments</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="year in props.years" :key="year.id">
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="whitespace-nowrap font-semibold text-navy-900">{{ year.name }}</span>
                                        <Badge v-if="year.is_active" tone="green">Active</Badge>
                                    </div>
                                </td>
                                <td class="text-xs">{{ formatDate(year.start_date) }} – {{ formatDate(year.end_date) }}</td>
                                <td class="text-xs">
                                    {{ formatDate(year.enrollment_start) }} – {{ formatDate(year.enrollment_end) }}
                                </td>
                                <td>{{ year.sections_count }}</td>
                                <td>{{ year.offerings_count }}</td>
                                <td>{{ year.enrollments_count }}</td>
                                <td>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <Badge :tone="statusTone(year.status)">{{ titleCase(year.status) }}</Badge>
                                        <Badge
                                            v-if="year.status === 'active'"
                                            :tone="year.enrollment_state === 'open' ? 'green' : year.enrollment_state === 'upcoming' ? 'gold' : 'red'"
                                        >
                                            {{ year.enrollment_state === 'open' ? 'Enrollment open' : year.enrollment_state === 'upcoming' ? 'Opening soon' : 'Enrollment closed' }}
                                        </Badge>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openEdit(year)">
                                            <Pencil class="size-3.5" />
                                            Edit
                                        </button>
                                        <button
                                            v-if="!year.is_active"
                                            type="button"
                                            class="btn btn-gold btn-sm"
                                            @click="activate(year)"
                                        >
                                            Activate
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.years.length === 0" class="empty-state">
                    <CalendarDays class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No academic years yet</p>
                    <p class="text-xs text-navy-400">Create the first academic year to start setting up sections and classes.</p>
                </div>
            </section>
        </div>

        <Modal :show="showModal" :title="editing ? 'Edit academic year' : 'New academic year'" max-width="xl" @close="showModal = false">
            <form id="academic-year-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div>
                    <label for="name" class="label">School year</label>
                    <input id="name" v-model="form.name" type="text" class="input" placeholder="SY 2026-2027" />
                    <p class="hint">Format: SY YYYY-YYYY</p>
                    <p v-if="form.errors.name" class="error-text">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="start_date" class="label">Start date</label>
                        <input id="start_date" v-model="form.start_date" type="date" class="input" />
                        <p v-if="form.errors.start_date" class="error-text">{{ form.errors.start_date }}</p>
                    </div>
                    <div>
                        <label for="end_date" class="label">End date</label>
                        <input id="end_date" v-model="form.end_date" type="date" class="input" />
                        <p v-if="form.errors.end_date" class="error-text">{{ form.errors.end_date }}</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="enrollment_start" class="label">Enrollment start</label>
                        <input id="enrollment_start" v-model="form.enrollment_start" type="date" class="input" />
                        <p v-if="form.errors.enrollment_start" class="error-text">{{ form.errors.enrollment_start }}</p>
                    </div>
                    <div>
                        <label for="enrollment_end" class="label">Enrollment end</label>
                        <input id="enrollment_end" v-model="form.enrollment_end" type="date" class="input" />
                        <p v-if="form.errors.enrollment_end" class="error-text">{{ form.errors.enrollment_end }}</p>
                    </div>
                </div>

                <div>
                    <label for="status" class="label">Status</label>
                    <select id="status" v-model="form.status" class="input">
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                    <p class="hint">Setting an academic year to active will deactivate the current one.</p>
                    <p v-if="form.errors.status" class="error-text">{{ form.errors.status }}</p>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Cancel</button>
                    <button type="submit" form="academic-year-form" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save academic year' }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
