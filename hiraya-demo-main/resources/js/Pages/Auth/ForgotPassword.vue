<script setup lang="ts">
import { ArrowLeft, ArrowRight, Mail } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../Components/AuthLayout.vue';

const form = useForm({
    email: '',
});

function submit(): void {
    form.post('/forgot-password');
}
</script>

<template>
    <AuthLayout
        title="Reset password"
        subtitle="Enter the email address on your account."
    >
        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="email" class="label">Email address</label>
                <div class="relative">
                    <Mail class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        inputmode="email"
                        class="input pl-10"
                        placeholder="you@school.edu.ph"
                        autofocus
                    />
                </div>
                <p v-if="form.errors.email" class="error-text">{{ form.errors.email }}</p>
            </div>

            <div class="rounded-md border border-navy-100 bg-navy-50/60 p-3.5">
                <p class="text-xs leading-5 text-navy-500">
                    The verification code expires after ten minutes and can only be used once.
                </p>
            </div>

            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                <span>{{ form.processing ? 'Sending…' : 'Send code' }}</span>
                <ArrowRight v-if="!form.processing" class="size-4" />
            </button>

            <Link href="/login" class="flex items-center justify-center gap-2 text-xs font-semibold text-navy-500 hover:text-navy-800">
                <ArrowLeft class="size-3.5" />
                Back to sign in
            </Link>
        </form>
    </AuthLayout>
</template>
