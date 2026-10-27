<script setup lang="ts">
import { ArrowRight, CircleAlert, CircleCheck, Eye, EyeOff, LockKeyhole } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthLayout from '../../Components/AuthLayout.vue';

interface InvitedUser {
    name: string;
    email: string;
}

const props = defineProps<{
    token: string;
    valid: boolean;
    invitedUser: InvitedUser | null;
}>();

const form = useForm({
    token: props.token,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

function submit(): void {
    form.post('/activate', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthLayout
        title="Activate account"
        subtitle="Choose a password for your account."
    >
        <div v-if="!valid" class="space-y-5">
            <div class="rounded-md border border-red-200 bg-red-50 p-4">
                <div class="flex gap-3">
                    <CircleAlert class="mt-0.5 size-5 shrink-0 text-red-600" />
                    <div>
                        <p class="text-sm font-semibold text-red-800">This activation link is invalid</p>
                        <p class="mt-1 text-xs leading-5 text-red-700">
                            The link may have expired or already been used. Ask the administrator to send a new invitation.
                        </p>
                    </div>
                </div>
            </div>

            <Link href="/login" class="btn btn-primary w-full">
                Go to sign in
                <ArrowRight class="size-4" />
            </Link>
        </div>

        <form v-else class="space-y-5" @submit.prevent="submit">
            <div class="rounded-md border border-laurel-200 bg-laurel-50 p-4">
                <div class="flex gap-3">
                    <CircleCheck class="mt-0.5 size-5 shrink-0 text-laurel-600" />
                    <div>
                        <p class="text-sm font-semibold text-laurel-800">{{ invitedUser?.name }}</p>
                        <p class="mt-0.5 text-xs text-laurel-700">{{ invitedUser?.email }}</p>
                    </div>
                </div>
            </div>

            <div>
                <label for="password" class="label">New password</label>
                <div class="relative">
                    <LockKeyhole class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        autocomplete="new-password"
                        class="input px-10"
                        placeholder="Choose a password"
                        autofocus
                    />
                    <button
                        type="button"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg p-2 text-navy-400 transition hover:bg-navy-50 hover:text-navy-700"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="size-4" />
                        <Eye v-else class="size-4" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="error-text">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="input"
                    placeholder="Repeat your password"
                />
            </div>

            <p v-if="form.errors.token" class="error-text">{{ form.errors.token }}</p>

            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                <span>{{ form.processing ? 'Activating…' : 'Activate account' }}</span>
                <ArrowRight v-if="!form.processing" class="size-4" />
            </button>
        </form>
    </AuthLayout>
</template>
