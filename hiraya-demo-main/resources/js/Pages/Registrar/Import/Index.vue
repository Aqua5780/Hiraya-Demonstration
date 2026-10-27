<script setup lang="ts">
import { Download, FileSpreadsheet, Upload } from '@lucide/vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../../../Components/AppShell.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import type { SharedPageProps } from '../../../types';

defineProps<{
    headers: string[];
}>();

const page = usePage<SharedPageProps>();
const importErrors = computed(() => page.props.flash.import_errors ?? []);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    file: null as File | null,
});

function submit(): void {
    form.post('/registrar/import', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('file');

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

function onFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
}
</script>

<template>
    <AppShell>
        <div class="mx-auto max-w-4xl space-y-6">
            <PageHeader title="Import records">
                <template #actions>
                    <a href="/registrar/import/template" class="btn btn-secondary">
                        <Download class="size-4" />
                        Download template
                    </a>
                </template>
            </PageHeader>

            <section class="card">
                <div class="card-header">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-8 items-center justify-center rounded-md bg-navy-50 text-navy-600">
                            <FileSpreadsheet class="size-4" />
                        </span>
                        <h2 class="card-title">CSV file</h2>
                    </div>
                    <span class="text-xs text-navy-400">Max 2 MB</span>
                </div>

                <form class="space-y-5 p-5" @submit.prevent="submit">
                    <div>
                        <label for="import-file" class="label">Student records file</label>
                        <input
                            id="import-file"
                            ref="fileInput"
                            type="file"
                            accept=".csv,.txt"
                            class="input py-2 text-sm"
                            @input="onFile"
                        />
                        <p v-if="form.errors.file" class="error-text">{{ form.errors.file }}</p>
                        <p class="hint">Use the provided template so the column names match.</p>
                    </div>

                    <div class="rounded-md border border-navy-100 bg-navy-50/50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-navy-600">Expected columns</p>
                        <p class="mt-2 break-words font-mono text-[0.68rem] leading-6 text-navy-500">
                            {{ headers.join(', ') }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <Link href="/registrar/students" class="btn btn-secondary">Cancel</Link>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing || !form.file">
                            <Upload class="size-4" />
                            {{ form.processing ? 'Importing…' : 'Import records' }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-if="importErrors.length" class="card border-red-200">
                <div class="card-header">
                    <h2 class="card-title text-red-700">Validation errors</h2>
                    <span class="text-xs text-red-600">{{ importErrors.length }} issue(s)</span>
                </div>
                <div class="table-shell">
                    <table class="data-table">
                        <thead><tr><th>Row</th><th>Column</th><th>Message</th></tr></thead>
                        <tbody>
                            <tr v-for="(error, index) in importErrors" :key="index">
                                <td class="text-xs">{{ error.row }}</td>
                                <td class="font-mono text-xs">{{ error.column }}</td>
                                <td class="text-xs">{{ error.message }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppShell>
</template>
