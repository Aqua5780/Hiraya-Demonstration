<script setup lang="ts">
import { ArrowRight, Check, Eye, EyeOff, LockKeyhole } from '@lucide/vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthLayout from '../../Components/AuthLayout.vue';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

function submit(): void {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <AuthLayout
        title="New password"
        subtitle="Use at least eight characters with letters and numbers."
    >
        <form class="space-y-5" @submit.prevent="submit">
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
                        placeholder="Enter a new password"
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
                <label for="password_confirmation" class="label">Confirm new password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    :type="showPassword ? 'text' : 'password'"
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="input"
                    placeholder="Repeat the new password"
                />
            </div>

            <ul class="grid gap-2 rounded-md border border-navy-100 bg-navy-50/50 p-4 text-xs text-navy-500">
                <li class="flex items-center gap-2">
                    <Check class="size-3.5 text-laurel-600" />
                    At least 8 characters
                </li>
                <li class="flex items-center gap-2">
                    <Check class="size-3.5 text-laurel-600" />
                    Contains letters and numbers
                </li>
            </ul>

            <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                <span>{{ form.processing ? 'Saving password…' : 'Save new password' }}</span>
                <ArrowRight v-if="!form.processing" class="size-4" />
            </button>
        </form>
    </AuthLayout>
</template>
