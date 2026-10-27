<script setup lang="ts">
import { BookOpen, CalendarRange, GraduationCap, Pencil, Plus, School, Users } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { titleCase } from '../../../lib/format';

interface Program {
    id: number;
    code: string;
    name: string;
    level: string;
    description: string | null;
    status: string;
}

interface YearLevel {
    id: number;
    name: string;
    level: string;
    sort_order: number;
}

interface AcademicYearOption {
    id: number;
    name: string;
    is_active: boolean;
}

interface FacultyOption {
    id: number;
    employee_number: string;
    name: string | null;
}

interface Section {
    id: number;
    academic_year_id: number;
    program_id: number;
    year_level_id: number;
    name: string;
    capacity: number;
    adviser_id: number | null;
    status: string;
    program?: { id: number; code: string; name: string };
    yearLevel?: { id: number; name: string };
    academicYear?: { id: number; name: string };
    adviser?: { id: number; employee_number: string; user?: { name: string } };
}

interface Subject {
    id: number;
    code: string;
    name: string;
    units: string | number;
    program_id: number | null;
    year_level_id: number | null;
    type: string;
    status: string;
    program?: { id: number; code: string };
    yearLevel?: { id: number; name: string };
}

const props = defineProps<{
    programs: Program[];
    yearLevels: YearLevel[];
    academicYears: AcademicYearOption[];
    faculty: FacultyOption[];
    sections: Section[];
    subjects: Subject[];
}>();

type Tab = 'programs' | 'year-levels' | 'sections' | 'subjects';
type ModalType = 'program' | 'year-level' | 'section' | 'subject';

const activeTab = ref<Tab>('programs');
const modalType = ref<ModalType | null>(null);
const editingId = ref<number | null>(null);

const activeYearId = computed(() => props.academicYears.find((year) => year.is_active)?.id ?? props.academicYears[0]?.id ?? null);

const addLabel = computed(() => ({
    programs: 'Add program',
    'year-levels': 'Add year level',
    sections: 'Add section',
    subjects: 'Add subject',
}[activeTab.value]));

const programForm = useForm({
    code: '',
    name: '',
    level: 'college',
    description: '',
    status: 'active',
});

const yearLevelForm = useForm({
    name: '',
    level: 'college',
    sort_order: 0,
});

const sectionForm = useForm({
    academic_year_id: activeYearId.value ?? 0,
    program_id: 0,
    year_level_id: 0,
    name: '',
    capacity: 40,
    adviser_id: '',
    status: 'active',
});

const subjectForm = useForm({
    code: '',
    name: '',
    units: 3,
    program_id: '',
    year_level_id: '',
    type: 'core',
    status: 'active',
});

const formId = computed(() => {
    switch (modalType.value) {
        case 'program':
            return 'program-form';
        case 'year-level':
            return 'year-level-form';
        case 'section':
            return 'section-form';
        case 'subject':
            return 'subject-form';
        default:
            return '';
    }
});

const modalTitle = computed(() => {
    const noun = {
        program: 'program',
        'year-level': 'year level',
        section: 'section',
        subject: 'subject',
    }[modalType.value ?? 'program'];

    return `${editingId.value ? 'Edit' : 'New'} ${noun}`;
});

function openModal(type: ModalType): void {
    modalType.value = type;
    editingId.value = null;

    if (type === 'program') {
        programForm.reset();
        programForm.clearErrors();
    } else if (type === 'year-level') {
        yearLevelForm.reset();
        yearLevelForm.clearErrors();
    } else if (type === 'section') {
        sectionForm.reset();
        sectionForm.clearErrors();
        sectionForm.academic_year_id = activeYearId.value ?? 0;
    } else {
        subjectForm.reset();
        subjectForm.clearErrors();
    }
}

function editProgram(program: Program): void {
    modalType.value = 'program';
    editingId.value = program.id;
    programForm.clearErrors();
    programForm.code = program.code;
    programForm.name = program.name;
    programForm.level = program.level;
    programForm.description = program.description ?? '';
    programForm.status = program.status;
}

function editYearLevel(yearLevel: YearLevel): void {
    modalType.value = 'year-level';
    editingId.value = yearLevel.id;
    yearLevelForm.clearErrors();
    yearLevelForm.name = yearLevel.name;
    yearLevelForm.level = yearLevel.level;
    yearLevelForm.sort_order = yearLevel.sort_order;
}

function editSection(section: Section): void {
    modalType.value = 'section';
    editingId.value = section.id;
    sectionForm.clearErrors();
    sectionForm.academic_year_id = section.academic_year_id;
    sectionForm.program_id = section.program_id;
    sectionForm.year_level_id = section.year_level_id;
    sectionForm.name = section.name;
    sectionForm.capacity = section.capacity;
    sectionForm.adviser_id = section.adviser_id ?? '';
    sectionForm.status = section.status;
}

function editSubject(subject: Subject): void {
    modalType.value = 'subject';
    editingId.value = subject.id;
    subjectForm.clearErrors();
    subjectForm.code = subject.code;
    subjectForm.name = subject.name;
    subjectForm.units = Number(subject.units);
    subjectForm.program_id = subject.program_id ?? '';
    subjectForm.year_level_id = subject.year_level_id ?? '';
    subjectForm.type = subject.type;
    subjectForm.status = subject.status;
}

function closeModal(): void {
    modalType.value = null;
    editingId.value = null;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    };

    if (modalType.value === 'program') {
        editingId.value
            ? programForm.patch(`/admin/master-data/programs/${editingId.value}`, options)
            : programForm.post('/admin/master-data/programs', options);
    } else if (modalType.value === 'year-level') {
        editingId.value
            ? yearLevelForm.patch(`/admin/master-data/year-levels/${editingId.value}`, options)
            : yearLevelForm.post('/admin/master-data/year-levels', options);
    } else if (modalType.value === 'section') {
        editingId.value
            ? sectionForm.patch(`/admin/master-data/sections/${editingId.value}`, options)
            : sectionForm.post('/admin/master-data/sections', options);
    } else if (modalType.value === 'subject') {
        editingId.value
            ? subjectForm.patch(`/admin/master-data/subjects/${editingId.value}`, options)
            : subjectForm.post('/admin/master-data/subjects', options);
    }
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Master data">
                <template #actions>
                    <button type="button" class="btn btn-primary" @click="openModal(activeTab === 'programs' ? 'program' : activeTab === 'year-levels' ? 'year-level' : activeTab === 'sections' ? 'section' : 'subject')">
                        <Plus class="size-4" />
                        {{ addLabel }}
                    </button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap gap-2 border-b border-navy-100 pb-2">
                <button
                    v-for="tab in [
                        { key: 'programs', label: 'Programs', icon: School },
                        { key: 'year-levels', label: 'Year levels', icon: GraduationCap },
                        { key: 'sections', label: 'Sections', icon: Users },
                        { key: 'subjects', label: 'Subjects', icon: BookOpen },
                    ]"
                    :key="tab.key"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition"
                    :class="activeTab === tab.key ? 'bg-navy-900 text-white' : 'text-navy-500 hover:bg-navy-50 hover:text-navy-800'"
                    @click="activeTab = tab.key"
                >
                    <component :is="tab.icon" class="size-4" />
                    {{ tab.label }}
                </button>
            </div>

            <section v-if="activeTab === 'programs'" class="card">
                <div class="table-shell">
                    <table class="data-table">
                        <thead>
                            <tr><th>Code</th><th>Program</th><th>Level</th><th>Status</th><th class="text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="program in props.programs" :key="program.id">
                                <td class="font-mono text-xs font-semibold">{{ program.code }}</td>
                                <td>
                                    <p class="font-semibold text-navy-800">{{ program.name }}</p>
                                    <p v-if="program.description" class="mt-0.5 text-xs text-navy-400">{{ program.description }}</p>
                                </td>
                                <td>{{ titleCase(program.level) }}</td>
                                <td><Badge :tone="program.status === 'active' ? 'green' : 'gray'">{{ titleCase(program.status) }}</Badge></td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="editProgram(program)">
                                            <Pencil class="size-3.5" /> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-else-if="activeTab === 'year-levels'" class="card">
                <div class="table-shell">
                    <table class="data-table">
                        <thead><tr><th>Year level</th><th>Level</th><th>Sort order</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="level in props.yearLevels" :key="level.id">
                                <td class="font-semibold text-navy-800">{{ level.name }}</td>
                                <td>{{ titleCase(level.level) }}</td>
                                <td>{{ level.sort_order }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="editYearLevel(level)">
                                            <Pencil class="size-3.5" /> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-else-if="activeTab === 'sections'" class="card">
                <div class="table-shell">
                    <table class="data-table">
                        <thead><tr><th>School year</th><th>Program</th><th>Year level</th><th>Section</th><th>Capacity</th><th>Adviser</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="section in props.sections" :key="section.id">
                                <td class="text-xs">{{ section.academicYear?.name }}</td>
                                <td class="font-mono text-xs">{{ section.program?.code }}</td>
                                <td>{{ section.yearLevel?.name }}</td>
                                <td class="font-semibold text-navy-800">{{ section.name }}</td>
                                <td>{{ section.capacity }}</td>
                                <td class="text-xs">{{ section.adviser?.user?.name ?? '—' }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="editSection(section)">
                                            <Pencil class="size-3.5" /> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-else class="card">
                <div class="table-shell">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Subject</th><th>Units</th><th>Program</th><th>Year level</th><th>Type</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="subject in props.subjects" :key="subject.id">
                                <td class="font-mono text-xs font-semibold">{{ subject.code }}</td>
                                <td class="font-semibold text-navy-800">{{ subject.name }}</td>
                                <td>{{ subject.units }}</td>
                                <td>{{ subject.program?.code ?? 'General' }}</td>
                                <td>{{ subject.yearLevel?.name ?? 'Any' }}</td>
                                <td>{{ titleCase(subject.type) }}</td>
                                <td><Badge :tone="subject.status === 'active' ? 'green' : 'gray'">{{ titleCase(subject.status) }}</Badge></td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="editSubject(subject)">
                                            <Pencil class="size-3.5" /> Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <Modal :show="modalType !== null" :title="modalTitle" max-width="xl" @close="closeModal">
            <form v-if="modalType === 'program'" id="program-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-[0.4fr_1fr]">
                    <div>
                        <label for="program-code" class="label">Code</label>
                        <input id="program-code" v-model="programForm.code" type="text" class="input" placeholder="BSTM" />
                        <p v-if="programForm.errors.code" class="error-text">{{ programForm.errors.code }}</p>
                    </div>
                    <div>
                        <label for="program-name" class="label">Program name</label>
                        <input id="program-name" v-model="programForm.name" type="text" class="input" />
                        <p v-if="programForm.errors.name" class="error-text">{{ programForm.errors.name }}</p>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="program-level" class="label">Level</label>
                        <select id="program-level" v-model="programForm.level" class="input">
                            <option value="elementary">Elementary</option>
                            <option value="junior_high">Junior High School</option>
                            <option value="senior_high">Senior High School</option>
                            <option value="college">College</option>
                        </select>
                        <p v-if="programForm.errors.level" class="error-text">{{ programForm.errors.level }}</p>
                    </div>
                    <div>
                        <label for="program-status" class="label">Status</label>
                        <select id="program-status" v-model="programForm.status" class="input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="program-description" class="label">Description</label>
                    <textarea id="program-description" v-model="programForm.description" rows="3" class="input" />
                </div>
            </form>

            <form v-else-if="modalType === 'year-level'" id="year-level-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div>
                    <label for="year-name" class="label">Year level</label>
                    <input id="year-name" v-model="yearLevelForm.name" type="text" class="input" placeholder="1st Year" />
                    <p v-if="yearLevelForm.errors.name" class="error-text">{{ yearLevelForm.errors.name }}</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="year-level" class="label">Level</label>
                        <select id="year-level" v-model="yearLevelForm.level" class="input">
                            <option value="elementary">Elementary</option>
                            <option value="junior_high">Junior High School</option>
                            <option value="senior_high">Senior High School</option>
                            <option value="college">College</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort-order" class="label">Sort order</label>
                        <input id="sort-order" v-model.number="yearLevelForm.sort_order" type="number" min="0" class="input" />
                    </div>
                </div>
            </form>

            <form v-else-if="modalType === 'section'" id="section-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="section-year" class="label">School year</label>
                        <select id="section-year" v-model="sectionForm.academic_year_id" class="input">
                            <option v-for="year in props.academicYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                        </select>
                        <p v-if="sectionForm.errors.academic_year_id" class="error-text">{{ sectionForm.errors.academic_year_id }}</p>
                    </div>
                    <div>
                        <label for="section-program" class="label">Program</label>
                        <select id="section-program" v-model="sectionForm.program_id" class="input">
                            <option :value="0" disabled>Select program</option>
                            <option v-for="program in props.programs" :key="program.id" :value="program.id">{{ program.code }} — {{ program.name }}</option>
                        </select>
                        <p v-if="sectionForm.errors.program_id" class="error-text">{{ sectionForm.errors.program_id }}</p>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="section-level" class="label">Year level</label>
                        <select id="section-level" v-model="sectionForm.year_level_id" class="input">
                            <option :value="0" disabled>Select year level</option>
                            <option v-for="level in props.yearLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
                        </select>
                        <p v-if="sectionForm.errors.year_level_id" class="error-text">{{ sectionForm.errors.year_level_id }}</p>
                    </div>
                    <div>
                        <label for="section-name" class="label">Section name</label>
                        <input id="section-name" v-model="sectionForm.name" type="text" class="input" placeholder="1-A" />
                        <p v-if="sectionForm.errors.name" class="error-text">{{ sectionForm.errors.name }}</p>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="section-capacity" class="label">Capacity</label>
                        <input id="section-capacity" v-model.number="sectionForm.capacity" type="number" min="1" class="input" />
                        <p v-if="sectionForm.errors.capacity" class="error-text">{{ sectionForm.errors.capacity }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="section-adviser" class="label">Adviser</label>
                        <select id="section-adviser" v-model="sectionForm.adviser_id" class="input">
                            <option value="">No adviser</option>
                            <option v-for="member in props.faculty" :key="member.id" :value="member.id">{{ member.name }} ({{ member.employee_number }})</option>
                        </select>
                    </div>
                </div>
            </form>

            <form v-else-if="modalType === 'subject'" id="subject-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-[0.4fr_1fr]">
                    <div>
                        <label for="subject-code" class="label">Code</label>
                        <input id="subject-code" v-model="subjectForm.code" type="text" class="input" placeholder="GEC101" />
                        <p v-if="subjectForm.errors.code" class="error-text">{{ subjectForm.errors.code }}</p>
                    </div>
                    <div>
                        <label for="subject-name" class="label">Subject name</label>
                        <input id="subject-name" v-model="subjectForm.name" type="text" class="input" />
                        <p v-if="subjectForm.errors.name" class="error-text">{{ subjectForm.errors.name }}</p>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label for="subject-units" class="label">Units</label>
                        <input id="subject-units" v-model.number="subjectForm.units" type="number" min="0.5" step="0.5" class="input" />
                        <p v-if="subjectForm.errors.units" class="error-text">{{ subjectForm.errors.units }}</p>
                    </div>
                    <div>
                        <label for="subject-program" class="label">Program</label>
                        <select id="subject-program" v-model="subjectForm.program_id" class="input">
                            <option value="">General</option>
                            <option v-for="program in props.programs" :key="program.id" :value="program.id">{{ program.code }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="subject-level" class="label">Year level</label>
                        <select id="subject-level" v-model="subjectForm.year_level_id" class="input">
                            <option value="">Any</option>
                            <option v-for="level in props.yearLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="subject-type" class="label">Type</label>
                        <select id="subject-type" v-model="subjectForm.type" class="input">
                            <option value="core">Core</option>
                            <option value="major">Major</option>
                            <option value="elective">Elective</option>
                        </select>
                    </div>
                    <div>
                        <label for="subject-status" class="label">Status</label>
                        <select id="subject-status" v-model="subjectForm.status" class="input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                    <button type="submit" :form="formId" class="btn btn-primary">Save</button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
