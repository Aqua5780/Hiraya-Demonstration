<script setup lang="ts">
import { ScrollText } from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { formatDateTime } from '../../../lib/format';

interface AuditRow {
    id: number;
    user: string | null;
    email: string | null;
    action_type: string;
    entity_name: string;
    record_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
}

const props = defineProps<{
    logs: {
        data: AuditRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | undefined>;
    actions: string[];
    entities: string[];
}>();

const filter = ref({
    q: props.filters.q ?? '',
    action: props.filters.action ?? '',
    entity: props.filters.entity ?? '',
    date: props.filters.date ?? '',
});

const expanded = ref<number | null>(null);

function applyFilters(): void {
    router.get('/admin/audit-logs', filter.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function toggleDetails(id: number): void {
    expanded.value = expanded.value === id ? null : id;
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Audit logs" />

            <section class="card p-4">
                <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
                    <div class="lg:col-span-2">
                        <label for="audit-search" class="label">Search</label>
                        <input id="audit-search" v-model="filter.q" type="search" class="input" placeholder="Action, entity, or user" />
                    </div>
                    <div>
                        <label for="audit-action" class="label">Action</label>
                        <select id="audit-action" v-model="filter.action" class="input">
                            <option value="">All actions</option>
                            <option v-for="action in props.actions" :key="action" :value="action">{{ action }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="audit-entity" class="label">Entity</label>
                        <select id="audit-entity" v-model="filter.entity" class="input">
                            <option value="">All entities</option>
                            <option v-for="entity in props.entities" :key="entity" :value="entity">{{ entity }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="audit-date" class="label">Date</label>
                        <input id="audit-date" v-model="filter.date" type="date" class="input" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-secondary w-full">Filter</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[60rem]">
                        <thead>
                            <tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>Record</th><th>IP</th><th class="text-right">Details</th></tr>
                        </thead>
                        <tbody>
                            <template v-for="log in props.logs.data" :key="log.id">
                                <tr>
                                    <td class="whitespace-nowrap text-xs">{{ formatDateTime(log.created_at) }}</td>
                                    <td>
                                        <p class="text-xs font-semibold text-navy-700">{{ log.user ?? 'System' }}</p>
                                        <p v-if="log.email" class="text-[0.68rem] text-navy-400">{{ log.email }}</p>
                                    </td>
                                    <td><Badge tone="navy">{{ log.action_type }}</Badge></td>
                                    <td class="text-xs">{{ log.entity_name }}</td>
                                    <td class="text-xs">{{ log.record_id ?? '—' }}</td>
                                    <td class="font-mono text-[0.68rem]">{{ log.ip_address ?? '—' }}</td>
                                    <td>
                                        <div class="flex justify-end">
                                            <button type="button" class="btn btn-ghost btn-sm" @click="toggleDetails(log.id)">
                                                {{ expanded === log.id ? 'Hide' : 'View' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="expanded === log.id">
                                    <td colspan="7" class="bg-navy-50/50">
                                        <div class="grid gap-4 py-2 sm:grid-cols-2">
                                            <div>
                                                <p class="mb-1 text-[0.68rem] font-bold uppercase tracking-wide text-navy-500">Old values</p>
                                                <pre class="max-h-48 overflow-auto rounded border border-navy-100 bg-white p-3 text-[0.68rem] leading-5 text-navy-600">{{ log.old_values ? JSON.stringify(log.old_values, null, 2) : '—' }}</pre>
                                            </div>
                                            <div>
                                                <p class="mb-1 text-[0.68rem] font-bold uppercase tracking-wide text-navy-500">New values</p>
                                                <pre class="max-h-48 overflow-auto rounded border border-navy-100 bg-white p-3 text-[0.68rem] leading-5 text-navy-600">{{ log.new_values ? JSON.stringify(log.new_values, null, 2) : '—' }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.logs.data.length === 0" class="empty-state">
                    <ScrollText class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No audit entries found</p>
                    <p class="text-xs text-navy-400">Adjust the filters to see more activity.</p>
                </div>

                <Pagination
                    :links="props.logs.links"
                    :from="props.logs.from"
                    :to="props.logs.to"
                    :total="props.logs.total"
                />
            </section>
        </div>
    </AppShell>
</template>
