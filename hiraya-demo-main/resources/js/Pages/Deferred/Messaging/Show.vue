<script setup lang="ts">
import { ArrowLeft, Send } from '@lucide/vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { formatDateTime } from '../../../lib/format';
import type { SharedPageProps } from '../../../types';

interface MessageRow {
    id: number;
    body: string;
    created_at: string;
    sender?: { id: number; name: string };
}

const props = defineProps<{
    conversation: {
        id: number;
        subject: string;
        student?: { user?: { name: string } };
        staff?: { name: string };
        messages: MessageRow[];
    };
    canReply: boolean;
}>();

const page = usePage<SharedPageProps>();
const currentUserId = computed(() => page.props.auth.user?.id);

const form = useForm({ body: '' });

function submit(): void {
    form.post(`/messages/${props.conversation.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <Link href="/messages" class="btn btn-ghost btn-sm">
                <ArrowLeft class="size-4" /> Back to messages
            </Link>

            <PageHeader :title="conversation.subject" :subtitle="conversation.staff?.name ?? conversation.student?.user?.name" />

            <section class="card">
                <div class="max-h-[55vh] space-y-4 overflow-y-auto p-5">
                    <div
                        v-for="message in conversation.messages"
                        :key="message.id"
                        class="flex"
                        :class="message.sender?.id === currentUserId ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] rounded-lg px-4 py-3"
                            :class="message.sender?.id === currentUserId ? 'bg-navy-900 text-white' : 'bg-navy-50 text-navy-800'"
                        >
                            <p class="text-sm leading-6">{{ message.body }}</p>
                            <p class="mt-1 text-[0.65rem]" :class="message.sender?.id === currentUserId ? 'text-navy-300' : 'text-navy-400'">
                                {{ message.sender?.name }} · {{ formatDateTime(message.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <form v-if="canReply" class="flex gap-2 border-t border-navy-100 p-4" @submit.prevent="submit">
                    <input v-model="form.body" type="text" class="input flex-1" placeholder="Write a reply" />
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Send class="size-4" /> Send
                    </button>
                </form>
            </section>
        </div>
    </AppShell>
</template>
