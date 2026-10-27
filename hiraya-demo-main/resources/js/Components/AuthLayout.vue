<script setup lang="ts">
import ConfirmModal from './ConfirmModal.vue';
import FlashModal from './FlashModal.vue';
import Logo from './Logo.vue';

defineProps<{
    title: string;
    subtitle?: string;
}>();
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-cream-100">
        <div
            class="pointer-events-none absolute inset-0 lg:hidden"
            style="background: linear-gradient(180deg, #081428 0px, #0b1b36 48px, #12264c 60px, #7f8186 76px, #d5cab6 86px, #f5f1e6 92px, #faf7ef 96px, #faf7ef 100%);"
        />
        <div
            class="pointer-events-none absolute inset-0 hidden lg:block"
            style="background: linear-gradient(101deg, #081428 0%, #0b1b36 27%, #12264c 38%, #2b4169 46%, #858584 54%, #d5cab6 61%, #f5f1e6 66%, #faf7ef 100%);"
        />

        <div class="relative z-10 min-h-screen lg:grid lg:grid-cols-[1.08fr_0.92fr]">
            <aside class="hidden flex-col px-10 py-10 text-white lg:flex lg:px-14 lg:py-12">
                <Logo light size="lg" />

                <div class="flex flex-1 items-center">
                    <div class="max-w-[30rem] border-l border-gold-400/40 pl-6">
                        <h1 class="font-display text-4xl font-bold leading-[1.08] text-white xl:text-[2.85rem]">
                            Good Shepherd of Taguig Colleges
                        </h1>
                        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-gold-200">
                            Established 2000
                        </p>
                    </div>
                </div>

                <div class="max-w-md text-xs leading-6 text-navy-200/80">
                    <p class="font-display text-sm font-semibold text-white">Faith. Wisdom. Triumph.</p>
                    <p class="mt-2">Central Bicutan, Taguig City</p>
                    <p>(02) 8731-6269 · official@gstc.edu.ph</p>
                </div>
            </aside>

            <main class="flex min-h-screen flex-col justify-start px-5 pb-10 pt-0 sm:px-8 lg:items-center lg:justify-center lg:px-10 lg:py-10">
                <div class="w-full max-w-md">
                    <div class="mb-6 pt-6 lg:hidden">
                        <Logo light />
                    </div>

                    <div class="card p-6 shadow-lift sm:p-8">
                        <h2 class="font-display text-3xl font-bold text-navy-900">{{ title }}</h2>
                        <p v-if="subtitle" class="mt-2 text-sm leading-6 text-navy-500">{{ subtitle }}</p>

                        <div class="mt-7">
                            <slot />
                        </div>
                    </div>

                    <p class="mt-6 text-center text-xs text-navy-400">
                        © {{ new Date().getFullYear() }} Good Shepherd of Taguig Colleges, Inc.
                    </p>
                </div>
            </main>
        </div>

        <FlashModal />
        <ConfirmModal />
    </div>
</template>
