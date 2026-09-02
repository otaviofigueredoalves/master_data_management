<script setup>
import { computed, ref, watch } from 'vue';
import { api } from '../../api/http';
import { useAuthStore } from '../../stores/auth';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import { formatDateTime, timeAgo } from '../../utils/format';
import { ROLE_DESCRIPTIONS, roleBadgeStyle, roleLabel } from '../../utils/rbac';
import PageHeader from '../../components/ui/PageHeader.vue';
import Icon from '../../components/ui/Icon.vue';
import Avatar from '../../components/ui/Avatar.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import PaginationBar from '../../components/ui/PaginationBar.vue';
import EmptyState from '../../components/ui/EmptyState.vue';

const auth = useAuthStore();
const tenant = useTenantStore();
const toast = useToastStore();

const loading = ref(true);
const members = ref([]);
const paginator = ref(null);
const invites = ref([]);
const invitesLoading = ref(false);

const canManageUsers = computed(() => tenant.can('create users') || tenant.can('invite users'));
const canUpdateRole = computed(() => tenant.can('update users'));
const canRemoveUser = computed(() => tenant.can('delete users'));

async function load(page = 1) {
    if (!tenant.currentTenantId) return;

    loading.value = true;
    try {
        const payload = await api.get('/users', { per_page: 15, page });
        members.value = payload.data ?? [];
        paginator.value = payload;
    } catch (error) {
        toast.error(error.message);
    } finally {
        loading.value = false;
    }
}

async function loadInvites() {
    if (!tenant.currentTenantId || !canManageUsers.value) return;

    invitesLoading.value = true;
    try {
        const payload = await api.get('/invitations', { per_page: 50 });
        invites.value = payload.data ?? [];
    } catch {
        /* ignore */
    } finally {
        invitesLoading.value = false;
    }
}

watch(() => tenant.currentTenantId, () => {
    load();
    loadInvites();
}, { immediate: true });

/* ------------------------------- add member ------------------------------ */

const mode = ref('invite'); // invite | direct
const addOpen = ref(false);
const saving = ref(false);
const formErrors = ref({});
const form = ref({ email: '', name: '', password: '', role: 'user', send_invite: true });
const inviteResult = ref(null);

function openAdd() {
    form.value = { email: '', name: '', password: '', role: 'user', send_invite: true };
    formErrors.value = {};
    inviteResult.value = null;
    addOpen.value = true;
}

async function saveMember() {
    saving.value = true;
    formErrors.value = {};
    try {
        const payload = {
            email: form.value.email.trim(),
            role: form.value.role,
            ...(mode.value === 'direct' ? { name: form.value.name.trim(), password: form.value.password } : { send_invite: true }),
        };

        if (mode.value === 'invite') {
            const result = await api.post('/invitations', { email: payload.email, role: payload.role });
            inviteResult.value = result;
            toast.success('Convite criado.');
        } else {
            await api.post('/users', payload);
            toast.success('Membro adicionado ao workspace.');
            addOpen.value = false;
        }

        load();
        loadInvites();
    } catch (error) {
        formErrors.value = error.errors ?? {};
        if (!Object.keys(error.errors ?? {}).length) toast.error(error.message);
    } finally {
        saving.value = false;
    }
}

/* ----------------------------- role change ------------------------------- */

const roleTarget = ref(null);
const savingRole = ref(false);

async function changeRole(member, roleName) {
    if (!member || !roleName || roleName === member.role?.name) return;

    savingRole.value = true;
    try {
        await api.put(`/users/${member.user_id}`, { role: roleName });
        toast.success(`Papel de ${member.user?.name ?? 'membro'} atualizado.`);
        load();
    } catch (error) {
        toast.error(error.message);
    } finally {
        savingRole.value = false;
        roleTarget.value = null;
    }
}

const removeTarget = ref(null);

async function confirmRemove() {
    try {
        await api.del(`/users/${removeTarget.value.user_id}`);
        toast.success('Acesso do membro revogado neste workspace.');
        removeTarget.value = null;
        load();
    } catch (error) {
        toast.error(error.message);
    }
}

function copyInviteUrl() {
    const url = inviteResult.value?.accept_url;
    if (!url) return;
    navigator.clipboard?.writeText(url).then(() => toast.success('Link copiado.')).catch(() => toast.error('Não foi possível copiar.'));
}
</script>

<template>
    <div>
        <PageHeader title="Membros" description="Pessoas com acesso ao workspace e seus papéis (RBAC).">
            <template #icon><Icon name="users" class="h-5 w-5" /></template>
            <button v-if="canManageUsers" class="btn btn-primary" @click="openAdd">
                <Icon name="plus" class="h-4 w-4" />
                Adicionar membro
            </button>
        </PageHeader>

        <div class="grid gap-4">
            <!-- Members -->
            <div class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Membros ativos</h2>
                        <p class="text-xs text-slate-400">Papel definido pela associação tenant_users.role_id</p>
                    </div>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">
                        {{ paginator?.total ?? members.length }}
                    </span>
                </div>

                <div v-if="loading" class="divide-y divide-slate-100">
                    <div v-for="i in 4" :key="i" class="flex items-center gap-4 px-4 py-4">
                        <div class="skeleton h-9 w-9 rounded-md" />
                        <div class="flex-1 space-y-2"><div class="skeleton h-3 w-1/4" /><div class="skeleton h-3 w-1/6" /></div>
                    </div>
                </div>

                <div v-else-if="members.length" class="divide-y divide-slate-100">
                    <div v-for="member in members" :key="member.id" class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                        <Avatar :name="member.user?.name ?? member.user?.email ?? '?'" size="md" />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                <span class="truncate">{{ member.user?.name }}</span>
                                <span v-if="auth.user?.id === member.user_id"
                                    class="rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 ring-1 ring-slate-200">
                                    você
                                </span>
                            </p>
                            <p class="truncate text-xs text-slate-400">{{ member.user?.email }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <select
                                v-if="canUpdateRole && auth.user?.id !== member.user_id"
                                class="cursor-pointer rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-semibold text-slate-600 focus:border-slate-500 focus:outline-none"
                                :disabled="savingRole && roleTarget === member.user_id"
                                @change="changeRole(member, $event.target.value)">
                                <option v-for="r in ['admin', 'manager', 'user']" :key="r" :value="r"
                                    :selected="member.role?.name === r">{{ roleLabel(r) }}</option>
                            </select>
                            <span v-else class="rounded-md px-2.5 py-1 text-xs font-bold ring-1"
                                :class="roleBadgeStyle(member.role?.name)">
                                {{ roleLabel(member.role?.name) }}
                            </span>

                            <button v-if="canRemoveUser && auth.user?.id !== member.user_id"
                                class="icon-btn !text-red-400 hover:!bg-red-50 hover:!text-red-600" title="Remover acesso"
                                @click="removeTarget = member">
                                <Icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <EmptyState v-else icon="users" title="Nenhum membro" description="Adicione pessoas ao workspace." />
            </div>

            <!-- Invitations -->
            <div v-if="canManageUsers" class="card overflow-hidden">
                <div class="card-header">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Convites pendentes</h2>
                        <p class="text-xs text-slate-400">Convites expiram após 7 dias por padrão</p>
                    </div>
                    <button class="btn btn-secondary btn-sm" @click="loadInvites">
                        <Icon name="refresh" class="h-3.5 w-3.5" :class="invitesLoading ? 'animate-spin' : ''" />
                    </button>
                </div>

                <div v-if="invites.length" class="divide-y divide-slate-100">
                    <div v-for="invite in invites" :key="invite.id"
                        class="flex flex-wrap items-center gap-3 px-4 py-2.5">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-md bg-slate-50 text-slate-600">
                            <Icon name="mail" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ invite.email }}</p>
                            <p class="text-[11px] text-slate-400">
                                Papel: <span class="font-semibold capitalize">{{ invite.roles?.[0] }}</span>
                                <span v-if="invite.accepted_at"> · aceito {{ timeAgo(invite.accepted_at) }}</span>
                                <span v-else> · expira {{ formatDateTime(invite.expires_at) }}</span>
                            </p>
                        </div>
                        <span v-if="invite.accepted_at"
                            class="rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-600 ring-1 ring-emerald-200">
                            aceito
                        </span>
                        <span v-else
                            class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-600 ring-1 ring-amber-200">
                            pendente
                        </span>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-sm text-slate-400">Nenhum convite no momento.</div>
            </div>
        </div>

        <div class="mt-5">
            <PaginationBar :meta="paginator" @page-change="load" />
        </div>

        <!-- Add member modal -->
        <BaseModal :open="addOpen" size="md" @close="addOpen = false">
            <h2 class="text-lg font-bold text-slate-900">Adicionar membro</h2>

            <div class="mt-4 grid grid-cols-2 gap-1 rounded-md bg-slate-100 p-1">
                <button type="button" class="rounded-lg py-2 text-sm font-semibold transition"
                    :class="mode === 'invite' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                    @click="mode = 'invite'; inviteResult = null">
                    Convidar por e-mail
                </button>
                <button type="button" class="rounded-lg py-2 text-sm font-semibold transition"
                    :class="mode === 'direct' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                    @click="mode = 'direct'">
                    Criar com senha
                </button>
            </div>

            <form class="mt-5 space-y-4" @submit.prevent="saveMember">
                <div v-if="inviteResult" class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <p class="text-sm font-semibold text-emerald-800">Convite criado! Compartilhe o link:</p>
                    <div class="mt-2 flex items-center gap-2">
                        <input :value="inviteResult.accept_url" readonly class="field !py-1.5 !text-xs" />
                        <button type="button" class="btn btn-secondary btn-sm shrink-0" @click="copyInviteUrl">
                            <Icon name="copy" class="h-3.5 w-3.5" /> Copiar
                        </button>
                    </div>
                </div>

                <div>
                    <label class="label" for="member-email">E-mail</label>
                    <input id="member-email" v-model="form.email" type="email" class="field"
                        placeholder="pessoa@empresa.com" required />
                    <p v-if="formErrors.email" class="field-error-text">{{ formErrors.email[0] }}</p>
                </div>

                <template v-if="mode === 'direct'">
                    <div>
                        <label class="label" for="member-name">Nome</label>
                        <input id="member-name" v-model="form.name" type="text" class="field" placeholder="Nome completo" />
                        <p v-if="formErrors.name" class="field-error-text">{{ formErrors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="member-password">Senha inicial</label>
                        <input id="member-password" v-model="form.password" type="password" autocomplete="new-password"
                            class="field" placeholder="Mínimo de 8 caracteres" />
                        <p v-if="formErrors.password" class="field-error-text">{{ formErrors.password[0] }}</p>
                    </div>
                </template>
                <p v-else class="text-xs leading-relaxed text-slate-400">
                    O convidado receberá um link para criar/confirmar a conta e aceitar o convite.
                </p>

                <div>
                    <label class="label" for="member-role">Papel</label>
                    <select id="member-role" v-model="form.role" class="field">
                        <option v-for="r in ['admin', 'manager', 'user']" :key="r" :value="r">{{ roleLabel(r) }}</option>
                    </select>
                    <p class="mt-1.5 text-xs text-slate-400">{{ ROLE_DESCRIPTIONS[form.role] }}</p>
                </div>
            </form>

            <template #footer>
                <button class="btn btn-secondary" @click="addOpen = false">Cancelar</button>
                <button class="btn btn-primary" :disabled="saving || !form.email.trim()" @click="saveMember">
                    <Icon v-if="saving" name="refresh" class="h-4 w-4 animate-spin" />
                    {{ mode === 'invite' ? 'Criar convite' : 'Adicionar membro' }}
                </button>
            </template>
        </BaseModal>

        <ConfirmDialog :open="Boolean(removeTarget)" title="Remover acesso do membro?"
            :message="`${removeTarget?.user?.name ?? ''} perderá o acesso a este workspace. O cadastro global da pessoa é mantido.`"
            confirm-text="Remover acesso" danger @confirm="confirmRemove" @close="removeTarget = null" />
    </div>
</template>
