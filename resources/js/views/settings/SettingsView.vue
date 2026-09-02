<script setup>
import { computed, onMounted, ref } from 'vue';
import { api } from '../../api/http';
import { useAuthStore } from '../../stores/auth';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import { formatDateTime, timeAgo } from '../../utils/format';
import { PERMISSION_LABELS, ROLE_DESCRIPTIONS, roleBadgeStyle, roleLabel } from '../../utils/rbac';
import PageHeader from '../../components/ui/PageHeader.vue';
import Icon from '../../components/ui/Icon.vue';
import Avatar from '../../components/ui/Avatar.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';

const auth = useAuthStore();
const tenant = useTenantStore();
const toast = useToastStore();

/* --------------------------------- profile -------------------------------- */

const savingProfile = ref(false);
const profile = ref({ name: '', email: '', timezone: 'UTC', locale: 'pt-BR' });
const profileErrors = ref({});

const timezones = [
    'UTC',
    'America/Sao_Paulo',
    'America/New_York',
    'America/Chicago',
    'America/Los_Angeles',
    'Europe/Lisbon',
    'Europe/London',
    'Europe/Madrid',
    'Africa/Luanda',
    'Asia/Tokyo',
];

const initialized = ref(false);

onMounted(() => {
    if (auth.user) {
        profile.value = {
            name: auth.user.name ?? '',
            email: auth.user.email ?? '',
            timezone: auth.user.timezone || 'UTC',
            locale: auth.user.locale || 'pt-BR',
        };
    }
    initialized.value = true;
});

async function saveProfile() {
    savingProfile.value = true;
    profileErrors.value = {};
    try {
        await auth.updateProfile({
            name: profile.value.name.trim(),
            email: profile.value.email.trim(),
            timezone: profile.value.timezone,
            locale: profile.value.locale,
        });
        toast.success('Perfil atualizado.');
    } catch (error) {
        profileErrors.value = error.errors ?? {};
        if (!Object.keys(error.errors ?? {}).length) toast.error(error.message);
    } finally {
        savingProfile.value = false;
    }
}

/* ---------------------------------- tokens -------------------------------- */

const tokens = ref([]);
const tokensLoading = ref(true);
const tokenOpen = ref(false);
const creatingToken = ref(false);
const newTokenName = ref('');
const freshToken = ref(null);

async function loadTokens() {
    tokensLoading.value = true;
    try {
        tokens.value = await api.get('/tokens');
    } catch (error) {
        toast.error(error.message);
    } finally {
        tokensLoading.value = false;
    }
}

async function createToken() {
    creatingToken.value = true;
    try {
        const payload = await api.post('/tokens', { name: newTokenName.value.trim() });
        freshToken.value = payload.token;
        newTokenName.value = '';
        loadTokens();
    } catch (error) {
        toast.error(error.message);
    } finally {
        creatingToken.value = false;
    }
}

const revokeTarget = ref(null);

async function confirmRevoke() {
    try {
        await api.del(`/tokens/${revokeTarget.value.id}`);
        toast.success('Token revogado.');
        revokeTarget.value = null;
        loadTokens();
    } catch (error) {
        toast.error(error.message);
    }
}

function copyToken() {
    navigator.clipboard?.writeText(freshToken.value)
        .then(() => toast.success('Token copiado.'))
        .catch(() => toast.error('Não foi possível copiar.'));
}

/* ------------------------------ role catalog ------------------------------ */

const sortedRoles = computed(() => [...tenant.roles].sort((a, b) => ['admin', 'manager', 'user'].indexOf(a.name) - ['admin', 'manager', 'user'].indexOf(b.name)));

const currentRoleName = computed(() => tenant.currentRoleName);
</script>

<template>
    <div>
        <PageHeader title="Configurações" description="Sua conta, tokens de API e o catálogo de papéis do workspace.">
            <template #icon><Icon name="sliders" class="h-5 w-5" /></template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-2">
            <!-- Profile -->
            <section class="card p-4">
                <div class="flex items-center gap-3">
                    <Avatar :name="auth.user?.name ?? '?'" size="lg" />
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Perfil</h2>
                        <p class="text-xs text-slate-400">Nome, e-mail e preferências da sua conta global</p>
                    </div>
                </div>

                <form v-if="initialized" class="mt-6 space-y-4" @submit.prevent="saveProfile">
                    <div>
                        <label class="label" for="profile-name">Nome</label>
                        <input id="profile-name" v-model="profile.name" class="field" required />
                        <p v-if="profileErrors.name" class="field-error-text">{{ profileErrors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="label" for="profile-email">E-mail</label>
                        <input id="profile-email" v-model="profile.email" type="email" class="field" required />
                        <p v-if="profileErrors.email" class="field-error-text">{{ profileErrors.email[0] }}</p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="profile-timezone">Fuso horário</label>
                            <select id="profile-timezone" v-model="profile.timezone" class="field">
                                <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label" for="profile-locale">Idioma</label>
                            <select id="profile-locale" v-model="profile.locale" class="field">
                                <option value="pt-BR">Português (Brasil)</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary" :disabled="savingProfile">
                        <Icon v-if="savingProfile" name="refresh" class="h-4 w-4 animate-spin" />
                        Salvar perfil
                    </button>
                </form>
            </section>

            <!-- API tokens -->
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Tokens de API</h2>
                        <p class="text-xs text-slate-400">Access tokens Sanctum por dispositivo — revogue quando quiser</p>
                    </div>
                    <button class="btn btn-primary btn-sm" @click="tokenOpen = true">
                        <Icon name="plus" class="h-3.5 w-3.5" /> Novo token
                    </button>
                </div>

                <div v-if="tokensLoading" class="space-y-3 p-4">
                    <div v-for="i in 3" :key="i" class="skeleton h-12 w-full" />
                </div>

                <div v-else-if="tokens.length" class="divide-y divide-slate-100">
                    <div v-for="token in tokens" :key="token.id" class="flex items-center gap-3 px-4 py-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500">
                            <Icon name="key" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ token.name }}</p>
                            <p class="text-[11px] text-slate-400">
                                criado {{ formatDateTime(token.created_at) }}
                                <template v-if="token.last_used_at"> · usado {{ timeAgo(token.last_used_at) }}</template>
                            </p>
                        </div>
                        <button class="icon-btn !text-red-400 hover:!bg-red-50 hover:!text-red-600" title="Revogar token"
                            @click="revokeTarget = token">
                            <Icon name="trash" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <p v-else class="px-4 py-8 text-center text-sm text-slate-400">Nenhum token criado ainda.</p>
            </section>
        </div>

        <!-- Role catalog -->
        <section class="card mt-6">
            <div class="card-header">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Catálogo de papéis do workspace</h2>
                    <p class="text-xs text-slate-400">RBAC por associação — o papel determina as permissões exibidas na interface</p>
                </div>
                <span v-if="currentRoleName" class="rounded-md px-2.5 py-1 text-xs font-bold ring-1"
                    :class="roleBadgeStyle(currentRoleName)">
                    seu papel: {{ roleLabel(currentRoleName) }}
                </span>
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-3">
                <div v-for="role in sortedRoles" :key="role.id"
                    class="rounded-lg border p-4 transition"
                    :class="role.name === currentRoleName ? 'border-slate-300 bg-slate-50/40 ring-2 ring-slate-200' : 'border-slate-200'">
                    <div class="flex items-center justify-between">
                        <span class="rounded-md px-2.5 py-1 text-xs font-bold ring-1" :class="roleBadgeStyle(role.name)">
                            {{ roleLabel(role.name) }}
                        </span>
                        <Icon v-if="role.name === currentRoleName" name="check-circle" class="h-5 w-5 text-slate-600" />
                    </div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-500">{{ ROLE_DESCRIPTIONS[role.name] }}</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span v-for="permission in role.permissions ?? []" :key="permission.name"
                            class="rounded-md bg-white px-2 py-1 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200">
                            {{ PERMISSION_LABELS[permission.name] ?? permission.name }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create token modal -->
        <BaseModal :open="tokenOpen" size="sm" title="Criar token de API" @close="tokenOpen = false">
            <div v-if="!freshToken">
                <label class="label" for="token-name">Nome do token</label>
                <input id="token-name" v-model="newTokenName" class="field" placeholder="Ex.: Postman, deploy, notebook"
                    @keyup.enter="createToken" />
                <p class="mt-2 text-xs text-slate-400">
                    O token é exibido apenas uma vez. Guarde-o em local seguro.
                </p>
            </div>
            <div v-else class="rounded-md border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-bold text-emerald-700 uppercase">Token criado — copie agora</p>
                <div class="mt-2 flex items-center gap-2">
                    <input :value="freshToken" readonly class="field !py-1.5 !font-mono !text-xs" />
                    <button class="btn btn-secondary btn-sm shrink-0" @click="copyToken">
                        <Icon name="copy" class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
            <template #footer>
                <button class="btn btn-secondary" @click="tokenOpen = false; freshToken = null">Fechar</button>
                <button v-if="!freshToken" class="btn btn-primary" :disabled="creatingToken || !newTokenName.trim()"
                    @click="createToken">
                    <Icon v-if="creatingToken" name="refresh" class="h-4 w-4 animate-spin" />
                    Gerar token
                </button>
            </template>
        </BaseModal>

        <ConfirmDialog :open="Boolean(revokeTarget)" title="Revogar token?"
            :message="`O token ${revokeTarget?.name ?? ''} deixará de funcionar imediatamente em todos os clientes.`"
            confirm-text="Revogar" danger @confirm="confirmRevoke" @close="revokeTarget = null" />
    </div>
</template>
