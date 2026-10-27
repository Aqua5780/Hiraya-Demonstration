<script setup lang="ts">
import { Eye, Pencil, Search, Upload, Users } from '@lucide/vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { formatCurrency, titleCase } from '../../../lib/format';

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    email: string | null;
    program: string | null;
    program_name: string | null;
    year_level: string | null;
    section: string | null;
    status: string;
    account_status: string | null;
    balance: number | null;
    enrolled: boolean;
}

interface Option {
    id: number;
    name?: string;
    code?: string;
}

const props = defineProps<{
    students: {
        data: StudentRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | undefined>;
    currentYear: { id: number; name: string } | null;
    programs: Option[];
    yearLevels: Option[];
    sections: Option[];
}>();

const filter = ref({
    q: props.filters.q ?? '',
    program_id: props.filters.program_id ?? '',
    year_level_id: props.filters.year_level_id ?? '',
    section_id: props.filters.section_id ?? '',
    status: props.filters.status ?? '',
    sort: props.filters.sort ?? 'name',
    direction: props.filters.direction ?? 'asc',
});

function applyFilters(): void {
    router.get('/registrar/students', filter.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters(): void {
    filter.value = {
        q: '',
        program_id: '',
        year_level_id: '',
        section_id: '',
        status: '',
        sort: 'name',
        direction: 'asc',
    };
    applyFilters();
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Student records">
                <template #actions>
                    <Link href="/registrar/import" class="btn btn-secondary">
                        <Upload class="size-4" />
                        Import records
                    </Link>
                </template>
            </PageHeader>

            <section class="card p-4">
                <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
                    <div class="lg:col-span-2">
                        <label for="student-search" class="label">Search</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                            <input id="student-search" v-model="filter.q" type="search" class="input pl-10" placeholder="Name or student number" />
                        </div>
                    </div>
                    <div>
                        <label for="student-program" class="label">Program</label>
                        <select id="student-program" v-model="filter.program_id" class="input">
                            <option value="">All programs</option>
                            <option v-for="program in props.programs" :key="program.id" :value="String(program.id)">{{ program.code }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="student-year" class="label">Year level</label>
                        <select id="student-year" v-model="filter.year_level_id" class="input">
                            <option value="">All year levels</option>
                            <option v-for="level in props.yearLevels" :key="level.id" :value="String(level.id)">{{ level.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="student-section" class="label">Section</label>
                        <select id="student-section" v-model="filter.section_id" class="input">
                            <option value="">All sections</option>
                            <option v-for="section in props.sections" :key="section.id" :value="String(section.id)">{{ section.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="student-status" class="label">Status</label>
                        <select id="student-status" v-model="filter.status" class="input">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="graduated">Graduated</option>
                        </select>
                    </div>
                    <div>
                        <label for="student-sort" class="label">Sort by</label>
                        <select id="student-sort" v-model="filter.sort" class="input">
                            <option value="name">Name</option>
                            <option value="student_number">Student number</option>
                            <option value="year_level">Year level</option>
                            <option value="section">Section</option>
                        </select>
                    </div>
                    <div>
                        <label for="student-direction" class="label">Order</label>
                        <select id="student-direction" v-model="filter.direction" class="input">
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-secondary flex-1">Apply</button>
                        <button type="button" class="btn btn-ghost" @click="resetFilters">Reset</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[64rem]">
                        <thead>
                            <tr><th>Student</th><th>Program</th><th>Year level</th><th>Section</th><th>Status</th><th>Balance</th><th class="text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="student in props.students.data" :key="student.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ student.name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }} · {{ student.email ?? 'No email' }}</p>
                                </td>
                                <td>{{ student.program ?? '—' }}</td>
                                <td>{{ student.year_level ?? '—' }}</td>
                                <td>
                                    <p>{{ student.section ?? '—' }}</p>
                                    <p v-if="!student.enrolled" class="mt-0.5 text-[0.68rem] text-gold-700">Not enrolled</p>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge :tone="student.status === 'active' ? 'green' : 'gray'">{{ titleCase(student.status) }}</Badge>
                                        <Badge v-if="student.account_status === 'pending'" tone="gold">Account pending</Badge>
                                    </div>
                                </td>
                                <td>{{ student.balance !== null ? formatCurrency(student.balance) : '—' }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <Link :href="`/registrar/students/${student.id}`" class="btn btn-secondary btn-sm">
                                            <Eye class="size-3.5" /> View
                                        </Link>
                                        <Link :href="`/registrar/students/${student.id}/edit`" class="btn btn-ghost btn-sm">
                                            <Pencil class="size-3.5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.students.data.length === 0" class="empty-state">
                    <Users class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No students found</p>
                    <p class="text-xs text-navy-400">Adjust the filters or import student records.</p>
                </div>

                <Pagination
                    :links="props.students.links"
                    :from="props.students.from"
                    :to="props.students.to"
                    :total="props.students.total"
                />
            </section>

            <p class="text-xs text-navy-400">
                Active year: {{ props.currentYear?.name ?? 'None' }}
            </p>
        </div>
    </AppShell>
</template>
