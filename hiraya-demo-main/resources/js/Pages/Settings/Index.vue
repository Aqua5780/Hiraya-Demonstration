<script setup lang="ts">
import { ArrowRight, Eye, EyeOff, KeyRound, LockKeyhole, UserRound } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../Components/AppShell.vue';
import PageHeader from '../../Components/PageHeader.vue';

defineProps<{
    account: {
        name: string;
        email: string;
        identifier: string | null;
        identifier_label: string | null;
    };
}>();

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showPasswords = ref(false);

function updatePassword(): void {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <AppShell>
        <div class="max-w-3xl space-y-6">
            <PageHeader title="Account settings" />

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                            <UserRound class="size-4" />
                        </span>
                        <h2 class="card-title">Profile</h2>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <div v-if="account.identifier" class="grid gap-1.5">
                        <span class="label">{{ account.identifier_label }}</span>
                        <input
                            :value="account.identifier"
                            type="text"
                            class="input bg-navy-50 text-navy-500"
                            disabled
                        />
                    </div>

                    <div>
                        <label for="name" class="label">Name</label>
                        <input
                            id="name"
                            :value="account.name"
                            type="text"
                            class="input bg-navy-50 text-navy-500"
                            disabled
                        />
                        <p class="hint">Name changes are handled by the administrator.</p>
                    </div>

                    <div>
                        <label for="email" class="label">Email address</label>
                        <input
                            id="email"
                            :value="account.email"
                            type="email"
                            class="input bg-navy-50 text-navy-500"
                            disabled
                        />
                        <p class="hint">Email changes are handled by the administrator.</p>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                            <KeyRound class="size-4" />
                        </span>
                        <h2 class="card-title">Password</h2>
                    </div>
                </div>

                <form class="space-y-5 p-5" @submit.prevent="updatePassword">
                    <div>
                        <label for="current_password" class="label">Current password</label>
                        <div class="relative">
                            <LockKeyhole class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                            <input
                                id="current_password"
                                v-model="passwordForm.current_password"
                                :type="showPasswords ? 'text' : 'password'"
                                name="current_password"
                                autocomplete="current-password"
                                class="input pl-10 pr-11"
                            />
                            <button
                                type="button"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded p-2 text-navy-400 transition hover:bg-navy-50 hover:text-navy-700"
                                :aria-label="showPasswords ? 'Hide passwords' : 'Show passwords'"
                                @click="showPasswords = !showPasswords"
                            >
                                <EyeOff v-if="showPasswords" class="size-4" />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.current_password" class="error-text">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="password" class="label">New password</label>
                            <input
                                id="password"
                                v-model="passwordForm.password"
                                :type="showPasswords ? 'text' : 'password'"
                                name="password"
                                autocomplete="new-password"
                                class="input"
                            />
                            <p v-if="passwordForm.errors.password" class="error-text">
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="label">Confirm password</label>
                            <input
                                id="password_confirmation"
                                v-model="passwordForm.password_confirmation"
                                :type="showPasswords ? 'text' : 'password'"
                                name="password_confirmation"
                                autocomplete="new-password"
                                class="input"
                            />
                        </div>
                    </div>

                    <p class="text-xs leading-5 text-navy-400">
                        Use at least eight characters with letters and numbers.
                    </p>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary" :disabled="passwordForm.processing">
                            {{ passwordForm.processing ? 'Updating…' : 'Update password' }}
                            <ArrowRight v-if="!passwordForm.processing" class="size-4" />
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppShell>
</template>
