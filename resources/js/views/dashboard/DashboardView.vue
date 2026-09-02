<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { api } from '../../api/http';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import { timeAgo } from '../../utils/format';
import { auditActionLabel, logEntityLabel } from '../../utils/audit';
import Icon from '../../components/ui/Icon.vue';
import PageHeader from '../../components/ui/PageHeader.vue';

const tenantStore = useTenantStore();
const toast = useToastStore();

const loading = ref(true);
const metrics = ref(null);
let timer = null;

const cards = computed(() => [
    {
        label: 'Entidades MDM',
        value: metrics.value?.entities_total ?? 0,
        icon: 'layers',
        hint: `${metrics.value?.entities_by_type ? Object.keys(metrics.value.entities_by_type).length : 0} tipos distintos`,
        tone: 'bg-slate-900',
    },
    {
        label: 'Golden records',
        value: metrics.value?.entities_master ?? 0,
        icon: 'star',
        hint: 'registros normalizados',
        tone: 'bg-slate-800',
    },
    {
        label: 'Integrações ativas',
        value: metrics.value?.integrations_active ?? 0,
        icon: 'plug',
        hint: 'conexões com origem',
        tone: 'bg-slate-700',
    },
    {
        label: 'Membros ativos',
        value: metrics.value?.members_active ?? 0,
        icon: 'users',
        hint: 'no workspace',
        tone: 'bg-slate-600',
    },
]);

const typeEntries = computed(() => {
    const byType = metrics.value?.entities_by_type ?? {};

    return Object.entries(byType).sort((a, b) => b[1] - a[1]);
});

const maxType = computed(() => Math.max(1, ...typeEntries.value.map(([, total]) => total)));

const statusStyle = {
    pending: 'bg-amber-100 text-amber-700',
    running: 'bg-sky-100 text-sky-700',
    completed: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
};

async function load() {
    if (!tenantStore.currentTenantId) return;

    loading.value = true;
    try {
        metrics.value = await api.get('/dashboard');
    } catch (error) {
        toast.error(error.message);
    } finally {
        loading.value = false;
    }
}

watch(() => tenantStore.currentTenantId, load, { immediate: true });

function startRefresh() {
    clearInterval(timer);
    timer = window.setInterval(() => {
        // Guard against payloads where the list is absent or not an array.
        const recent = metrics.value?.sync_jobs_last;
        const active = Array.isArray(recent) && recent.some((job) => job.status === 'pending' || job.status === 'running');
        if (active) load();
    }, 5000);
}

onBeforeUnmount(() => clearInterval(timer));
startRefresh();
</script>

<template>
    <div>
        <PageHeader title="Dashboard" description="Visão geral do workspace — métricas cacheadas por tenant.">
            <template #icon><Icon name="grid" class="h-5 w-5" /></template>
            <button class="btn btn-secondary btn-sm" :disabled="loading" @click="load">
                <Icon name="refresh" class="h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                Atualizar
            </button>
        </PageHeader>

        <div v-if="loading && !metrics" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="i in 4" :key="i" class="card h-32 p-4"><div class="skeleton h-full w-full" /></div>
        </div>

        <template v-else>
            <!-- Stat cards -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="card in cards" :key="card.label"
                    class="card group relative overflow-hidden p-4 transition hover:shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[13px] font-semibold text-slate-500">{{ card.label }}</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
                        </div>
                        <span
                            class="grid h-9 w-9 place-items-center rounded text-white transition-colors duration-200"
                            :class="card.tone">
                            <Icon :name="card.icon" class="h-5 w-5" />
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                <!-- Entities by type -->
                <div class="card p-4 lg:col-span-2">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Entidades por tipo</h2>
                            <p class="text-xs text-slate-400">Distribuição dos registros do workspace</p>
                        </div>
                        <span class="rounded-md bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                            {{ metrics?.entities_total ?? 0 }} total
                        </span>
                    </div>

                    <div v-if="typeEntries.length" class="space-y-3">
                        <div v-for="[type, total] in typeEntries" :key="type" class="flex items-center gap-3">
                            <span class="w-24 truncate text-xs font-semibold text-slate-600 capitalize">{{ type }}</span>
                            <div class="h-2.5 flex-1 overflow-hidden rounded-md bg-slate-100">
                                <div class="h-full rounded-sm bg-slate-900 transition-all duration-500"
                                    :style="{ width: `${(total / maxType) * 100}%` }" />
                            </div>
                            <span class="w-8 text-right text-xs font-bold text-slate-700">{{ total }}</span>
                        </div>
                    </div>
                    <div v-else class="rounded-md border border-dashed border-slate-200 py-8 text-center text-sm text-slate-400">
                        Nenhuma entidade cadastrada ainda.
                    </div>
                </div>

                <!-- Recent syncs -->
                <div class="card p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900">Sincronizações recentes</h2>
                        <RouterLink :to="{ name: 'sync-jobs' }"
                            class="text-xs font-semibold text-slate-600 hover:text-slate-700">
                            Ver todas
                        </RouterLink>
                    </div>

                    <div v-if="metrics?.sync_jobs_last?.length" class="space-y-2.5">
                        <div v-for="job in metrics.sync_jobs_last" :key="job.id"
                            class="flex items-center justify-between gap-3 rounded-md border border-slate-100 px-3 py-2.5">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="relative flex h-2 w-2 shrink-0">
                                    <span v-if="job.status === 'running' || job.status === 'pending'"
                                        class="absolute inline-flex h-full w-full animate-ping rounded-md bg-sky-400 opacity-75" />
                                    <span class="relative inline-flex h-2 w-2 rounded-md"
                                        :class="{
                                            'bg-amber-400': job.status === 'pending',
                                            'bg-sky-500': job.status === 'running',
                                            'bg-emerald-500': job.status === 'completed',
                                            'bg-red-500': job.status === 'failed',
                                        }" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-bold text-slate-700">Sync #{{ job.id }}</span>
                                    <span class="text-[11px] text-slate-400 capitalize">{{ job.type }}</span>
                                </span>
                            </div>
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold capitalize"
                                :class="statusStyle[job.status] ?? 'bg-slate-100 text-slate-600'">
                                {{ job.status }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="rounded-md border border-dashed border-slate-200 py-8 text-center text-sm text-slate-400">
                        Nenhuma sincronização ainda.
                    </div>
                </div>
            </div>

            <!-- Recent audit -->
            <div class="card mt-6">
                <div class="card-header">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Últimos eventos de auditoria</h2>
                        <p class="text-xs text-slate-400">Quem fez o quê, quando — nos últimos registros</p>
                    </div>
                    <RouterLink :to="{ name: 'audit' }" class="text-xs font-semibold text-slate-600 hover:text-slate-700">
                        Abrir trilha
                    </RouterLink>
                </div>
                <div v-if="metrics?.recent_audit_logs?.length" class="divide-y divide-slate-100">
                    <div v-for="log in metrics.recent_audit_logs" :key="log.id"
                        class="flex items-center gap-3 px-4 py-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400">
                            <Icon name="shield" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-slate-700">
                                <span class="font-semibold">{{ log.user?.name ?? 'system' }}</span>
                                <span class="text-slate-400"> · </span>
                                <span class="text-xs text-slate-500">{{ auditActionLabel(log.action) }}</span>
                            </p>
                            <p class="text-[11px] text-slate-400">{{ logEntityLabel(log) }} · #{{ log.entity_id }}</p>
                        </div>
                        <span class="shrink-0 text-[11px] text-slate-400">{{ timeAgo(log.created_at) }}</span>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-sm text-slate-400">Nenhum evento de auditoria registrado.</div>
            </div>
        </template>
    </div>
</template>
