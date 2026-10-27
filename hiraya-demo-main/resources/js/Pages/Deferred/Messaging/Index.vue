<script setup lang="ts">
import { Inbox, MessageSquare, Send } from '@lucide/vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { formatDateTime } from '../../../lib/format';
import type { SharedPageProps } from '../../../types';

interface ConversationRow {
    id: number;
    subject: string;
    last_message_at: string | null;
    staff?: { name: string };
    student?: { student_number: string; first_name: string; last_name: string };
}

interface StaffOption {
    id: number;
    name: string;
    role?: { name: string };
}

const props = defineProps<{
    conversations: ConversationRow[];
    staff: StaffOption[];
}>();

const page = usePage<SharedPageProps>();
const isStudent = computed(() => page.props.auth.user?.role === 'student');

const form = useForm({
    staff_id: '',
    subject: '',
    body: '',
});

function submit(): void {
    form.post('/messages', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Messages" />

            <div class="grid gap-6 xl:grid-cols-[1fr_1.1fr]">
                <section v-if="isStudent" class="card">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <Send class="size-4 text-navy-500" />
                            <h2 class="card-title">New message</h2>
                        </div>
                    </div>
                    <form class="space-y-4 p-5" @submit.prevent="submit">
                        <div>
                            <label for="message-staff" class="label">Send to</label>
                            <select id="message-staff" v-model="form.staff_id" class="input">
                                <option value="">Select a recipient</option>
                                <option v-for="member in props.staff" :key="member.id" :value="member.id">
                                    {{ member.name }} — {{ member.role?.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.staff_id" class="error-text">{{ form.errors.staff_id }}</p>
                        </div>
                        <div>
                            <label for="message-subject" class="label">Subject</label>
                            <input id="message-subject" v-model="form.subject" type="text" class="input" />
                            <p v-if="form.errors.subject" class="error-text">{{ form.errors.subject }}</p>
                        </div>
                        <div>
                            <label for="message-body" class="label">Message</label>
                            <textarea id="message-body" v-model="form.body" rows="4" class="input" />
                            <p v-if="form.errors.body" class="error-text">{{ form.errors.body }}</p>
                        </div>
                        <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
                            {{ form.processing ? 'Sending…' : 'Send message' }}
                        </button>
                    </form>
                </section>

                <section class="card" :class="{ 'xl:col-span-2': !isStudent }">
                    <div class="card-header">
                        <div class="flex items-center gap-2.5">
                            <Inbox class="size-4 text-navy-500" />
                            <h2 class="card-title">Inbox</h2>
                        </div>
                        <span class="text-xs text-navy-400">{{ props.conversations.length }} conversations</span>
                    </div>

                    <div v-if="props.conversations.length" class="divide-y divide-navy-50">
                        <Link
                            v-for="conversation in props.conversations"
                            :key="conversation.id"
                            :href="`/messages/${conversation.id}`"
                            class="block px-5 py-4 transition hover:bg-cream-50"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-navy-800">{{ conversation.subject }}</p>
                                    <p class="mt-0.5 text-xs text-navy-400">
                                        {{ isStudent ? conversation.staff?.name : `${conversation.student?.first_name} ${conversation.student?.last_name}` }}
                                    </p>
                                </div>
                                <span class="whitespace-nowrap text-[0.68rem] text-navy-400">
                                    {{ conversation.last_message_at ? formatDateTime(conversation.last_message_at) : '—' }}
                                </span>
                            </div>
                        </Link>
                    </div>

                    <EmptyState v-else title="No messages yet" description="Conversations will appear here.">
                        <template #icon><MessageSquare class="size-6" /></template>
                    </EmptyState>
                </section>
            </div>
        </div>
    </AppShell>
</template>
