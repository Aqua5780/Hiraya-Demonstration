<script setup lang="ts">
import { X } from '@lucide/vue';
import { onBeforeUnmount, onMounted, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show: boolean;
        title: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
        closeable?: boolean;
    }>(),
    {
        maxWidth: 'lg',
        closeable: true,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const widths = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-2xl',
};

function close(): void {
    if (props.closeable) {
        emit('close');
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && props.show) {
        close();
    }
}

watch(
    () => props.show,
    (show) => {
        document.body.classList.toggle('overflow-hidden', show);
    },
);

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[80] flex items-end justify-center p-0 sm:items-center sm:p-6">
                <div class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" @click="close" />

                <div
                    class="relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-lg border border-navy-100 bg-white shadow-lift sm:rounded-lg"
                    :class="widths[maxWidth]"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-navy-100 px-5 py-4">
                        <h2 class="font-display text-xl font-bold text-navy-900">{{ title }}</h2>
                        <button
                            v-if="closeable"
                            type="button"
                            class="rounded p-1.5 text-navy-400 transition hover:bg-navy-50 hover:text-navy-700"
                            aria-label="Close dialog"
                            @click="close"
                        >
                            <X class="size-4" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="border-t border-navy-100 bg-cream-50 px-5 py-4">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
