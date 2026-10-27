<script setup lang="ts">
import { Eye, Search, Wallet } from '@lucide/vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { formatCurrency } from '../../../lib/format';

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    program: string | null;
    section: string | null;
    assessed_amount: number;
    balance: number;
}

const props = defineProps<{
    year: { id: number; name: string } | null;
    students: {
        data: StudentRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    } | null;
    filters: Record<string, string | undefined>;
}>();

const search = ref(props.filters.q ?? '');

function applySearch(): void {
    router.get('/finance/accounts', { q: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Student ledger" />

            <div v-if="!props.year" class="rounded-lg border border-gold-200 bg-gold-50 p-4 text-sm text-gold-900">
                No academic year is active.
            </div>

            <template v-else>
                <section class="card p-4">
                    <form class="flex gap-2" @submit.prevent="applySearch">
                        <div class="relative flex-1">
                            <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                            <input v-model="search" type="search" class="input pl-10" placeholder="Search by name or student number" />
                        </div>
                        <button type="submit" class="btn btn-secondary">Search</button>
                    </form>
                </section>

                <section class="card">
                    <div class="card-header">
                        <h2 class="card-title">Accounts with balances</h2>
                        <span class="text-xs text-navy-400">{{ props.year.name }}</span>
                    </div>

                    <div v-if="props.students?.data.length" class="table-shell">
                        <table class="data-table min-w-[52rem]">
                            <thead><tr><th>Student</th><th>Program</th><th>Section</th><th>Assessed</th><th>Balance</th><th class="text-right">Actions</th></tr></thead>
                            <tbody>
                                <tr v-for="student in props.students.data" :key="student.id">
                                    <td>
                                        <p class="font-semibold text-navy-800">{{ student.name }}</p>
                                        <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }}</p>
                                    </td>
                                    <td>{{ student.program ?? '—' }}</td>
                                    <td>{{ student.section ?? '—' }}</td>
                                    <td>{{ formatCurrency(student.assessed_amount) }}</td>
                                    <td class="font-semibold">{{ formatCurrency(student.balance) }}</td>
                                    <td>
                                        <div class="flex justify-end">
                                            <Link :href="`/finance/accounts/${student.id}`" class="btn btn-secondary btn-sm">
                                                <Eye class="size-3.5" /> View
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <EmptyState v-else title="No accounts with balances" description="All student accounts are settled for the active year.">
                        <template #icon><Wallet class="size-6" /></template>
                    </EmptyState>

                    <Pagination
                        v-if="props.students"
                        :links="props.students.links"
                        :from="props.students.from"
                        :to="props.students.to"
                        :total="props.students.total"
                    />
                </section>
            </template>
        </div>
    </AppShell>
</template>
