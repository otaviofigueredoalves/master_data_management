<script setup>
import { ref, watch } from 'vue';
import { api } from '../../api/http';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import { formatDateTime, timeAgo } from '../../utils/format';
import { AUDIT_ACTIONS, ENTITY_TYPE_LABELS, auditActionLabel, logEntityLabel } from '../../utils/audit';
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
const permissionDenied = ref(false);

const filters = ref({ action: '', entity_type: '', user_id: '' });

const actionTone = (action) => {
    if (action.includes('deleted')) return 'bg-red-50 text-red-700 ring-red-200';
    if (action.includes('failed')) return 'bg-red-50 text-red-700 ring-red-200';
    if (action.includes('created')) return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    if (action.includes('normalized') || action.includes('completed')) return 'bg-slate-50 text-slate-700 ring-slate-200';
    if (action.includes('updated') || action.includes('sync_started')) return 'bg-amber-50 text-amber-700 ring-amber-200';

    return 'bg-slate-100 text-slate-600 ring-slate-200';
};

async function load(page = 1) {
    if (!tenant.currentTenantId) return;

    loading.value = true;
    permissionDenied.value = false;
    try {
        const params = { per_page: 20, page };
        if (filters.value.action) params.action = filters.value.action;
        if (filters.value.entity_type) params.entity_type = filters.value.entity_type;
        if (filters.value.user_id) params.user_id = filters.value.user_id;

        const payload = await api.get('/audit-logs', params);
        rows.value = payload.data ?? [];
        paginator.value = payload;
    } catch (error) {
        if (error.status === 403) {
            permissionDenied.value = true;
        } else {
            toast.error(error.message);
        }
    } finally {
        loading.value = false;
    }
}

watch(() => tenant.currentTenantId, load, { immediate: true });

const detailLog = ref(null);
const detailOpen = ref(false);

function openDetail(log) {
    detailLog.value = log;
    detailOpen.value = true;
}
</script>

<template>
    <div>
        <PageHeader title="Auditoria" description="Trilha completa de quem fez o quê neste workspace, com antes/depois.">
            <template #icon><Icon name="shield" class="h-5 w-5" /></template>
            <button class="btn btn-secondary btn-sm" :disabled="loading" @click="load()">
                <Icon name="refresh" class="h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                Atualizar
            </button>
        </PageHeader>

        <!-- Permission gate -->
        <div v-if="permissionDenied" class="card">
            <EmptyState icon="shield" title="Sem permissão para auditoria"
                description="Seu papel neste workspace não permite visualizar a trilha de auditoria. Fale com o admin." />
        </div>

        <template v-else>
            <div class="card mb-5 p-4">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="label !mb-1" for="audit-action">Ação</label>
                        <select id="audit-action" v-model="filters.action" class="field">
                            <option value="">Todas as ações</option>
                            <option v-for="action in AUDIT_ACTIONS" :key="action" :value="action">{{ auditActionLabel(action) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label !mb-1" for="audit-entity">Entidade</label>
                        <select id="audit-entity" v-model="filters.entity_type" class="field">
                            <option value="">Todas as entidades</option>
                            <option v-for="(label, key) in ENTITY_TYPE_LABELS" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button class="btn btn-secondary w-full" :disabled="!filters.action && !filters.entity_type" @click="load()">
                            <Icon name="filter" class="h-4 w-4" /> Aplicar filtros
                        </button>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div v-if="loading" class="divide-y divide-slate-100">
                    <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-4 py-4">
                        <div class="skeleton h-8 w-8 rounded-md" />
                        <div class="flex-1 space-y-2"><div class="skeleton h-3 w-1/3" /><div class="skeleton h-3 w-1/4" /></div>
                    </div>
                </div>

                <div v-else-if="rows.length" class="divide-y divide-slate-100">
                    <button v-for="log in rows" :key="log.id"
                        class="flex w-full items-center gap-4 px-4 py-2.5 text-left transition hover:bg-slate-50"
                        @click="openDetail(log)">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-md"
                            :class="log.user_id ? 'bg-slate-800 text-white' : 'bg-slate-200 text-slate-500'">
                            <Icon :name="log.user_id ? 'users' : 'activity'" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-bold ring-1"
                                    :class="actionTone(log.action)">
                                    {{ auditActionLabel(log.action) }}
                                </span>
                                <span class="text-xs font-semibold text-slate-700">
                                    {{ log.user?.name ?? 'system' }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-xs text-slate-400">
                                <span class="font-semibold text-slate-500">{{ logEntityLabel(log) }}</span> · #{{ log.entity_id }}
                                <template v-if="log.ip_address"> · {{ log.ip_address }}</template>
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs font-medium text-slate-500">{{ timeAgo(log.created_at) }}</p>
                            <p class="text-[11px] text-slate-400">{{ formatDateTime(log.created_at) }}</p>
                        </div>
                        <Icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-300" />
                    </button>
                </div>

                <EmptyState v-else icon="shield" title="Nenhum evento encontrado"
                    description="Altere o filtro ou realize alguma ação para gerar registros de auditoria." />
            </div>

            <div class="mt-5">
                <PaginationBar :meta="paginator" @page-change="load" />
            </div>
        </template>

        <!-- Detail -->
        <BaseModal :open="Boolean(detailLog)" size="xl" @close="detailOpen = false">
            <template v-if="detailLog">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span class="rounded-md px-2.5 py-1 text-xs font-bold ring-1"
                            :class="actionTone(detailLog.action)">
                            {{ auditActionLabel(detailLog.action) }}
                        </span>
                        <h2 class="mt-2 text-lg font-bold text-slate-900">
                            {{ detailLog.user?.name ?? 'system' }}
                            <span class="font-normal text-slate-400">em</span>
                            {{ formatDateTime(detailLog.created_at) }}
                        </h2>
                        <p class="mt-0.5 text-sm text-slate-500">
                            {{ logEntityLabel(detailLog) }} · #{{ detailLog.entity_id }}
                        </p>
                    </div>
                    <span class="grid h-10 w-10 place-items-center rounded-md bg-slate-100 text-slate-500">
                        <Icon name="shield" class="h-5 w-5" />
                    </span>
                </div>

                <div v-if="detailLog.old_values || detailLog.new_values" class="mt-6 grid gap-4 lg:grid-cols-2">
                    <div>
                        <p class="label">Antes</p>
                        <div class="max-h-96 overflow-y-auto rounded-md border border-slate-200 bg-slate-50/60 p-3">
                            <JsonViewer v-if="detailLog.old_values" :value="detailLog.old_values" root />
                            <p v-else class="text-sm text-slate-400 italic">— sem valor anterior (criação)</p>
                        </div>
                    </div>
                    <div>
                        <p class="label">Depois</p>
                        <div class="max-h-96 overflow-y-auto rounded-md border border-emerald-200 bg-emerald-50/30 p-3">
                            <JsonViewer v-if="detailLog.new_values" :value="detailLog.new_values" root />
                            <p v-else class="text-sm text-slate-400 italic">— sem valor posterior (exclusão)</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 border-t border-slate-100 pt-4 text-xs text-slate-400">
                    <span>IP: {{ detailLog.ip_address ?? '—' }}</span>
                    <span>User agent: {{ detailLog.user_agent ? detailLog.user_agent.slice(0, 60) : '—' }}</span>
                </div>
            </template>
        </BaseModal>
    </div>
</template>
