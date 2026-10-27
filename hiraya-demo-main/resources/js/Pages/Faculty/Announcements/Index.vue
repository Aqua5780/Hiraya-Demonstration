<script setup lang="ts">
import { Megaphone, Paperclip, Pencil, Plus, Trash2 } from '@lucide/vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import Badge from '../../../Components/Badge.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { showConfirm } from '../../../lib/confirm';
import { formatDateTime, titleCase } from '../../../lib/format';

interface SectionOption {
    id: number;
    name: string;
    program: string | null;
    year_level: string | null;
}

interface AnnouncementRow {
    id: number;
    title: string;
    content: string;
    status: string;
    posted_at: string | null;
    created_at: string;
    attachment_name: string | null;
    sections: SectionOption[];
}

const props = defineProps<{
    year: { id: number; name: string } | null;
    announcements: AnnouncementRow[];
    sections: SectionOption[];
}>();

const showModal = ref(false);
const editing = ref<AnnouncementRow | null>(null);

const form = useForm({
    title: '',
    content: '',
    status: 'draft',
    target_sections: [] as number[],
    attachment: null as File | null,
});

function openCreate(): void {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.status = 'draft';
    form.target_sections = [];
    form.attachment = null;
    showModal.value = true;
}

function openEdit(announcement: AnnouncementRow): void {
    editing.value = announcement;
    form.clearErrors();
    form.title = announcement.title;
    form.content = announcement.content;
    form.status = announcement.status;
    form.target_sections = announcement.sections.map((section) => section.id);
    form.attachment = null;
    showModal.value = true;
}

function toggleSection(id: number): void {
    if (form.target_sections.includes(id)) {
        form.target_sections = form.target_sections.filter((sectionId) => sectionId !== id);
    } else {
        form.target_sections.push(id);
    }
}

function onAttachment(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.attachment = input.files?.[0] ?? null;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    };

    if (editing.value) {
        form.patch(`/faculty/announcements/${editing.value.id}`, options);
    } else {
        form.post('/faculty/announcements', options);
    }
}

function destroy(announcement: AnnouncementRow): void {
    showConfirm(
        {
            title: 'Delete announcement',
            message: `Delete “${announcement.title}”?`,
            confirmLabel: 'Delete',
            tone: 'danger',
        },
        () => router.delete(`/faculty/announcements/${announcement.id}`, { preserveScroll: true }),
    );
}
</script>

<template>
    <AppShell>
        <div class="space-y-6">
            <PageHeader title="Class announcements">
                <template #actions>
                    <button type="button" class="btn btn-primary" @click="openCreate">
                        <Plus class="size-4" />
                        New announcement
                    </button>
                </template>
            </PageHeader>

            <section class="card">
                <div class="table-shell">
                    <table class="data-table min-w-[52rem]">
                        <thead><tr><th>Title</th><th>Sections</th><th>Status</th><th>Posted</th><th>Attachment</th><th class="text-right">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="announcement in props.announcements" :key="announcement.id">
                                <td>
                                    <p class="max-w-[22rem] truncate font-semibold text-navy-800">{{ announcement.title }}</p>
                                    <p class="mt-0.5 max-w-[28rem] truncate text-xs text-navy-400">{{ announcement.content }}</p>
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-for="section in announcement.sections" :key="section.id" tone="navy">
                                            {{ section.program }} {{ section.name }}
                                        </Badge>
                                    </div>
                                </td>
                                <td><Badge :tone="announcement.status === 'published' ? 'green' : 'gold'">{{ titleCase(announcement.status) }}</Badge></td>
                                <td class="text-xs">{{ announcement.posted_at ? formatDateTime(announcement.posted_at) : '—' }}</td>
                                <td class="text-xs">
                                    <a
                                        v-if="announcement.attachment_name"
                                        :href="`/announcements/${announcement.id}/attachment`"
                                        class="inline-flex items-center gap-1 text-navy-600 hover:text-navy-900"
                                    >
                                        <Paperclip class="size-3.5" /> {{ announcement.attachment_name }}
                                    </a>
                                    <span v-else class="text-navy-300">None</span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openEdit(announcement)">
                                            <Pencil class="size-3.5" /> Edit
                                        </button>
                                        <button type="button" class="btn btn-ghost btn-sm text-red-600" @click="destroy(announcement)">
                                            <Trash2 class="size-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="props.announcements.length === 0" class="empty-state">
                    <Megaphone class="size-6 text-navy-300" />
                    <p class="text-sm font-semibold text-navy-800">No class announcements yet</p>
                    <p class="text-xs text-navy-400">Post an announcement to one of your sections.</p>
                </div>
            </section>
        </div>

        <Modal :show="showModal" :title="editing ? 'Edit announcement' : 'New class announcement'" max-width="xl" @close="showModal = false">
            <form id="faculty-announcement-form" class="space-y-5 p-5" @submit.prevent="submit">
                <div>
                    <label for="faculty-announcement-title" class="label">Title</label>
                    <input id="faculty-announcement-title" v-model="form.title" type="text" class="input" />
                    <p v-if="form.errors.title" class="error-text">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label for="faculty-announcement-content" class="label">Content</label>
                    <textarea id="faculty-announcement-content" v-model="form.content" rows="6" class="input" />
                    <p v-if="form.errors.content" class="error-text">{{ form.errors.content }}</p>
                </div>
                <div>
                    <span class="label">Target sections</span>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="section in props.sections"
                            :key="section.id"
                            class="flex cursor-pointer items-center gap-2 rounded-md border border-navy-100 px-3 py-2 text-sm"
                            :class="form.target_sections.includes(section.id) ? 'border-gold-300 bg-gold-50' : 'bg-white'"
                        >
                            <input
                                type="checkbox"
                                class="size-4 rounded border-navy-300 text-gold-500 focus:ring-gold-400"
                                :checked="form.target_sections.includes(section.id)"
                                @change="toggleSection(section.id)"
                            />
                            <span>{{ section.program }} {{ section.year_level }} — {{ section.name }}</span>
                        </label>
                    </div>
                    <p v-if="props.sections.length === 0" class="mt-1 text-xs text-navy-400">No sections are assigned to your classes.</p>
                    <p v-if="form.errors.target_sections" class="error-text">{{ form.errors.target_sections }}</p>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="faculty-announcement-status" class="label">Status</label>
                        <select id="faculty-announcement-status" v-model="form.status" class="input">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                    <div>
                        <label for="faculty-announcement-attachment" class="label">Attachment</label>
                        <input
                            id="faculty-announcement-attachment"
                            type="file"
                            class="input py-1.5 text-xs"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                            @input="onAttachment"
                        />
                        <p v-if="form.errors.attachment" class="error-text">{{ form.errors.attachment }}</p>
                        <a
                            v-if="editing?.attachment_name"
                            :href="`/announcements/${editing.id}/attachment`"
                            target="_blank"
                            class="mt-1 inline-flex items-center gap-1 text-xs text-navy-500 underline"
                        >
                            Current: {{ editing.attachment_name }}
                        </a>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Cancel</button>
                    <button type="submit" form="faculty-announcement-form" class="btn btn-primary" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save announcement' }}
                    </button>
                </div>
            </template>
        </Modal>
    </AppShell>
</template>
