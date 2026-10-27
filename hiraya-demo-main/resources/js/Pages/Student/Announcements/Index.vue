<script setup lang="ts">
import { Megaphone, Paperclip } from '@lucide/vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import { formatDateTime } from '../../../lib/format';

interface AnnouncementRow {
    id: number;
    title: string;
    content: string;
    audience_type: string;
    author: string | null;
    posted_at: string | null;
    attachment_name: string | null;
    sections: { id: number; name: string; program: string | null; year_level: string | null }[];
}

defineProps<{
    year: { id: number; name: string } | null;
    enrollment: { section: string | null; section_id: number | null } | null;
    announcements: {
        data: AnnouncementRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
}>();
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Announcements" />

            <section class="card">
                <div class="divide-y divide-navy-50 px-5">
                    <article v-for="announcement in announcements.data" :key="announcement.id" class="py-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-base font-bold text-navy-900">{{ announcement.title }}</p>
                                <p class="mt-1 text-xs text-navy-400">
                                    {{ announcement.author ?? 'System' }} · {{ announcement.posted_at ? formatDateTime(announcement.posted_at) : '—' }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <Badge :tone="announcement.audience_type === 'global' ? 'navy' : 'gold'">
                                    {{ announcement.audience_type === 'global' ? 'Global' : 'Class' }}
                                </Badge>
                                <Badge v-for="section in announcement.sections" :key="section.id" tone="gray">
                                    {{ section.program }} {{ section.name }}
                                </Badge>
                            </div>
                        </div>

                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-navy-600">{{ announcement.content }}</p>

                        <a
                            v-if="announcement.attachment_name"
                            :href="`/announcements/${announcement.id}/attachment`"
                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-navy-600 hover:text-navy-900"
                        >
                            <Paperclip class="size-3.5" /> {{ announcement.attachment_name }}
                        </a>
                    </article>

                    <EmptyState
                        v-if="announcements.data.length === 0"
                        title="No announcements yet"
                        description="Global and class announcements will appear here."
                    >
                        <template #icon><Megaphone class="size-6" /></template>
                    </EmptyState>
                </div>

                <Pagination
                    :links="announcements.links"
                    :from="announcements.from"
                    :to="announcements.to"
                    :total="announcements.total"
                />
            </section>

            <p v-if="!enrollment" class="text-xs text-navy-400">
                You are not enrolled yet, so only global announcements are shown.
            </p>
        </div>
    </AppShell>
</template>
