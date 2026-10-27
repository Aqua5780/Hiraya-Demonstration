<script setup lang="ts">
import { ArrowLeft, Save } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import PageHeader from '../../../Components/PageHeader.vue';

interface ProgramOption {
    id: number;
    code: string;
    name: string;
}

interface YearLevelOption {
    id: number;
    name: string;
}

const props = defineProps<{
    student: {
        id: number;
        student_number: string;
        first_name: string;
        middle_name: string | null;
        last_name: string;
        suffix: string | null;
        email: string | null;
        phone: string | null;
        address: string | null;
        birth_date: string | null;
        gender: string | null;
        status: string;
        program_id: number | null;
        year_level_id: number | null;
        guardian_name: string | null;
        guardian_phone: string | null;
        account_status: string | null;
    };
    programs: ProgramOption[];
    yearLevels: YearLevelOption[];
    canEditLoginEmail: boolean;
}>();

const emailLocked = computed(() => props.student.account_status !== null && !props.canEditLoginEmail);

const form = useForm({
    first_name: props.student.first_name,
    middle_name: props.student.middle_name ?? '',
    last_name: props.student.last_name,
    suffix: props.student.suffix ?? '',
    email: props.student.email ?? '',
    phone: props.student.phone ?? '',
    address: props.student.address ?? '',
    birth_date: props.student.birth_date ?? '',
    gender: props.student.gender ?? '',
    program_id: props.student.program_id ?? 0,
    year_level_id: props.student.year_level_id ?? 0,
    status: props.student.status,
    guardian_name: props.student.guardian_name ?? '',
    guardian_phone: props.student.guardian_phone ?? '',
});

function submit(): void {
    form.patch(`/registrar/students/${props.student.id}`);
}
</script>

<template>
    <AppShell>
        <div class="mx-auto max-w-4xl space-y-6">
            <Link href="/registrar/students" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to records
            </Link>

            <PageHeader :title="`Edit ${props.student.student_number}`" />

            <form class="card" @submit.prevent="submit">
                <div class="card-header">
                    <h2 class="card-title">Student information</h2>
                    <p class="text-xs text-navy-400">Account status: {{ props.student.account_status ?? 'No account' }}</p>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="label">First name</label>
                            <input id="first_name" v-model="form.first_name" type="text" class="input" />
                            <p v-if="form.errors.first_name" class="error-text">{{ form.errors.first_name }}</p>
                        </div>
                        <div>
                            <label for="middle_name" class="label">Middle name</label>
                            <input id="middle_name" v-model="form.middle_name" type="text" class="input" />
                        </div>
                        <div>
                            <label for="last_name" class="label">Last name</label>
                            <input id="last_name" v-model="form.last_name" type="text" class="input" />
                            <p v-if="form.errors.last_name" class="error-text">{{ form.errors.last_name }}</p>
                        </div>
                        <div>
                            <label for="suffix" class="label">Suffix</label>
                            <input id="suffix" v-model="form.suffix" type="text" class="input" placeholder="Jr., III" />
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="email" class="label">Email address</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="input"
                                :readonly="emailLocked"
                                :class="{ 'bg-navy-50 text-navy-500': emailLocked }"
                            />
                            <p v-if="emailLocked" class="mt-1 text-xs text-navy-400">Login email changes require an administrator.</p>
                            <p v-if="form.errors.email" class="error-text">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label for="phone" class="label">Phone</label>
                            <input id="phone" v-model="form.phone" type="text" class="input" />
                        </div>
                    </div>

                    <div>
                        <label for="address" class="label">Address</label>
                        <textarea id="address" v-model="form.address" rows="2" class="input" />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="birth_date" class="label">Birth date</label>
                            <input id="birth_date" v-model="form.birth_date" type="date" class="input" />
                        </div>
                        <div>
                            <label for="gender" class="label">Gender</label>
                            <input id="gender" v-model="form.gender" type="text" class="input" />
                        </div>
                        <div>
                            <label for="status" class="label">Record status</label>
                            <select id="status" v-model="form.status" class="input">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="graduated">Graduated</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="program_id" class="label">Program</label>
                            <select id="program_id" v-model="form.program_id" class="input">
                                <option v-for="program in props.programs" :key="program.id" :value="program.id">{{ program.code }} — {{ program.name }}</option>
                            </select>
                            <p v-if="form.errors.program_id" class="error-text">{{ form.errors.program_id }}</p>
                        </div>
                        <div>
                            <label for="year_level_id" class="label">Year level</label>
                            <select id="year_level_id" v-model="form.year_level_id" class="input">
                                <option v-for="level in props.yearLevels" :key="level.id" :value="level.id">{{ level.name }}</option>
                            </select>
                            <p v-if="form.errors.year_level_id" class="error-text">{{ form.errors.year_level_id }}</p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="guardian_name" class="label">Guardian name</label>
                            <input id="guardian_name" v-model="form.guardian_name" type="text" class="input" />
                        </div>
                        <div>
                            <label for="guardian_phone" class="label">Guardian phone</label>
                            <input id="guardian_phone" v-model="form.guardian_phone" type="text" class="input" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-navy-100 px-5 py-4">
                    <Link href="/registrar/students" class="btn btn-secondary">Cancel</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Save class="size-4" />
                        {{ form.processing ? 'Saving…' : 'Save changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppShell>
</template>
