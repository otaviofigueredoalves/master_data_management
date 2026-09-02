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
import JsonViewer from '../../components/ui/JsonViewer.vue';
import RowActions from '../../components/ui/RowActions.vue';

const tenant = useTenantStore();
const toast = useToastStore();

const loading = ref(true);
const rows = ref([]);
const paginator = ref(null);

const filters = ref({ search: '', type: '', source_system: '', is_master: '' });
const debounceTimer = ref(null);

const typeStyle = {
    customer: 'bg-slate-50 text-slate-700 ring-slate-200',
    supplier: 'bg-teal-50 text-teal-700 ring-teal-200',
    product: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    vendor: 'bg-amber-50 text-amber-700 ring-amber-200',
    employee: 'bg-sky-50 text-sky-700 ring-sky-200',
};

const sourceStyle = {
    erp: 'bg-sky-50 text-sky-700 ring-sky-200',
    crm: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    legacy: 'bg-amber-50 text-amber-700 ring-amber-200',
    billing: 'bg-teal-50 text-teal-700 ring-teal-200',
};

function styleFor(map, value) {
    return map[value] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}

const entityTypes = ['customer', 'supplier', 'product', 'vendor', 'employee'];
const sourceSystems = ['erp', 'crm', 'legacy', 'billing'];

async function load(page = 1) {
    if (!tenant.currentTenantId) return;

    loading.value = true;
    try {
        const params = { per_page: 15, page };
        const { search, type, source_system, is_master } = filters.value;
        if (search) params.search = search;
        if (type) params.type = type;
        if (source_system) params.source_system = source_system;
        if (is_master !== '') params.is_master = is_master === '1';

        const payload = await api.get('/mdm-entities', params);
        rows.value = payload.data ?? [];
        paginator.value = payload;
    } catch (error) {
        toast.error(error.message);
    } finally {
        loading.value = false;
    }
}

watch(() => tenant.currentTenantId, load, { immediate: true });

watch(filters, () => {
    clearTimeout(debounceTimer.value);
    debounceTimer.value = window.setTimeout(load, 350);
});

/* ------------------------------ detail drawer ----------------------------- */

const detailOpen = ref(false);
const detailEntity = ref(null);
const detailRelations = ref([]);
const detailLoading = ref(false);

async function openDetail(entity) {
    detailEntity.value = entity;
    detailOpen.value = true;
    detailLoading.value = true;
    detailRelations.value = [];

    try {
        const [fresh, relations] = await Promise.all([
            api.get(`/mdm-entities/${entity.id}`),
            api.get(`/mdm-entities/${entity.id}/relations`).catch(() => []),
        ]);
        detailEntity.value = fresh;
        detailRelations.value = relations ?? [];
    } catch (error) {
        toast.error(error.message);
    } finally {
        detailLoading.value = false;
    }
}

const canNormalize = computed(() => tenant.can('normalize mdm entities'));
const canEdit = computed(() => tenant.can('update mdm entities'));
const canDelete = computed(() => tenant.can('delete mdm entities'));
const canCreate = computed(() => tenant.can('create mdm entities'));

async function normalize(entity) {
    try {
        const updated = await api.post(`/mdm-entities/${entity.id}/normalize`);
        detailEntity.value = updated;
        toast.success('Entidade normalizada e promovida a golden record.');
        load();
    } catch (error) {
        toast.error(error.message);
    }
}

const deleteTarget = ref(null);

async function confirmDelete() {
    try {
        await api.del(`/mdm-entities/${deleteTarget.value.id}`);
        toast.success('Entidade excluída.');
        if (detailEntity.value?.id === deleteTarget.value.id) detailOpen.value = false;
        deleteTarget.value = null;
        load();
    } catch (error) {
        toast.error(error.message);
    }
}

/* ---------------------------------- form --------------------------------- */

const formOpen = ref(false);
const editing = ref(null);
const saving = ref(false);
const formErrors = ref({});
const form = ref({ type: 'customer', source_system: 'erp', external_id: '', data: '' });
const jsonError = ref('');

const dataTemplates = {
    customer: { name: 'ACME Corporation', email: 'contact@acme.com', phone: '+5511999990000', cnpj: '12.345.678/0001-90' },
    supplier: { name: 'Fornecedor Beta Ltda', email: 'compras@beta.com', tax_id: '98.765.432/0001-10' },
    product: { sku: 'PROD-001', name: 'Cadeira Ergonômica', price: 1290.0, category: 'mobiliário' },
};

function useTemplate(type) {
    form.value.data = JSON.stringify(dataTemplates[type] ?? { name: '' }, null, 4);
    jsonError.value = '';
}

function openCreate() {
    editing.value = null;
    form.value = { type: 'customer', source_system: 'erp', external_id: '', data: '' };
    useTemplate('customer');
    formErrors.value = {};
    jsonError.value = '';
    formOpen.value = true;
}

function openEdit(entity) {
    editing.value = entity;
    form.value = {
        type: entity.type,
        source_system: entity.source_system,
        external_id: entity.external_id,
        data: JSON.stringify(entity.data ?? {}, null, 4),
    };
    formErrors.value = {};
    jsonError.value = '';
    formOpen.value = true;
}

function parseData() {
    try {
        const parsed = JSON.parse(form.value.data);
        if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) {
            throw new Error('O payload deve ser um objeto JSON.');
        }
        jsonError.value = '';

        return parsed;
    } catch (error) {
        jsonError.value = error.message;

        return null;
    }
}

async function save() {
    const data = parseData();
    if (!data) return;

    saving.value = true;
    formErrors.value = {};
    try {
        const payload = editing.value
            ? { type: form.value.type, source_system: form.value.source_system, data }
            : { type: form.value.type, source_system: form.value.source_system, external_id: form.value.external_id.trim(), data };

        const endpoint = editing.value ? `/mdm-entities/${editing.value.id}` : '/mdm-entities';

        await (editing.value ? api.put(endpoint, payload) : api.post(endpoint, payload));

        toast.success(editing.value ? 'Entidade atualizada.' : 'Entidade criada.');
        formOpen.value = false;
        load();
    } catch (error) {
        formErrors.value = error.errors ?? {};
        if (!Object.keys(error.errors ?? {}).length) toast.error(error.message);
    } finally {
        saving.value = false;
    }
}

function entityName(entity) {
    return entity.data?.name ?? entity.data?.nome ?? '';
}

function actionsFor(entity) {
    const actions = [{ key: 'view', label: 'Ver detalhes', icon: 'eye' }];
    if (canEdit.value) actions.push({ key: 'edit', label: 'Editar', icon: 'pencil' });
    if (canDelete.value) actions.push({ key: 'delete', label: 'Excluir', icon: 'trash', danger: true });

    return actions;
}

function runEntityAction(entity, action) {
    if (action.key === 'view') openDetail(entity);
    if (action.key === 'edit') openEdit(entity);
    if (action.key === 'delete') deleteTarget.value = entity;
}

</script>

<template>
    <div>
        <PageHeader title="Entidades MDM"
            description="Registros mestres do workspace — versionados e prontos para normalização.">
            <template #icon><Icon name="layers" class="h-5 w-5" /></template>
            <button v-if="canCreate" class="btn btn-primary" @click="openCreate">
                <Icon name="plus" class="h-4 w-4" />
                Nova entidade
            </button>
        </PageHeader>

        <!-- Filters -->
        <div class="card mb-5 p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="relative">
                    <Icon name="search" class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input v-model="filters.search" class="field !pl-9" placeholder="Buscar por external_id ou nome…" />
                </div>
                <select v-model="filters.type" class="field">
                    <option value="">Todos os tipos</option>
                    <option v-for="type in entityTypes" :key="type" :value="type" class="capitalize">{{ type }}</option>
                </select>
                <select v-model="filters.source_system" class="field">
                    <option value="">Todos os sistemas</option>
                    <option v-for="system in sourceSystems" :key="system" :value="system" class="capitalize">{{ system }}</option>
                </select>
                <select v-model="filters.is_master" class="field">
                    <option value="">Todos os status</option>
                    <option value="1">Golden records</option>
                    <option value="0">Não master</option>
                </select>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div v-if="loading" class="divide-y divide-slate-100">
                <div v-for="i in 6" :key="i" class="flex items-center gap-4 px-4 py-4">
                    <div class="skeleton h-9 w-9 rounded-lg" />
                    <div class="flex-1 space-y-2"><div class="skeleton h-3 w-1/3" /><div class="skeleton h-3 w-1/5" /></div>
                    <div class="skeleton h-6 w-20 rounded-md" />
                </div>
            </div>

            <div v-else-if="rows.length" class="overflow-x-auto">
                <table class="table-base min-w-[760px]">
                    <thead>
                        <tr class="bg-slate-50/70">
                            <th>Registro</th>
                            <th>Tipo</th>
                            <th>Sistema</th>
                            <th>Status</th>
                            <th>Versão</th>
                            <th>Atualizado</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entity in rows" :key="entity.id" class="cursor-pointer" @click="openDetail(entity)">
                            <td>
                                <p class="font-mono text-[13px] font-semibold text-slate-800">{{ entity.external_id }}</p>
                                <p v-if="entityName(entity)" class="max-w-52 truncate text-xs text-slate-400">{{ entityName(entity) }}</p>
                            </td>
                            <td>
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-bold capitalize ring-1"
                                    :class="styleFor(typeStyle, entity.type)">{{ entity.type }}</span>
                            </td>
                            <td>
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase ring-1"
                                    :class="styleFor(sourceStyle, entity.source_system)">{{ entity.source_system }}</span>
                            </td>
                            <td>
                                <span v-if="entity.is_master"
                                    class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">
                                    <Icon name="star" class="h-3 w-3" />
                                    master
                                </span>
                                <span v-else class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">
                                    pendente
                                </span>
                            </td>
                            <td><span class="font-mono text-xs font-bold text-slate-600">v{{ entity.version }}</span></td>
                            <td class="text-xs text-slate-500">{{ timeAgo(entity.updated_at) }}</td>
                            <td>
                                <div class="flex justify-end">
                                    <RowActions :items="actionsFor(entity)" @select="(action) => runEntityAction(entity, action)" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else icon="layers" title="Nenhuma entidade encontrada"
                description="Ajuste os filtros ou crie a primeira entidade MDM do workspace.">
                <button v-if="canCreate" class="btn btn-primary" @click="openCreate">
                    <Icon name="plus" class="h-4 w-4" /> Criar entidade
                </button>
            </EmptyState>
        </div>

        <div class="mt-5">
            <PaginationBar :meta="paginator" @page-change="load" />
        </div>

        <!-- Detail drawer -->
        <BaseModal :open="detailOpen" size="lg" @close="detailOpen = false">
            <template v-if="detailEntity">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ detailEntity.external_id }}</h2>
                        <p v-if="entityName(detailEntity)" class="mt-0.5 text-sm text-slate-500">{{ entityName(detailEntity) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="detailEntity.is_master"
                            class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200">
                            <Icon name="star" class="h-3.5 w-3.5" /> Golden record
                        </span>
                        <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200">
                            v{{ detailEntity.version }}
                        </span>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Tipo</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700 capitalize">{{ detailEntity.type }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Sistema</p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-700">{{ detailEntity.source_system }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Criado por</p>
                        <p class="mt-0.5 truncate text-sm font-semibold text-slate-700">{{ detailEntity.created_by ? detailEntity.createdBy?.name : '—' }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Criado em</p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-700">{{ formatDateTime(detailEntity.created_at) }}</p>
                    </div>
                </div>

                <div v-if="detailRelations.length" class="mt-4">
                    <p class="label">Relacionamentos ({{ detailRelations.length }})</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="relation in detailRelations" :key="relation.id"
                            class="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            {{ relation.relation_type }} → #{{ relation.parent_id === detailEntity.id ? relation.child_id : relation.parent_id }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 space-y-5">
                    <div>
                        <p class="label">Dados fonte <span class="font-normal text-slate-400">(payload original)</span></p>
                        <div class="max-h-60 overflow-y-auto rounded-md border border-slate-200 bg-slate-50/60 p-3">
                            <JsonViewer :value="detailEntity.data" root />
                        </div>
                    </div>
                    <div v-if="detailEntity.normalized_data">
                        <p class="label">Dados normalizados <span class="font-normal text-slate-400">(golden record)</span></p>
                        <div class="max-h-60 overflow-y-auto rounded-md border border-emerald-200 bg-emerald-50/40 p-3">
                            <JsonViewer :value="detailEntity.normalized_data" root />
                        </div>
                    </div>
                    <div v-else-if="canNormalize"
                        class="rounded-md border border-dashed border-amber-300 bg-amber-50/60 p-4">
                        <p class="text-sm font-semibold text-amber-800">Registro ainda não normalizado</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-700">
                            A normalização aplica regras de domínio (e-mails em minúsculas, telefones só com dígitos,
                            chaves em snake_case) e promove este registro a golden record.
                        </p>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex flex-wrap items-center gap-2">
                    <button v-if="canDelete && detailEntity" class="btn btn-danger-secondary" @click="deleteTarget = detailEntity; detailOpen = false">
                        <Icon name="trash" class="h-4 w-4" /> Excluir
                    </button>
                    <div class="flex-1" />
                    <button v-if="canNormalize && detailEntity" class="btn" :class="detailEntity?.is_master ? 'btn-secondary' : 'btn-primary'"
                        :disabled="detailLoading" @click="normalize(detailEntity)">
                        <Icon name="sparkles" class="h-4 w-4" />
                        {{ detailEntity?.is_master ? 'Re-normalizar' : 'Normalizar e promover' }}
                    </button>
                    <button v-if="canEdit && detailEntity" class="btn btn-secondary" @click="openEdit(detailEntity); detailOpen = false">
                        <Icon name="pencil" class="h-4 w-4" /> Editar
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- Create/edit form -->
        <BaseModal :open="formOpen" size="lg" @close="formOpen = false">
            <template v-if="editing">
                <h2 class="text-lg font-bold text-slate-900">Editar entidade</h2>
                <p class="mt-0.5 text-sm text-slate-500">A versão será incrementada automaticamente a cada salvamento.</p>
            </template>
            <template v-else>
                <h2 class="text-lg font-bold text-slate-900">Nova entidade MDM</h2>
                <p class="mt-0.5 text-sm text-slate-500">Cada entidade representa um registro vindo de um sistema de origem.</p>
            </template>

            <form class="mt-6 space-y-4" @submit.prevent="save">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label" for="entity-type">Tipo</label>
                        <input id="entity-type" v-model="form.type" class="field" list="type-suggestions" required />
                        <datalist id="type-suggestions">
                            <option v-for="type in entityTypes" :key="type" :value="type" />
                        </datalist>
                    </div>
                    <div>
                        <label class="label" for="entity-source">Sistema de origem</label>
                        <input id="entity-source" v-model="form.source_system" class="field" list="source-suggestions" required />
                        <datalist id="source-suggestions">
                            <option v-for="system in sourceSystems" :key="system" :value="system" />
                        </datalist>
                    </div>
                    <div>
                        <label class="label" for="entity-external">External ID</label>
                        <input id="entity-external" v-model="form.external_id" class="field" :disabled="Boolean(editing)"
                            :class="editing ? 'cursor-not-allowed bg-slate-100 opacity-70' : ''"
                            placeholder="CUST-1001" :required="!editing" />
                        <p v-if="editing" class="mt-1 text-[11px] text-slate-400">Não editável — identifica o registro na origem.</p>
                        <p v-if="formErrors.external_id" class="field-error-text">{{ formErrors.external_id[0] }}</p>
                    </div>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label class="label !mb-0" for="entity-data">Payload de dados (JSON)</label>
                        <div class="flex gap-1.5">
                            <button v-for="(_, key) in dataTemplates" :key="key" type="button"
                                class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-bold text-slate-500 capitalize transition hover:bg-slate-200"
                                @click="useTemplate(key)">
                                {{ key }}
                            </button>
                        </div>
                    </div>
                    <textarea id="entity-data" v-model="form.data" rows="8" spellcheck="false"
                        class="field !font-mono !text-[13px]" :class="jsonError ? '!border-red-400' : ''"></textarea>
                    <p v-if="jsonError" class="field-error-text">JSON inválido: {{ jsonError }}</p>
                    <p v-else class="mt-1 text-[11px] text-slate-400">
                        Use <code class="font-semibold">nome</code> para que o nome apareça na listagem.
                    </p>
                </div>
            </form>

            <template #footer>
                <button class="btn btn-secondary" @click="formOpen = false">Cancelar</button>
                <button class="btn btn-primary" :disabled="saving" @click="save">
                    <Icon v-if="saving" name="refresh" class="h-4 w-4 animate-spin" />
                    {{ editing ? 'Salvar alterações' : 'Criar entidade' }}
                </button>
            </template>
        </BaseModal>

        <ConfirmDialog :open="Boolean(deleteTarget)" title="Excluir entidade?"
            :message="`A entidade ${deleteTarget?.external_id ?? ''} será removida permanentemente (auditada em entity.deleted).`"
            confirm-text="Excluir" danger :loading="false" @confirm="confirmDelete" @close="deleteTarget = null" />
    </div>
</template>
