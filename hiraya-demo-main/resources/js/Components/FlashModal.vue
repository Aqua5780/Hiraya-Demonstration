<script setup lang="ts">
import { CircleAlert, CircleCheck, Info } from '@lucide/vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { Component } from 'vue';
import Modal from './Modal.vue';
import type { SharedPageProps } from '../types';
import type { ToastPayload, ToastType } from '../lib/toast';

const page = usePage<SharedPageProps>();
const queue = ref<ToastPayload[]>([]);
const current = computed(() => queue.value[0] ?? null);
let lastFlashSignature = '';

const meta: Record<ToastType, { title: string; icon: Component; iconClass: string }> = {
    success: { title: 'Saved', icon: CircleCheck, iconClass: 'text-laurel-600' },
    error: { title: 'Action needed', icon: CircleAlert, iconClass: 'text-red-600' },
    info: { title: 'Notice', icon: Info, iconClass: 'text-navy-600' },
};

function enqueue(message: string | null | undefined, type: ToastType): void {
    if (!message) {
        return;
    }

    queue.value.push({
        id: Date.now() + Math.floor(Math.random() * 1000),
        message,
        type,
    });
}

function dismiss(): void {
    queue.value = queue.value.slice(1);
}

function handleNotice(event: Event): void {
    const detail = (event as CustomEvent<ToastPayload>).detail;
    enqueue(detail.message, detail.type);
}

function consumeFlash(): void {
    const flash = page.props.flash ?? {};
    const signature = `${flash.success ?? ''}|${flash.error ?? ''}|${flash.info ?? ''}`;

    if (!signature || signature === lastFlashSignature) {
        return;
    }

    lastFlashSignature = signature;
    enqueue(flash.success, 'success');
    enqueue(flash.error, 'error');
    enqueue(flash.info, 'info');
}

watch(
    () => page.props.flash,
    () => consumeFlash(),
    { deep: true },
);

onMounted(() => {
    consumeFlash();
    window.addEventListener('hiraya-toast', handleNotice);
});

onBeforeUnmount(() => {
    window.removeEventListener('hiraya-toast', handleNotice);
});
</script>

<template>
    <Modal
        :show="current !== null"
        :title="current ? meta[current.type].title : 'Notice'"
        max-width="md"
        @close="dismiss"
    >
        <div v-if="current" class="flex gap-3 p-5">
            <component
                :is="meta[current.type].icon"
                class="mt-0.5 size-5 shrink-0"
                :class="meta[current.type].iconClass"
            />
            <p class="text-sm leading-6 text-navy-700">{{ current.message }}</p>
        </div>

        <template #footer>
            <div class="flex justify-end">
                <button type="button" class="btn btn-primary" @click="dismiss">OK</button>
            </div>
        </template>
    </Modal>
</template>
