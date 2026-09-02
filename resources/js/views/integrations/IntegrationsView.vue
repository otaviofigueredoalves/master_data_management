<script setup>
import { computed, ref, watch } from 'vue';
import { api } from '../../api/http';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import { formatDateTime, timeAgo } from '../../utils/format';
import PageHeader from '../../components/ui/PageHeader.vue';
import Icon from '../../components/ui/Icon.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import PaginationBar from '../../components/ui/PaginationBar.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import RowActions from '../../components/ui/RowActions.vue';

const tenant = useTenantStore();
const toast = useToastStore();

const loading = ref(true);
const rows = ref([]);
const paginator = ref(null);

const typeStyle = {
    salesforce: 'bg-slate-50 text-slate-700 ring-slate-200',
    sap: 'bg-amber-50 text-amber-700 ring-amber-200',
    crm: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    erp: 'bg-sky-50 text-sky-700 ring-sky-200',
    legacy: 'bg-slate-100 text-slate-600 ring-slate-200',
};

function styleFor(type) {
    return typeStyle[type] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

async function load(page = 1) {
    if (!tenant.currentTenantId) return;

    loading.value = true;
    try {
        const payload = await api.get('/integrations', { per_page: 15, page });
        rows.value = payload.data ?? [];
        paginator.value = payload;
    } catch (error) {
        toast.error(error.message);
    } finally {
        loading.value = false;
    }
}

watch(() => tenant.currentTenantId, load, { immediate: true });

const canManage = computed(() => tenant.can('manage integrations'));
const canSync = computed(() => tenant.can('run sync'));

/* ----------------------------- create / edit ----------------------------- */

const formOpen = ref(false);
const editing = ref(null);
const saving = ref(false);
const formErrors = ref({});
const jsonErrors = ref({});
const form = ref({ name: '', type: 'salesforce', credentials: '', settings: '', is_active: true });

const credentialTemplates = {
    salesforce: { instance: 'acme.my.salesforce.com', username: 'svc.acme', token: '••••••••' },
    sap: { host: 'sap.acme.internal', client: '100', user: 'rfc_svc' },
    legacy: { endpoint: 'https://legacy.acme.io/api', api_key: '••••••••' },
};

function openCreate() {
    editing.value = null;
    form.value = {
        name: '',
        type: 'salesforce',
        credentials: JSON.stringify(credentialTemplates.salesforce, null, 4),
        settings: JSON.stringify({ direction: 'inbound', schedule: 'daily' }, null, 4),
        is_active: true,
    };
    formErrors.value = {};
    jsonErrors.value = {};
    formOpen.value = true;
}

function openEdit(integration) {
    editing.value = integration;
    form.value = {
        name: integration.name,
        type: integration.type,
        credentials: JSON.stringify(integration.credentials ?? {}, null, 4),
        settings: JSON.stringify(integration.settings ?? {}, null, 4),
        is_active: integration.is_active,
    };
    formErrors.value = {};
    jsonErrors.value = {};
    formOpen.value = true;
}

function parseJsonField(key, value) {
    try {
        const parsed = JSON.parse(value || '{}');
        jsonErrors.value[key] = '';

        return parsed;
    } catch (error) {
        jsonErrors.value[key] = error.message;

        return null;
    }
}

async function save() {
    const credentials = parseJsonField('credentials', form.value.credentials);
    const settings = parseJsonField('settings', form.value.settings);
    if (credentials === null || settings === null) return;

    saving.value = true;
    formErrors.value = {};
    try {
        const payload = {
            name: form.value.name.trim(),
            type: form.value.type.trim(),
            credentials,
            settings,
            is_active: form.value.is_active,
        };

        if (editing.value) {
            await api.put(`/integrations/${editing.value.id}`, payload);
            toast.success('Integração atualizada.');
        } else {
            await api.post('/integrations', payload);
            toast.success('Integração criada.');
        }

        formOpen.value = false;
        load();
    } catch (error) {
        formErrors.value = error.errors ?? {};
        if (!Object.keys(error.errors ?? {}).length) toast.error(error.message);
    } finally {
        saving.value = false;
    }
}

async function toggleActive(integration) {
    try {
        const updated = await api.put(`/integrations/${integration.id}`, {
            name: integration.name,
            type: integration.type,
            credentials: integration.credentials ?? {},
            settings: integration.settings ?? {},
            is_active: !integration.is_active,
        });
        Object.assign(integration, updated);
        toast.success(updated.is_active ? 'Integração ativada.' : 'Integração desativada.');
    } catch (error) {
        toast.error(error.message);
    }
}

/* ---------------------------------- sync --------------------------------- */

const syncResult = ref(null);

async function triggerSync(integration, type) {
    try {
        const job = await api.post(`/integrations/${integration.id}/sync`, { type });
        syncResult.value = job;
        toast.success(`Sincronização ${type} enfileirada.`);
    } catch (error) {
        toast.error(error.message);
    }
}

const deleteTarget = ref(null);

async function confirmDelete() {
    try {
        await api.del(`/integrations/${deleteTarget.value.id}`);
        toast.success('Integração removida.');
        deleteTarget.value = null;
        load();
    } catch (error) {
        toast.error(error.message);
    }
}

function credentialKeys(integration) {
    return Object.keys(integration.credentials ?? {}).join(' · ');
}

function actionsFor(integration) {
    const actions = [];

    if (canSync.value) {
        actions.push({ key: 'sync-full', label: 'Sync completo', icon: 'download' });
        actions.push({ key: 'sync-inc', label: 'Sync incremental', icon: 'refresh' });
    }

    if (canManage.value) {
        if (actions.length) actions.push({ divider: true });
        actions.push({ key: 'edit', label: 'Editar', icon: 'pencil' });
        actions.push({ key: 'delete', label: 'Excluir', icon: 'trash', danger: true });
    }

    return actions;
}

function runIntegrationAction(integration, action) {
    if (action.key === 'sync-full') triggerSync(integration, 'full');
    if (action.key === 'sync-inc') triggerSync(integration, 'incremental');
    if (action.key === 'edit') openEdit(integration);
    if (action.key === 'delete') deleteTarget.value = integration;
}
</script>

<template>
    <div>
        <PageHeader title="Integrações"
            description="Sistemas de origem conectados ao workspace. Sincronizações rodam em fila e são rastreáveis.">
            <template #icon><Icon name="plug" class="h-5 w-5" /></template>
            <button v-if="canManage" class="btn btn-primary" @click="openCreate">
                <Icon name="plus" class="h-4 w-4" />
                Nova integração
            </button>
        </PageHeader>

        <div class="card overflow-hidden">
            <div v-if="loading" class="divide-y divide-slate-100">
                <div v-for="i in 5" :key="i" class="flex items-center gap-4 px-4 py-4">
                    <div class="skeleton h-10 w-10 rounded-md" />
                    <div class="flex-1 space-y-2"><div class="skeleton h-3 w-1/3" /><div class="skeleton h-3 w-1/5" /></div>
                </div>
            </div>

            <div v-else-if="rows.length" class="overflow-x-auto">
                <table class="table-base min-w-[820px]">
                    <thead>
                        <tr class="bg-slate-50/70">
                            <th>Integração</th>
                            <th>Status</th>
                            <th>Último sync</th>
                            <th class="text-center">Syncs</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="integration in rows" :key="integration.id">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid h-9 w-9 place-items-center rounded-md bg-slate-100 text-slate-500">
                                        <Icon name="database" class="h-4.5 w-4.5" />
                                    </span>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ integration.name }}</p>
                                        <p class="text-xs text-slate-400">
                                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold capitalize ring-1"
                                                :class="styleFor(integration.type)">{{ integration.type }}</span>
                                            <span class="ml-1.5">{{ credentialKeys(integration) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button
                                    class="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-md transition-colors disabled:opacity-50"
                                    :class="integration.is_active ? 'bg-emerald-500' : 'bg-slate-200'"
                                    :disabled="!canManage"
                                    role="switch"
                                    :aria-checked="integration.is_active"
                                    :title="canManage ? 'Alternar status' : 'Sem permissão'"
                                    @click="toggleActive(integration)">
                                    <span class="inline-block h-4.5 w-4.5 transform rounded-md bg-white shadow transition-transform"
                                        :class="integration.is_active ? 'translate-x-5.5' : 'translate-x-1'" />
                                </button>
                                <span class="ml-2 text-xs font-semibold" :class="integration.is_active ? 'text-emerald-600' : 'text-slate-400'">
                                    {{ integration.is_active ? 'ativa' : 'inativa' }}
                                </span>
                            </td>
                            <td class="text-xs text-slate-500">
                                <template v-if="integration.last_sync_at">
                                    <p>{{ timeAgo(integration.last_sync_at) }}</p>
                                    <p class="text-[11px] text-slate-400">{{ formatDateTime(integration.last_sync_at) }}</p>
                                </template>
                                <span v-else class="text-slate-300">nunca</span>
                            </td>
                            <td class="text-center">
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600">
                                    {{ integration.sync_jobs_count ?? 0 }}
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end">
                                    <RowActions
                                        :items="actionsFor(integration)"
                                        @select="(action) => runIntegrationAction(integration, action)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else icon="plug" title="Nenhuma integração ainda"
                description="Conecte um ERP, CRM ou sistema legado para começar a sincronizar entidades.">
                <button v-if="canManage" class="btn btn-primary" @click="openCreate">
                    <Icon name="plus" class="h-4 w-4" /> Conectar sistema
                </button>
            </EmptyState>
        </div>

        <div class="mt-5">
            <PaginationBar :meta="paginator" @page-change="load" />
        </div>

        <!-- Sync started dialog -->
        <BaseModal :open="Boolean(syncResult)" size="sm" title="Sincronização enfileirada" @close="syncResult = null">
            <div v-if="syncResult" class="flex items-start gap-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-amber-100 text-amber-600">
                    <Icon name="clock" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm leading-relaxed text-slate-600">
                        O job <strong class="font-semibold">#{{ syncResult.id }}</strong> foi criado com status
                        <span class="rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-700 capitalize">{{ syncResult.status }}</span>.
                        Ele executa em segundo plano na fila de sincronização.
                    </p>
                </div>
            </div>
            <template #footer>
                <button class="btn btn-secondary" @click="syncResult = null">Fechar</button>
                <RouterLink :to="{ name: 'sync-jobs' }" class="btn btn-primary" @click="syncResult = null">
                    Acompanhar syncs
                </RouterLink>
            </template>
        </BaseModal>

        <!-- Create/edit integration -->
        <BaseModal :open="formOpen" size="lg" @close="formOpen = false">
            <template v-if="editing">
                <h2 class="text-lg font-bold text-slate-900">Editar integração</h2>
                <p class="mt-0.5 text-sm text-slate-500">Credenciais são salvas criptografadas em repouso.</p>
            </template>
            <template v-else>
                <h2 class="text-lg font-bold text-slate-900">Nova integração</h2>
                <p class="mt-0.5 text-sm text-slate-500">Credenciais são salvas criptografadas em repouso.</p>
            </template>

            <form class="mt-6 space-y-4" @submit.prevent="save">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="integration-name">Nome</label>
                        <input id="integration-name" v-model="form.name" class="field" placeholder="Ex.: Salesforce CRM" required />
                    </div>
                    <div>
                        <label class="label" for="integration-type">Tipo</label>
                        <input id="integration-type" v-model="form.type" class="field" list="integration-types" required />
                        <datalist id="integration-types">
                            <option v-for="type in ['salesforce', 'sap', 'crm', 'erp', 'legacy', 'custom']" :key="type" :value="type" />
                        </datalist>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="integration-credentials">Credenciais (JSON)</label>
                        <textarea id="integration-credentials" v-model="form.credentials" rows="6" spellcheck="false"
                            class="field !font-mono !text-[13px]" :class="jsonErrors.credentials ? '!border-red-400' : ''"></textarea>
                        <p v-if="jsonErrors.credentials" class="field-error-text">{{ jsonErrors.credentials }}</p>
                    </div>
                    <div>
                        <label class="label" for="integration-settings">Configurações (JSON)</label>
                        <textarea id="integration-settings" v-model="form.settings" rows="6" spellcheck="false"
                            class="field !font-mono !text-[13px]" :class="jsonErrors.settings ? '!border-red-400' : ''"></textarea>
                        <p v-if="jsonErrors.settings" class="field-error-text">{{ jsonErrors.settings }}</p>
                    </div>
                </div>

                <label class="flex cursor-pointer items-center gap-3">
                    <input v-model="form.is_active" type="checkbox"
                        class="h-4.5 w-4.5 rounded border-slate-300 text-slate-600 focus:ring-slate-500" />
                    <span class="text-sm font-medium text-slate-700">Integração ativa (disponível para sync)</span>
                </label>
            </form>

            <template #footer>
                <button class="btn btn-secondary" @click="formOpen = false">Cancelar</button>
                <button class="btn btn-primary" :disabled="saving || !form.name.trim()" @click="save">
                    <Icon v-if="saving" name="refresh" class="h-4 w-4 animate-spin" />
                    {{ editing ? 'Salvar alterações' : 'Criar integração' }}
                </button>
            </template>
        </BaseModal>

        <ConfirmDialog :open="Boolean(deleteTarget)" title="Excluir integração?"
            :message="`A integração ${deleteTarget?.name ?? ''} e seu histórico de syncs associado serão removidos.`"
            confirm-text="Excluir" danger @confirm="confirmDelete" @close="deleteTarget = null" />
    </div>
</template>
