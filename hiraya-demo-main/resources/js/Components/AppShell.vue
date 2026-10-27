<script setup lang="ts">
import {
    BadgeCheck,
    BookOpen,
    BookOpenCheck,
    CalendarDays,
    CalendarRange,
    CircleQuestionMark,
    ClipboardList,
    Database,
    GraduationCap,
    LayoutDashboard,
    LogOut,
    Megaphone,
    Menu,
    ReceiptText,
    ScrollText,
    Settings,
    Upload,
    UserPlus,
    Users,
    Wallet,
    X,
} from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { Component } from 'vue';
import ConfirmModal from './ConfirmModal.vue';
import FlashModal from './FlashModal.vue';
import Logo from './Logo.vue';
import { formatDate } from '../lib/format';
import type { SharedPageProps } from '../types';

interface NavItem {
    label: string;
    icon: Component;
    href?: string;
    checkpoint?: string;
}

const page = usePage<SharedPageProps>();
const mobileOpen = ref(false);

const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role ?? 'student');
const currentPath = computed(() => page.url.split('?')[0]);
const activeYear = computed(() => page.props.activeYear);

const navigation: Record<string, NavItem[]> = {
    admin: [
        { label: 'Dashboard', href: '/admin', icon: LayoutDashboard },
        { label: 'Academic Years', href: '/admin/academic-years', icon: CalendarDays },
        { label: 'Scheduling & Offerings', href: '/admin/class-offerings', icon: CalendarRange },
        { label: 'Announcements', href: '/admin/announcements', icon: Megaphone },
        { label: 'Accounts & Activation', href: '/admin/accounts', icon: UserPlus },
        { label: 'Master Data', href: '/admin/master-data', icon: Database },
        { label: 'Audit Logs', href: '/admin/audit-logs', icon: ScrollText },
        { label: 'Help & FAQ', href: '/help', icon: CircleQuestionMark },
    ],
    registrar: [
        { label: 'Dashboard', href: '/registrar', icon: LayoutDashboard },
        { label: 'Student Records', href: '/registrar/students', icon: Users },
        { label: 'Import Records', href: '/registrar/import', icon: Upload },
        { label: 'Help & FAQ', href: '/help', icon: CircleQuestionMark },
    ],
    faculty: [
        { label: 'Dashboard', href: '/faculty', icon: LayoutDashboard },
        { label: 'Class Schedule', href: '/faculty/schedule', icon: CalendarDays },
        { label: 'Class List', href: '/faculty/classes', icon: ClipboardList },
        { label: 'Grade Sheet', href: '/faculty/grades', icon: BookOpenCheck },
        { label: 'Class Announcements', href: '/faculty/announcements', icon: Megaphone },
        { label: 'Help & FAQ', href: '/help', icon: CircleQuestionMark },
    ],
    student: [
        { label: 'Dashboard', href: '/student', icon: LayoutDashboard },
        { label: 'Enrollment', href: '/student/enrollment', icon: ClipboardList },
        { label: 'My Classes', href: '/student/classes', icon: Users },
        { label: 'My Schedule', href: '/student/schedule', icon: CalendarDays },
        { label: 'My Grades', href: '/student/grades', icon: GraduationCap },
        { label: 'Announcements', href: '/student/announcements', icon: Megaphone },
        { label: 'Payments', href: '/student/payments', icon: Wallet },
        { label: 'Help & FAQ', href: '/help', icon: CircleQuestionMark },
    ],
    finance: [
        { label: 'Dashboard', href: '/finance', icon: LayoutDashboard },
        { label: 'Payment Verification', href: '/finance/payments', icon: BadgeCheck },
        { label: 'Post Payment', href: '/finance/payments/create', icon: ReceiptText },
        { label: 'Student Ledger', href: '/finance/accounts', icon: BookOpen },
        { label: 'Help & FAQ', href: '/help', icon: CircleQuestionMark },
    ],
};

const navItems = computed(() => navigation[role.value] ?? navigation.student);
const initials = computed(() => user.value?.initials ?? 'H');

const activeNavHref = computed(() => {
    return navItems.value
        .filter((item) => item.href && (currentPath.value === item.href || currentPath.value.startsWith(`${item.href}/`)))
        .sort((a, b) => (b.href?.length ?? 0) - (a.href?.length ?? 0))[0]?.href ?? null;
});

const enrollmentLabel = computed(() => {
    if (!activeYear.value) {
        return 'No active academic year';
    }

    return `Enrollment window: ${formatDate(activeYear.value.enrollment_start)} – ${formatDate(activeYear.value.enrollment_end)}`;
});

function isActive(href?: string): boolean {
    if (!href) {
        return false;
    }

    return href === activeNavHref.value;
}

watch(mobileOpen, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
});

onBeforeUnmount(() => {
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <div class="min-h-screen bg-cream-100">
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-40 bg-navy-950/60 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
            @click="mobileOpen = false"
        />

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 transform flex-col bg-navy-950 text-white shadow-2xl transition-transform duration-200 ease-out lg:translate-x-0"
            :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-gold-300 via-gold-400 to-laurel-400" />

            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <Logo light />
                <button
                    type="button"
                    class="rounded-lg p-2 text-navy-300 hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="Close navigation"
                    @click="mobileOpen = false"
                >
                    <X class="size-5" />
                </button>
            </div>

            <div class="px-5 pb-2 pt-5">
                <p class="truncate text-sm font-semibold text-white">{{ user?.name }}</p>
                <p class="truncate text-xs text-navy-400">{{ user?.email }}</p>
            </div>

            <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-3 pb-4" aria-label="Primary navigation">
                <template v-for="item in navItems" :key="item.label">
                    <Link
                        v-if="item.href"
                        :href="item.href"
                        class="nav-link"
                        :class="{ 'nav-link-active': isActive(item.href) }"
                        @click="mobileOpen = false"
                    >
                        <component :is="item.icon" class="size-[1.15rem] shrink-0" />
                        <span class="flex-1">{{ item.label }}</span>
                    </Link>
                    <div v-else class="nav-link nav-link-disabled" :title="`Planned for ${item.checkpoint}`">
                        <component :is="item.icon" class="size-[1.15rem] shrink-0" />
                        <span class="flex-1">{{ item.label }}</span>
                        <span class="rounded-md bg-white/5 px-1.5 py-0.5 text-[0.58rem] font-bold tracking-wide text-navy-300">
                            {{ item.checkpoint }}
                        </span>
                    </div>
                </template>
            </nav>

            <div class="border-t border-white/10 p-4">
                <div class="rounded-md border border-white/10 bg-white/[0.04] p-3.5">
                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-gold-300">Active academic year</p>
                    <p class="mt-1.5 font-display text-base font-bold text-white">
                        {{ activeYear?.name ?? 'Not configured' }}
                    </p>
                    <p class="mt-1 text-[0.68rem] leading-5 text-navy-400">{{ enrollmentLabel }}</p>
                </div>

                <Link
                    href="/settings"
                    class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-navy-300 transition hover:bg-white/10 hover:text-white"
                    :class="{ 'bg-white/10 text-white': currentPath === '/settings' }"
                >
                    <Settings class="size-[1.15rem]" />
                    Account settings
                </Link>

                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="mt-1 flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-navy-300 transition hover:bg-red-500/10 hover:text-red-300"
                >
                    <LogOut class="size-[1.15rem]" />
                    Sign out
                </Link>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="fixed inset-x-0 top-0 z-30 border-b border-navy-100 bg-cream-100/90 backdrop-blur lg:pl-72">
                <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-navy-600 hover:bg-white lg:hidden"
                            aria-label="Open navigation"
                            @click="mobileOpen = true"
                        >
                            <Menu class="size-5" />
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-navy-400">
                                Good Shepherd of Taguig Colleges, Inc.
                            </p>
                            <p class="truncate text-sm font-bold text-navy-900">
                                {{ activeYear?.name ?? 'Hiraya' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <span
                            v-if="activeYear"
                            class="hidden items-center gap-1.5 rounded-md border border-gold-200 bg-gold-50 px-3 py-1.5 text-[0.68rem] font-semibold text-gold-800 sm:inline-flex"
                        >
                            {{ activeYear.name }}
                        </span>

                        <Link
                            href="/help"
                            class="rounded-md p-2 text-navy-500 transition hover:bg-white hover:text-navy-800"
                            aria-label="Help and frequently asked questions"
                        >
                            <CircleQuestionMark class="size-5" />
                        </Link>

                        <Link
                            href="/settings"
                            class="rounded-md p-2 text-navy-500 transition hover:bg-white hover:text-navy-800"
                            aria-label="Account settings"
                        >
                            <Settings class="size-5" />
                        </Link>

                        <div class="hidden items-center gap-2.5 border-l border-navy-100 pl-3 md:flex">
                            <div class="flex size-9 items-center justify-center rounded-full bg-navy-900 text-xs font-bold text-gold-300">
                                {{ initials }}
                            </div>
                            <div class="max-w-[10rem]">
                                <Link href="/settings" class="block truncate text-xs font-bold text-navy-900 hover:text-navy-700">
                                    {{ user?.name }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-h-screen pt-16">
                <div class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    <slot />
                </div>

                <footer class="mx-auto flex w-full max-w-7xl flex-col gap-1 border-t border-navy-100 px-4 py-5 text-[0.68rem] text-navy-400 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <p>Hiraya · Good Shepherd of Taguig Colleges, Inc.</p>
                    <p class="font-display text-navy-500">Faith. Wisdom. Triumph.</p>
                </footer>
            </main>
        </div>

        <FlashModal />
        <ConfirmModal />
    </div>
</template>
