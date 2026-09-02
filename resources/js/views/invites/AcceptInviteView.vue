<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../../api/http';
import { useAuthStore } from '../../stores/auth';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import AppLogo from '../../components/ui/AppLogo.vue';
import Icon from '../../components/ui/Icon.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tenant = useTenantStore();
const toast = useToastStore();

const email = ref('');
const name = ref('');
const password = ref('');
const needAccount = ref(false);
const submitted = ref(false);
const acceptedTenantId = ref(null);
const submitting = ref(false);
const formError = ref('');
const fieldErrors = ref({});

async function submit() {
    submitting.value = true;
    formError.value = '';
    fieldErrors.value = {};

    const payload = { email: email.value.trim() };
    if (needAccount.value) {
        if (name.value.trim()) payload.name = name.value.trim();
        if (password.value) payload.password = password.value;
    }

    try {
        const data = await api.post(`/invitations/${route.params.token}/accept`, payload);

        acceptedTenantId.value = data?.tenant_id ?? null;
        submitted.value = true;

        // If the invitee is the current session user, refresh memberships right away.
        if (auth.token && auth.user?.email?.toLowerCase() === email.value.trim().toLowerCase()) {
            await auth.fetchMe().catch(() => null);
            const ids = auth.tenants.map((item) => item.id);
            if (acceptedTenantId.value && ids.includes(acceptedTenantId.value)) {
                await tenant.loadTenants().catch(() => null);
                await tenant.setCurrent(acceptedTenantId.value);
                tenant.ready = true;
            }
        }
    } catch (error) {
        const message = String(error.message).toLowerCase();

        if (message.includes('crie uma conta') && !needAccount.value) {
            needAccount.value = true;
            formError.value = 'Este e-mail ainda não possui conta. Preencha nome e senha para criá-la e aceitar o convite.';
        } else {
            formError.value = error.message;
            fieldErrors.value = error.errors ?? {};
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <AppLogo />
            </div>

            <div class="card p-8">
                <template v-if="!submitted">
                    <span class="grid h-12 w-12 place-items-center rounded-md bg-slate-50 text-slate-600 ring-1 ring-slate-100">
                        <Icon name="mail" class="h-6 w-6" />
                    </span>
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Você foi convidado</h1>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        Uma organização te convidou para fazer parte do workspace dela. Confirme seu e-mail para aceitar.
                    </p>

                    <form class="mt-7 space-y-5" @submit.prevent="submit">
                        <div v-if="formError"
                            class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                            {{ formError }}
                        </div>

                        <div>
                            <label class="label" for="invite-email">Seu e-mail</label>
                            <input id="invite-email" v-model="email" type="email" autocomplete="email" class="field"
                                placeholder="voce@empresa.com" required />
                            <p v-if="fieldErrors.email" class="field-error-text">{{ fieldErrors.email[0] }}</p>
                        </div>

                        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1"
                            leave-active-class="transition duration-150" leave-to-class="opacity-0">
                            <div v-if="needAccount" class="space-y-5">
                                <div>
                                    <label class="label" for="invite-name">Nome (novo usuário)</label>
                                    <input id="invite-name" v-model="name" type="text" class="field"
                                        placeholder="Seu nome completo" />
                                </div>
                                <div>
                                    <label class="label" for="invite-password">Definir senha</label>
                                    <input id="invite-password" v-model="password" type="password"
                                        autocomplete="new-password" class="field" placeholder="Mínimo de 8 caracteres" />
                                    <p v-if="fieldErrors.password" class="field-error-text">{{ fieldErrors.password[0] }}</p>
                                </div>
                            </div>
                        </Transition>

                        <button class="btn btn-primary btn-lg btn-block" :disabled="submitting || !email.trim()">
                            <Icon v-if="submitting" name="refresh" class="h-4 w-4 animate-spin" />
                            {{ submitting ? 'Aceitando…' : needAccount ? 'Criar conta e aceitar convite' : 'Aceitar convite' }}
                        </button>
                    </form>
                </template>

                <template v-else>
                    <span class="grid h-12 w-12 place-items-center rounded-md bg-emerald-100 text-emerald-600">
                        <Icon name="check-circle" class="h-7 w-7" />
                    </span>
                    <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Convite aceito! 🎉</h1>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                        Você agora faz parte do workspace. Entre na sua conta para começar a trabalhar.
                    </p>
                    <div class="mt-7 flex flex-col gap-2.5">
                        <RouterLink :to="{ name: 'login' }" class="btn btn-primary btn-lg btn-block">
                            Entrar na minha conta
                        </RouterLink>
                        <RouterLink v-if="auth.isAuthenticated" :to="{ name: 'dashboard' }"
                            class="btn btn-secondary btn-lg btn-block">
                            Ir para o app
                        </RouterLink>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
