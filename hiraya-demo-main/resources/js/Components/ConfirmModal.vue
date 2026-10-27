<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Modal from './Modal.vue';
import type { ConfirmDetail } from '../lib/confirm';

const show = ref(false);
const options = ref<ConfirmDetail | null>(null);
const input = ref('');

function handle(event: Event): void {
    const detail = (event as CustomEvent<ConfirmDetail>).detail;

    options.value = detail;
    input.value = detail.inputValue ?? '';
    show.value = true;
}

function cancel(): void {
    show.value = false;
    options.value = null;
    input.value = '';
}

function confirm(): void {
    if (! options.value) {
        return;
    }

    if (options.value.inputRequired && input.value.trim() === '') {
        return;
    }

    const callback = options.value.onConfirm;
    const value = options.value.inputLabel ? input.value.trim() : undefined;

    cancel();
    callback(value);
}

onMounted(() => window.addEventListener('hiraya-confirm', handle));

onBeforeUnmount(() => window.removeEventListener('hiraya-confirm', handle));
</script>

<template>
    <Modal
        :show="show"
        :title="options?.title ?? 'Confirm action'"
        max-width="md"
        @close="cancel"
    >
        <div class="space-y-4 p-5">
            <p class="text-sm leading-6 text-navy-600">{{ options?.message }}</p>

            <div v-if="options?.inputLabel">
                <label for="confirm-input" class="label">{{ options.inputLabel }}</label>
                <textarea
                    id="confirm-input"
                    v-model="input"
                    rows="3"
                    class="input"
                    :placeholder="options.inputPlaceholder"
                />
                <p v-if="options.inputRequired && input.trim() === ''" class="error-text">
                    This field is required.
                </p>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="cancel">
                    {{ options?.cancelLabel ?? 'Cancel' }}
                </button>
                <button
                    type="button"
                    class="btn"
                    :class="options?.tone === 'danger' ? 'btn-danger' : 'btn-primary'"
                    :disabled="options?.inputRequired && input.trim() === ''"
                    @click="confirm"
                >
                    {{ options?.confirmLabel ?? 'Confirm' }}
                </button>
            </div>
        </template>
    </Modal>
</template>
