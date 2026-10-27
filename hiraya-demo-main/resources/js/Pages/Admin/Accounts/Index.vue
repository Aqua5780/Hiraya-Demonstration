<script setup lang="ts">
import { Copy, KeyRound, Mail, Plus, RefreshCw, ShieldCheck, UserPlus, Users } from '@lucide/vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatDateTime, titleCase } from '../../../lib/format';
import { showToast } from '../../../lib/toast';
import type { SharedPageProps } from '../../../types';

interface RoleOption {
    id: number;
    name: string;
    slug: string;
}

interface AccountRow {
    id: number;
    name: string;
    email: string;
    status: string;
    role: string | null;
    role_slug: string | null;
    employee_number: string | null;
    student_number: string | null;
    last_login_at: string | null;
    created_at: string;
}

interface StudentWithoutAccount {
    id: number;
    student_number: string;
    name: string;
    program: string | null;
    year_level: string | null;
    preview_email: string;
}

const props = defineProps<{
    users: {
        data: AccountRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: Record<string, string | undefined>;
    roles: RoleOption[];
    allRoles: RoleOption[];
    studentsWithoutAccounts: StudentWithoutAccount[];
    studentsWithoutAccountsTotal: number;
}>();

const page = usePage<SharedPageProps>();
const demoActivationUrl = computed(() => page.props.flash.demo_activation_url);
const currentUserId = computed(() => page.props.auth.user?.id ?? null);

const filter = ref({
    q: props.filters.q ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});

const studentSearch = ref('');
const filteredStudents = computed(() => {
    const query = studentSearch.value.trim().toLowerCase();

    if (query === '') {
        return props.studentsWithoutAccounts;
    }

    return props.studentsWithoutAccounts.filter(
        (student) =>
            student.name.toLowerCase().includes(query)
            || student.student_number.toLowerCase().includes(query),
    );
});

const showModal = ref(false);
const form = useForm({
    name: '',
    email: '',
    role: 'faculty',
    department: '',
});

const passwordUser = ref<AccountRow | null>(null);
const showPasswordModal = computed(() => passwordUser.value !== null);
const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

function applyFilters(): void {
    router.get('/admin/accounts', filter.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function openInvite(): void {
    form.reset();
    form.clearErrors();
    form.role = 'faculty';
    showModal.value = true;
}

function submit(): void {
    form.post('/admin/accounts/invite', {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
}

function resend(user: AccountRow): void {
    router.post(`/admin/accounts/${user.id}/resend`, {}, { preserveScroll: true });
}

function toggleStatus(user: AccountRow): void {
    const next = user.status === 'active' ? 'inactive' : 'active';

    showConfirm(
        {
            title: next === 'active' ? 'Activate account' : 'Deactivate account',
            message: `Set ${user.name}'s account to ${next}?`,
            confirmLabel: next === 'active' ? 'Activate' : 'Deactivate',
            tone: next === 'active' ? 'primary' : 'danger',
        },
        () => router.patch(`/admin/accounts/${user.id}/status`, { status: next }, { preserveScroll: true }),
    );
}

function createStudentAccount(student: StudentWithoutAccount): void {
    showConfirm(
        {
            title: 'Create student account',
            message: `Create ${student.preview_email} for ${student.name} (${student.student_number})? An activation link will be generated.`,
            confirmLabel: 'Create account',
        },
        () => router.post('/admin/accounts/students', { student_id: student.id }, { preserveScroll: true }),
    );
}

function openPassword(user: AccountRow): void {
    passwordUser.value = user;
    passwordForm.reset();
    passwordForm.clearErrors();
}

function submitPassword(): void {
    if (! passwordUser.value) {
        return;
    }

    passwordForm.patch(`/admin/accounts/${passwordUser.value.id}/password`, {
        preserveScroll: true,
        onSuccess: () => {
            passwordUser.value = null;
            passwordForm.reset();
        },
    });
}

function copyActivationUrl(): void {
    if (! demoActivationUrl.value) {
        return;
    }

    navigator.clipboard.writeText(demoActivationUrl.value);
    showToast('Activation link copied.');
}

function statusTone(status: string): 'green' | 'gold' | 'red' {
    if (status === 'active') return 'green';
    if (status === 'pending') return 'gold';
    return 'red';
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Accounts">
                <template #actions>
                    <button type="button" class="btn btn-primary" @click="openInvite">
                        <UserPlus class="size-4" />
                        Invite account
                    </button>
                </template>
            </PageHeader>

            <div v-if="demoActivationUrl" class="rounded-lg border border-gold-200 bg-gold-50 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gold-800">Demo activation link</p>
                        <a :href="demoActivationUrl" target="_blank" class="mt-1 block break-all text-xs font-semibold text-navy-700 underline">
                            {{ demoActivationUrl }}
                        </a>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm shrink-0" @click="copyActivationUrl">
                        <Copy class="size-3.5" /> Copy link
                    </button>
                </div>
            </div>

            <section class="card p-4">
                <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="applyFilters">
                    <div class="lg:col-span-2">
                        <label for="account-search" class="label">Search</label>
                        <input id="account-search" v-model="filter.q" type="search" class="input" placeholder="Name or email" />
                    </div>
                    <div>
                        <label for="account-role" class="label">Role</label>
                        <select id="account-role" v-model="filter.role" class="input">
                            <option value="">All roles</option>
                            <option v-for="role in props.allRoles" :key="role.id" :value="role.slug">{{ role.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="account-status" class="label">Status</label>
                        <select id="account-status" v-model="filter.status" class="input">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-secondary w-full">Filter</button>
                    </div>
                </form>
            </section>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <UserPlus class="size-4 text-navy-500" />
                        <h2 class="card-title">Students without accounts</h2>
                    </div>
                    <Badge tone="navy">{{ props.studentsWithoutAccountsTotal }} students</Badge>
                </div>

                <p class="px-5 pt-4 text-xs leading-5 text-navy-400">
                    School emails are fixed to <span class="font-mono">@gstc.edu.ph</span> and generated from the student's name. Duplicate addresses receive a numeric suffix.
                </p>

                <div class="px-5 pt-4">
                    <label for="student-search" class="label">Find student by name</label>
                    <input
                        id="student-search"
                        v-model="studentSearch"
                        type="search"
                        class="input"
                        placeholder="Student name or number"
                    />
                </div>

                <div v-if="filteredStudents.length" class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Student</th><th>Program</th><th>School email preview</th><th class="text-right">Action</th></tr></thead>
                        <tbody>
                            <tr v-for="student in filteredStudents" :key="student.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ student.name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ student.student_number }}</p>
                                </td>
                                <td class="text-xs">{{ student.program ?? '—' }} {{ student.year_level ?? '' }}</td>
                                <td class="font-mono text-xs">{{ student.preview_email }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="createStudentAccount(student)">
                                            <UserPlus class="size-3.5" /> Create account
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState v-else :title="props.studentsWithoutAccounts.length ? 'No matching students' : 'All students have accounts'" description="New students without accounts will appear here.">
                    <template #icon><Users class="size-6" /></template>
                </EmptyState>
            </section>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[60rem]">
                        <thead>
                            <tr><th>Account</th><th>Role</th><th>Identifier</th><th>Status</th><th>Last login</th><th class="text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in props.users.data" :key="user.id">
                                <td>
                                    <p class="font-semibold text-navy-800">{{ user.name }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">{{ user.email }}</p>
                                </td>
                                <td class="text-xs">{{ user.role ?? '—' }}</td>
                                <td class="font-mono text-xs">{{ user.employee_number ?? user.student_number ?? '—' }}</td>
                                <td><Badge :tone="statusTone(user.status)">{{ titleCase(user.status) }}</Badge></td>
                                <td class="text-xs">{{ user.last_login_at ? formatDateTime(user.last_login_at) : 'Never' }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <span v-if="user.id === currentUserId" class="self-center text-xs text-navy-300">Current account</span>
                                        <template v-else>
                                            <button
                                                v-if="user.role_slug === 'student'"
                                                type="button"
                                                class="btn btn-secondary btn-sm"
                                                @click="openPassword(user)"
                                            >
                                                <KeyRound class="size-3.5" /> Password
                                            </button>
                                            <button
                                                v-if="user.status === 'pending'"
                                                type="button"
                                                class="btn btn-secondary btn-sm"
                                                @click="resend(user)"
                                            >
                                                <RefreshCw class="size-3.5" /> Resend
                                            </button>
                                            <button
                                                v-else
                                                type="button"
                                                class="btn btn-secondary btn-sm"
                                                @click="toggleStatus(user)"
                                            >
                                                <ShieldCheck class="size-3.5" />
                                                {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.users.data.length === 0" class="empty-state">
                    <Users class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No accounts found</p>
                    <p class="text-xs text-navy-400">Adjust the filters or invite a new account.</p>
                </div>

                <Pagination
                    :links="props.users.links"
                    :from="props.users.from"
                    :to="props.users.to"
                    :total="props.users.total"
                />
            </section>
        </div>

        <Modal :show="showModal" title="Invite staff account" max-width="xl" @close="showModal = false">
            <form id="invite-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="invite-name" class="label">Full name</label>
                        <input id="invite-name" v-model="form.name" type="text" class="input" />
                        <p v-if="form.errors.name" class="error-text">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="invite-email" class="label">Email address</label>
                        <div class="relative">
                            <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                            <input id="invite-email" v-model="form.email" type="email" class="input pl-10" />
                        </div>
                        <p v-if="form.errors.email" class="error-text">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="invite-role" class="label">Role</label>
                        <select id="invite-role" v-model="form.role" class="input">
                            <option v-for="role in props.roles" :key="role.id" :value="role.slug">{{ role.name }}</option>
                        </select>
                        <p v-if="form.errors.role" class="error-text">{{ form.errors.role }}</p>
                    </div>
                    <div v-if="form.role === 'faculty'">
                        <label for="invite-department" class="label">Department</label>
                        <input id="invite-department" v-model="form.department" type="text" class="input" placeholder="Optional" />
                        <p v-if="form.errors.department" class="error-text">{{ form.errors.department }}</p>
                    </div>
                </div>

                <div class="rounded-md border border-navy-100 bg-navy-50/50 p-3 text-xs leading-5 text-navy-500">
                    An activation link will be generated. In demo mode the link is shown on this page instead of being emailed.
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Cancel</button>
                    <button type="submit" form="invite-form" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Creating…' : 'Create invitation' }}
                    </button>
                </div>
            </template>
        </Modal>

        <Modal :show="showPasswordModal" title="Set student password" max-width="md" @close="passwordUser = null">
            <form id="student-password-form" class="space-y-4 p-5" @submit.prevent="submitPassword">
                <p class="text-sm leading-6 text-navy-600">
                    Set a new password for <span class="font-semibold">{{ passwordUser?.name }}</span>. Existing sessions will be signed out.
                </p>

                <div>
                    <label for="student-password" class="label">New password</label>
                    <input
                        id="student-password"
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                        class="input"
                    />
                    <p v-if="passwordForm.errors.password" class="error-text">{{ passwordForm.errors.password }}</p>
                </div>

                <div>
                    <label for="student-password-confirmation" class="label">Confirm password</label>
                    <input
                        id="student-password-confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        class="input"
                    />
                </div>

                <p class="text-xs leading-5 text-navy-400">Use at least eight characters with letters and numbers.</p>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="passwordUser = null">Cancel</button>
                    <button type="submit" form="student-password-form" class="btn btn-primary" :disabled="passwordForm.processing">
                        {{ passwordForm.processing ? 'Updating…' : 'Update password' }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
