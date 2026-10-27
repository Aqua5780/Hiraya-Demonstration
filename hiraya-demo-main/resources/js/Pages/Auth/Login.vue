<script setup lang="ts">
import { ArrowRight, ChevronDown, Eye, EyeOff, LockKeyhole, Mail } from '@lucide/vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthLayout from '../../Components/AuthLayout.vue';

interface DemoAccount {
    role: string;
    email: string;
    password: string;
    name: string;
}

defineProps<{
    demoAccounts: DemoAccount[];
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const selectedDemo = ref<string | null>(null);

function submit(): void {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

function useDemo(account: DemoAccount): void {
    form.email = account.email;
    form.password = account.password;
    selectedDemo.value = account.email;
}
</script>

<template>
    <AuthLayout title="Login">
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
                        autocomplete="username"
                        inputmode="email"
                        class="input pl-10"
                        placeholder="you@school.edu.ph"
                        autofocus
                    />
                </div>
                <p v-if="form.errors.email" class="error-text">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="label">Password</label>
                    <Link href="/forgot-password" class="text-xs font-semibold text-navy-600 hover:text-navy-900">
                        Forgot password?
                    </Link>
                </div>
                <div class="relative">
                    <LockKeyhole class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        autocomplete="current-password"
                        class="input pl-10 pr-11"
                        placeholder="Enter your password"
                    />
                    <button
                        type="button"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded p-2 text-navy-400 transition hover:bg-navy-50 hover:text-navy-700"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" class="size-4" />
                        <Eye v-else class="size-4" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="error-text">{{ form.errors.password }}</p>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-navy-600">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="size-4 rounded border-navy-300 text-gold-500 focus:ring-gold-400"
                />
                Remember me
            </label>

            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                <span>{{ form.processing ? 'Logging in…' : 'Login' }}</span>
                <ArrowRight v-if="!form.processing" class="size-4" />
            </button>
        </form>

        <details v-if="demoAccounts.length" class="group mt-7 border-t border-navy-100 pt-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <span class="text-xs font-bold uppercase tracking-wide text-navy-600">Demo accounts</span>
                <span class="flex items-center gap-2">
                    <span class="badge badge-gold">Local only</span>
                    <ChevronDown class="size-4 text-navy-300 transition-transform group-open:rotate-180" />
                </span>
            </summary>

            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <button
                    v-for="account in demoAccounts"
                    :key="account.email"
                    type="button"
                    class="rounded-md border p-3 text-left transition"
                    :class="
                        selectedDemo === account.email
                            ? 'border-gold-400 bg-gold-50'
                            : 'border-navy-100 bg-white hover:border-navy-200 hover:bg-navy-50'
                    "
                    @click="useDemo(account)"
                >
                    <p class="text-xs font-bold text-navy-800">{{ account.role }}</p>
                    <p class="mt-0.5 truncate text-[0.68rem] text-navy-400">{{ account.email }}</p>
                </button>
            </div>

            <p class="mt-2 text-[0.68rem] leading-5 text-navy-400">
                All demo accounts use the password <span class="font-mono font-semibold text-navy-600">Password123!</span>.
            </p>
        </details>
    </AuthLayout>
</template>
