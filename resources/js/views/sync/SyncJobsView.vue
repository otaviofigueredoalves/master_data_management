<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { api } from '../../api/http';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import { formatDateTime, timeAgo } from '../../utils/format';
import PageHeader from '../../components/ui/PageHeader.vue';
import Icon from '../../components/ui/Icon.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import PaginationBar from '../../components/ui/PaginationBar.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import JsonViewer from '../../components/ui/JsonViewer.vue';

const tenant = useTenantStore();
const toast = useToastStore();

const loading = ref(true);
const rows = ref([]);
const paginator = ref(null);
const statusFilter = ref('');
let timer = null;

const statusStyle = {
    pending: 'bg-amber-50 text-amber-700 ring-amber-200',
    running: 'bg-sky-50 text-sky-700 ring-sky-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    failed: 'bg-red-50 text-red-700 ring-red-200',
};

const hasActiveJobs = computed(() =>
    rows.value.some((job) => job.status === 'pending' || job.status === 'running'),
);

async function load(page = 1) {
    if (!tenant.currentTenantId) return;

    loading.value = true;
    try {
        const params = { per_page: 15, page };
        if (statusFilter.value) params.status = statusFilter.value;

        const payload = await api.get('/sync-jobs', params);
        rows.value = payload.data ?? [];
        paginator.value = payload;
    } catch (error) {
        toast.error(error.message);
    } finally {
        loading.value = false;
    }
}

watch(() => tenant.currentTenantId, load, { immediate: true });
watch(statusFilter, () => load());

function startPolling() {
    clearInterval(timer);
    timer = window.setInterval(() => {
        if (hasActiveJobs.value || statusFilter.value === 'pending' || statusFilter.value === 'running') {
            load();
        }
    }, 4000);
}

onBeforeUnmount(() => clearInterval(timer));
startPolling();

/* --------------------------------- detail -------------------------------- */

const detailJob = ref(null);
const detailOpen = ref(false);

async function openDetail(job) {
    detailOpen.value = true;
    detailJob.value = null;
    try {
        detailJob.value = await api.get(`/sync-jobs/${job.id}`);
    } catch (error) {
        toast.error(error.message);
    }
}
</script>

<template>
    <div>
        <PageHeader title="Sincronizações" description="Histórico de execuções dos jobs de integração — atualização automática.">
            <template #icon><Icon name="refresh" class="h-5 w-5" /></template>
            <div class="flex items-center gap-2">
                <span v-if="hasActiveJobs"
                    class="inline-flex items-center gap-2 rounded-md bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 ring-1 ring-sky-200">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute h-full w-full animate-ping rounded-md bg-sky-400 opacity-75" />
                        <span class="relative h-2 w-2 rounded-md bg-sky-500" />
                    </span>
                    executando…
                </span>
                <button class="btn btn-secondary btn-sm" :disabled="loading" @click="load()">
                    <Icon name="refresh" class="h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                    Atualizar
                </button>
            </div>
        </PageHeader>

        <div class="card mb-5 flex flex-wrap items-center gap-3 p-4">
            <span class="text-sm font-semibold text-slate-600">Filtrar:</span>
            <select v-model="statusFilter" class="field !w-auto">
                <option value="">Todos os status</option>
                <option value="pending">pending</option>
                <option value="running">running</option>
                <option value="completed">completed</option>
                <option value="failed">failed</option>
            </select>
        </div>

        <div class="card overflow-hidden">
            <div v-if="loading" class="divide-y divide-slate-100">
                <div v-for="i in 6" :key="i" class="flex items-center gap-4 px-4 py-4">
                    <div class="flex-1 space-y-2"><div class="skeleton h-3 w-1/4" /><div class="skeleton h-3 w-1/6" /></div>
                    <div class="skeleton h-6 w-24 rounded-md" />
                </div>
            </div>

            <div v-else-if="rows.length" class="overflow-x-auto">
                <table class="table-base min-w-[760px]">
                    <thead>
                        <tr class="bg-slate-50/70">
                            <th>Job</th>
                            <th>Integração</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Iniciado</th>
                            <th>Concluído</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="job in rows" :key="job.id">
                            <td><span class="font-mono text-xs font-bold text-slate-700">#{{ job.id }}</span></td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ job.integration?.name ?? '—' }}</p>
                                <p class="text-[11px] text-slate-400 capitalize">{{ job.integration?.type }}</p>
                            </td>
                            <td>
                                <span class="rounded-md bg-teal-50 px-2 py-0.5 text-[11px] font-bold text-teal-700 capitalize ring-1 ring-teal-200">
                                    {{ job.type }}
                                </span>
                            </td>
                            <td>
                                <span class="rounded-md px-2.5 py-1 text-[11px] font-bold capitalize ring-1"
                                    :class="statusStyle[job.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'">
                                    {{ job.status }}
                                </span>
                            </td>
                            <td class="text-xs text-slate-500">
                                <template v-if="job.started_at">
                                    {{ timeAgo(job.started_at) }}
                                    <p class="text-[11px] text-slate-400">{{ formatDateTime(job.started_at) }}</p>
                                </template>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td class="text-xs text-slate-500">
                                <template v-if="job.completed_at">{{ formatDateTime(job.completed_at) }}</template>
                                <span v-else class="text-slate-300">—</span>
                            </td>
                            <td>
                                <div class="flex justify-end">
                                    <button class="btn btn-secondary btn-sm" @click="openDetail(job)">
                                        <Icon name="eye" class="h-3.5 w-3.5" />
                                        Detalhes
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else icon="clock" title="Nenhuma sincronização"
                description="Dispare um sync em uma integração para vê-lo aparecer aqui.">
                <RouterLink :to="{ name: 'integrations' }" class="btn btn-primary">
                    <Icon name="plug" class="h-4 w-4" /> Abrir integrações
                </RouterLink>
            </EmptyState>
        </div>

        <div class="mt-5">
            <PaginationBar :meta="paginator" @page-change="load" />
        </div>

        <!-- Detail -->
        <BaseModal :open="detailOpen" size="lg" @close="detailOpen = false">
            <template v-if="detailJob">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Sync job #{{ detailJob.id }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ detailJob.integration?.name ?? 'Integração' }} ·
                            <span class="capitalize">{{ detailJob.integration?.type }}</span> ·
                            <span class="capitalize">{{ detailJob.type }}</span>
                        </p>
                    </div>
                    <span class="rounded-md px-3 py-1 text-xs font-bold capitalize ring-1"
                        :class="statusStyle[detailJob.status] ?? 'bg-slate-100 text-slate-600 ring-slate-200'">
                        {{ detailJob.status }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Criado</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ formatDateTime(detailJob.created_at) }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Iniciado</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ formatDateTime(detailJob.started_at) }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Concluído</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ formatDateTime(detailJob.completed_at) }}</p>
                    </div>
                </div>

                <div v-if="detailJob.error_message" class="mt-4 rounded-md border border-red-200 bg-red-50 p-4">
                    <p class="text-xs font-bold text-red-700 uppercase">Erro</p>
                    <p class="mt-1 font-mono text-sm text-red-700 break-words">{{ detailJob.error_message }}</p>
                </div>

                <div class="mt-5 space-y-5">
                    <div>
                        <p class="label">Payload enviado</p>
                        <div class="max-h-48 overflow-y-auto rounded-md border border-slate-200 bg-slate-50/60 p-3">
                            <JsonViewer :value="detailJob.payload" root />
                        </div>
                    </div>
                    <div>
                        <p class="label">Resultado</p>
                        <div class="max-h-72 overflow-y-auto rounded-md border border-slate-200 bg-slate-50/60 p-3">
                            <JsonViewer :value="detailJob.result" root />
                        </div>
                    </div>
                </div>
            </template>
            <div v-else class="space-y-3 py-2">
                <div class="skeleton h-4 w-1/3" />
                <div class="skeleton h-40 w-full" />
            </div>
        </BaseModal>
    </div>
</template>
