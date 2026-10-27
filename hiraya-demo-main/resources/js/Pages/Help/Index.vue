<script setup lang="ts">
import {
    BookOpen,
    ChevronDown,
    CircleQuestionMark,
    CreditCard,
    LifeBuoy,
    Search,
    ShieldCheck,
} from '@lucide/vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../Components/AppShell.vue';
import Badge from '../../Components/Badge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';

interface FaqItem {
    id: number;
    question: string;
    answer: string;
    role_visibility: string | null;
}

interface Category {
    id: number;
    name: string;
    slug: string;
    faqs: FaqItem[];
}

const props = defineProps<{
    categories: Category[];
    search: string;
    role: string | null;
}>();

const query = ref(props.search);
const openFaq = ref<number | null>(props.categories[0]?.faqs[0]?.id ?? null);

const totalFaqs = computed(() => props.categories.reduce((total, category) => total + category.faqs.length, 0));

const guides: Record<string, string[]> = {
    student: [
        'Sign in with your official student account and review your dashboard.',
        'Check your enrollment status, class schedule, and balance.',
        'Use the search box for questions about grades, payments, or account access.',
    ],
    faculty: [
        'Review your assigned classes and today’s schedule on the dashboard.',
        'Open a class list before encoding midterm and final grades.',
        'Post class announcements only to the sections you handle.',
    ],
    registrar: [
        'Search for a student by student number or name.',
        'Review the student’s program, year level, section, and balance before editing.',
        'Use the CSV template when importing multiple student records.',
    ],
    finance: [
        'Review pending payment submissions before posting them.',
        'Match the reference number and proof of payment to the submitted amount.',
        'Open the student ledger to confirm the remaining balance after posting.',
    ],
    admin: [
        'Keep one academic year active at a time.',
        'Prepare programs, year levels, sections, and subjects before opening classes.',
        'Review the audit log after sensitive account or record changes.',
    ],
};

const quickGuide = computed(() => guides[props.role ?? 'student'] ?? guides.student);

function submitSearch(): void {
    const value = query.value.trim();

    router.get('/help', value ? { q: value } : {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearSearch(): void {
    query.value = '';
    submitSearch();
}

function toggleFaq(id: number): void {
    openFaq.value = openFaq.value === id ? null : id;
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader
                title="Help"
            >
                <template #actions>
                    <Badge tone="navy">{{ totalFaqs }} answer{{ totalFaqs === 1 ? '' : 's' }}</Badge>
                </template>
            </PageHeader>

            <div class="grid gap-6 xl:grid-cols-[0.7fr_1.3fr]">
                <aside class="space-y-5">
                    <section class="card p-5">
                        <form @submit.prevent="submitSearch">
                            <label for="help-search" class="label">Search help</label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-navy-300" />
                                <input
                                    id="help-search"
                                    v-model="query"
                                    type="search"
                                    class="input pl-10"
                                    placeholder="Search questions and answers"
                                />
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm flex-1">Search</button>
                                <button v-if="search" type="button" class="btn btn-secondary btn-sm" @click="clearSearch">
                                    Clear
                                </button>
                            </div>
                        </form>

                        <div class="mt-5 border-t border-navy-100 pt-4">
                            <p class="eyebrow">Categories</p>
                            <nav class="mt-2 space-y-1">
                                <a
                                    v-for="category in categories"
                                    :key="category.id"
                                    :href="`#category-${category.slug}`"
                                    class="flex items-center justify-between rounded-lg px-2.5 py-2 text-sm text-navy-600 transition hover:bg-navy-50 hover:text-navy-900"
                                >
                                    <span>{{ category.name }}</span>
                                    <span class="text-xs text-navy-300">{{ category.faqs.length }}</span>
                                </a>
                            </nav>
                        </div>
                    </section>

                    <section class="card overflow-hidden">
                        <div class="bg-navy-950 p-5 text-white">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-gold-400/15 text-gold-300">
                                <LifeBuoy class="size-5" />
                            </div>
                            <p class="font-display mt-3 text-lg font-bold">Quick start</p>
                            <p class="mt-1 text-xs leading-5 text-navy-300">Three steps for your role</p>
                        </div>
                        <ol class="space-y-3 p-5">
                            <li v-for="(step, index) in quickGuide" :key="step" class="flex gap-3">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-gold-100 text-[0.68rem] font-bold text-gold-800">
                                    {{ index + 1 }}
                                </span>
                                <p class="text-xs leading-5 text-navy-500">{{ step }}</p>
                            </li>
                        </ol>
                    </section>

                    <section class="card p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-laurel-100 text-laurel-700">
                                <ShieldCheck class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-navy-800">Account security</p>
                                <p class="mt-1 text-xs leading-5 text-navy-400">
                                    Never share your password. After three failed sign-in attempts, your account is
                                    locked for five minutes. Use the forgot-password flow if you need to recover access.
                                </p>
                            </div>
                        </div>
                    </section>
                </aside>

                <section class="space-y-5">
                    <div v-if="search" class="flex flex-wrap items-center gap-2 rounded-md border border-gold-200 bg-gold-50 px-4 py-3 text-xs text-gold-900">
                        <Search class="size-3.5" />
                        <span>Showing results for</span>
                        <span class="font-semibold">“{{ search }}”</span>
                        <span class="text-gold-700">· {{ totalFaqs }} result(s)</span>
                    </div>

                    <div v-if="categories.length === 0" class="card">
                        <EmptyState
                            title="No help articles found"
                            description="Try a different keyword, or browse all categories after clearing the search."
                        >
                            <template #icon><CircleQuestionMark class="size-6" /></template>
                            <template #action>
                                <button type="button" class="btn btn-secondary btn-sm" @click="clearSearch">
                                    Clear search
                                </button>
                            </template>
                        </EmptyState>
                    </div>

                    <section
                        v-for="category in categories"
                        :id="`category-${category.slug}`"
                        :key="category.id"
                        class="card scroll-mt-24"
                    >
                        <div class="card-header">
                            <div class="flex items-center gap-2.5">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                                    <BookOpen class="size-4" />
                                </span>
                                <h2 class="card-title">{{ category.name }}</h2>
                            </div>
                            <span class="text-xs text-navy-400">{{ category.faqs.length }} question{{ category.faqs.length === 1 ? '' : 's' }}</span>
                        </div>

                        <div class="divide-y divide-navy-50">
                            <div v-for="faq in category.faqs" :key="faq.id">
                                <button
                                    type="button"
                                    class="flex w-full items-start gap-3 px-5 py-4 text-left transition hover:bg-cream-50"
                                    :aria-expanded="openFaq === faq.id"
                                    @click="toggleFaq(faq.id)"
                                >
                                    <CircleQuestionMark class="mt-0.5 size-4 shrink-0 text-gold-600" />
                                    <span class="flex-1 text-sm font-semibold text-navy-800">{{ faq.question }}</span>
                                    <ChevronDown
                                        class="mt-0.5 size-4 shrink-0 text-navy-300 transition-transform"
                                        :class="{ 'rotate-180': openFaq === faq.id }"
                                    />
                                </button>
                                <div v-if="openFaq === faq.id" class="px-5 pb-5 pl-12">
                                    <p class="whitespace-pre-line text-xs leading-6 text-navy-500">{{ faq.answer }}</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="rounded-lg border border-navy-100 bg-white p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-navy-50 text-navy-600">
                                <CreditCard class="size-4" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-navy-800">Still need help?</p>
                                <p class="mt-1 text-xs leading-5 text-navy-400">
                                    Contact the registrar for student records and enrollment concerns, the finance office
                                    for payments and balances, or the administrator for account access and role changes.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppShell>
</template>
