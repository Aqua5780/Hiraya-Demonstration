<script setup lang="ts">
import { ArrowLeft, Save } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface StudentGrade {
    detail_id: number;
    student_number: string;
    name: string;
    midterm: number | null;
    final: number | null;
    final_rating: number | null;
    remarks: string | null;
}

const props = defineProps<{
    classOffering: {
        id: number;
        subject: string;
        subject_name: string;
        section: string;
        program: string;
        year_level: string;
        academic_year: string;
        passing_grade: number;
        midterm_weight: number;
        final_weight: number;
    };
    students: StudentGrade[];
}>();

const form = useForm({
    grades: props.students.map((student) => ({
        enrollment_detail_id: student.detail_id,
        midterm: student.midterm,
        final: student.final,
    })),
});

function previewFinal(index: number): string {
    const grade = form.grades[index];
    const midterm = grade.midterm === null || grade.midterm === ('' as unknown) ? null : Number(grade.midterm);
    const final = grade.final === null || grade.final === ('' as unknown) ? null : Number(grade.final);

    if ((midterm === null || Number.isNaN(midterm)) && (final === null || Number.isNaN(final))) {
        return '—';
    }

    const rating = (midterm ?? 0) * props.classOffering.midterm_weight
        + (final ?? 0) * props.classOffering.final_weight;

    return rating.toFixed(2);
}

function previewRemarks(index: number): string | null {
    const rating = previewFinal(index);

    if (rating === '—') {
        return null;
    }

    return Number(rating) >= props.classOffering.passing_grade ? 'Passed' : 'Failed';
}

function submit(): void {
    form.post(`/faculty/grades/${props.classOffering.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <Link href="/faculty/grades" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to grade sheets
            </Link>

            <PageHeader
                :title="`${classOffering.subject} · ${classOffering.section}`"
                :subtitle="`${classOffering.subject_name} · ${classOffering.program} ${classOffering.year_level} · ${classOffering.academic_year}`"
            />

            <form class="card" @submit.prevent="submit">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Grade encoding</h2>
                        <p class="mt-0.5 text-xs text-navy-400">
                            Final rating weights: {{ classOffering.midterm_weight * 100 }}% midterm, {{ classOffering.final_weight * 100 }}% final.
                        </p>
                    </div>
                    <Badge tone="navy">{{ students.length }} students</Badge>
                </div>

                <div v-if="students.length" class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Student</th><th>Midterm</th><th>Final</th><th>Final rating</th><th>Remarks</th></tr></thead>
                        <tbody>
                            <tr v-for="(student, index) in students" :key="student.detail_id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ student.name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }}</p>
                                </td>
                                <td>
                                    <input
                                        v-model.number="form.grades[index].midterm"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="input input-sm w-24"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model.number="form.grades[index].final"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="input input-sm w-24"
                                    />
                                </td>
                                <td class="font-semibold">{{ previewFinal(index) }}</td>
                                <td>
                                    <Badge v-if="previewRemarks(index)" :tone="previewRemarks(index) === 'Passed' ? 'green' : 'red'">
                                        {{ previewRemarks(index) }}
                                    </Badge>
                                    <span v-else class="text-xs text-navy-300">Pending</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-else title="No students enrolled" description="Students will appear here after enrollment.">
                    <template #icon><BookOpenCheck class="size-6" /></template>
                </EmptyState>

                <div class="flex items-center justify-between gap-3 border-t border-navy-100 px-5 py-4">
                    <p v-if="form.errors.grades" class="text-xs text-red-600">{{ form.errors.grades }}</p>
                    <span v-else />
                    <button type="submit" class="btn btn-primary" :disabled="form.processing || students.length === 0">
                        <Save class="size-4" />
                        {{ form.processing ? 'Saving…' : 'Save grades' }}
                    </button>
                </div>
            </form>
        </div>
    </AppShell>
</template>
