<script setup lang="ts">
import { ArrowLeft, SearchX, ServerCrash, ShieldAlert } from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../Components/AppShell.vue';
import Logo from '../Components/Logo.vue';
import type { SharedPageProps } from '../types';

const props = defineProps<{
    status: number;
}>();

const page = usePage<SharedPageProps>();
const authenticated = computed(() => Boolean(page.props.auth.user));

const details = computed(() => {
    switch (props.status) {
        case 403:
            return {
                icon: ShieldAlert,
                title: 'Access denied',
                message: 'Your account does not have permission to open this page.',
            };
        case 404:
            return {
                icon: SearchX,
                title: 'Page not found',
                message: 'The page you are looking for does not exist or has been moved.',
            };
        case 419:
            return {
                icon: ShieldAlert,
                title: 'Session expired',
                message: 'Your session has expired. Refresh the page and try again.',
            };
        case 503:
            return {
                icon: ServerCrash,
                title: 'Temporarily unavailable',
                message: 'The system is undergoing maintenance. Please try again shortly.',
            };
        default:
            return {
                icon: ServerCrash,
                title: 'Something went wrong',
                message: 'The server could not complete this request. Please try again.',
            };
    }
});

const homeHref = computed(() => {
    if (!authenticated.value) {
        return '/login';
    }

    return {
        admin: '/admin',
        registrar: '/registrar',
        faculty: '/faculty',
        finance: '/finance',
        student: '/student',
    }[page.props.auth.user?.role ?? 'student'];
});
</script>

<template>
    <div>
        <AppShell v-if="authenticated">
            <div class="flex min-h-[55vh] items-center justify-center">
                <div class="max-w-md text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-lg bg-navy-50 text-navy-500">
                        <component :is="details.icon" class="size-6" />
                    </div>
                    <p class="mt-5 text-xs font-bold uppercase tracking-[0.18em] text-navy-400">Error {{ status }}</p>
                    <h1 class="font-display mt-2 text-3xl font-bold text-navy-900">{{ details.title }}</h1>
                    <p class="mt-2 text-sm leading-6 text-navy-500">{{ details.message }}</p>
                    <Link href="/" class="btn btn-primary mt-6">
                        <ArrowLeft class="size-4" /> Back to dashboard
                    </Link>
                </div>
            </div>
        </AppShell>

        <div v-else class="flex min-h-screen items-center justify-center bg-cream-100 px-5">
            <div class="w-full max-w-md text-center">
                <div class="flex justify-center">
                    <Logo size="lg" />
                </div>
                <div class="card mt-8 p-8">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-lg bg-navy-50 text-navy-500">
                        <component :is="details.icon" class="size-5" />
                    </div>
                    <p class="mt-4 text-xs font-bold uppercase tracking-[0.18em] text-navy-400">Error {{ status }}</p>
                    <h1 class="font-display mt-2 text-2xl font-bold text-navy-900">{{ details.title }}</h1>
                    <p class="mt-2 text-sm leading-6 text-navy-500">{{ details.message }}</p>
                    <Link :href="homeHref" class="btn btn-primary mt-6">
                        <ArrowLeft class="size-4" /> Back to login
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
