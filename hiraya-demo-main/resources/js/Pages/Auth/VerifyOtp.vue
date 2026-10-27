<script setup lang="ts">
import { ArrowLeft, ArrowRight, KeyRound } from '@lucide/vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthLayout from '../../Components/AuthLayout.vue';
import type { SharedPageProps } from '../../types';

const props = defineProps<{
    email: string;
}>();

const page = usePage<SharedPageProps>();
const demoOtp = computed(() => page.props.flash.demo_otp);

const form = useForm({
    otp: '',
});

function submit(): void {
    form.post('/forgot-password/verify');
}
</script>

<template>
    <AuthLayout
        title="Verify email"
        :subtitle="`Enter the six-digit code sent to ${props.email}.`"
    >
        <div v-if="demoOtp" class="mb-5 rounded-md border border-gold-200 bg-gold-50 p-4">
            <p class="text-[0.68rem] font-bold uppercase tracking-wide text-gold-800">Demo mode</p>
            <div class="mt-1 flex items-baseline gap-2">
                <p class="text-xs text-gold-800">Your verification code is</p>
                <p class="font-mono text-lg font-bold tracking-[0.25em] text-navy-900">{{ demoOtp }}</p>
            </div>
            <p class="mt-1 text-[0.68rem] leading-5 text-gold-700">
                In production this code is sent to the registered email address only.
            </p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="otp" class="label">Verification code</label>
                <div class="relative">
                    <KeyRound class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                    <input
                        id="otp"
                        v-model="form.otp"
                        type="text"
                        name="otp"
                        inputmode="numeric"
                        maxlength="6"
                        autocomplete="one-time-code"
                        class="input py-3 pl-10 text-center font-mono text-xl tracking-[0.4em]"
                        placeholder="000000"
                        autofocus
                    />
                </div>
                <p v-if="form.errors.otp" class="error-text">{{ form.errors.otp }}</p>
            </div>

            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                <span>{{ form.processing ? 'Verifying…' : 'Verify code' }}</span>
                <ArrowRight v-if="!form.processing" class="size-4" />
            </button>

            <div class="flex flex-col items-center gap-3 text-xs">
                <Link href="/forgot-password" class="font-semibold text-navy-600 hover:text-navy-900">
                    Didn’t receive the code? Request a new one
                </Link>
                <Link href="/login" class="flex items-center gap-2 font-semibold text-navy-400 hover:text-navy-700">
                    <ArrowLeft class="size-3.5" />
                    Back to sign in
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
