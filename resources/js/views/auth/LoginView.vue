<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import AppLogo from '../../components/ui/AppLogo.vue';
import Icon from '../../components/ui/Icon.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const toast = useToastStore();

const email = ref('');
const password = ref('');
const code = ref('');
const showCode = ref(false);
const submitting = ref(false);
const formError = ref('');
const fieldErrors = ref({});

const demoAccounts = [
    { label: 'Admin', email: 'demo@mdmsaas.test', className: 'bg-slate-100 text-slate-800 ring-slate-300 hover:bg-slate-200' },
    { label: 'Manager', email: 'manager@mdmsaas.test', className: 'bg-teal-50 text-teal-800 ring-teal-200 hover:bg-teal-100' },
    { label: 'Viewer', email: 'viewer@mdmsaas.test', className: 'bg-slate-50 text-slate-600 ring-slate-300 hover:bg-slate-100' },
];

function fillDemo(account) {
    email.value = account.email;
    password.value = 'password';
    formError.value = '';
    fieldErrors.value = {};
    showCode.value = false;
}

async function submit() {
    submitting.value = true;
    formError.value = '';
    fieldErrors.value = {};

    try {
        await auth.login(email.value.trim(), password.value, code.value.trim() || null);

        const tenants = auth.tenants;
        toast.success('Login realizado com sucesso.');

        const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
            ? route.query.redirect
            : null;

        if (redirect) {
            router.push(redirect);
        } else if (tenants.length > 0) {
            router.push({ name: 'dashboard' });
        } else {
            router.push({ name: 'workspace.setup' });
        }
    } catch (error) {
        const wantsCode = error.status === 422 && String(error.message).toLowerCase().includes('dois fatores');

        if (wantsCode && !showCode.value) {
            showCode.value = true;
            formError.value = 'Autenticação de dois fatores ativada. Digite o código do seu aplicativo.';
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
    <div class="flex min-h-screen">
        <!-- Branding panel -->
        <div class="relative hidden w-[42%] bg-slate-950 lg:block">
            <div class="flex h-full flex-col justify-between p-10">
                <AppLogo dark />

                <div>
                    <div class="border-l-2 border-slate-600 pl-4">
                        <p class="text-lg leading-relaxed font-semibold text-slate-100">
                            “Sem um registro dourado, você não sabe quem é o seu cliente.”
                        </p>
                        <p class="mt-3 text-xs text-slate-500">
                            Normalize, promova a master, audite tudo.
                        </p>
                    </div>

                    <div class="mt-8 space-y-1.5 border-t border-slate-800 pt-4">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Por que MDM?</p>
                        <p class="text-xs text-slate-400">Um workspace isolado por organização</p>
                        <p class="text-xs text-slate-400">RBAC por papel — admin, manager, viewer</p>
                        <p class="text-xs text-slate-400">API REST com tokens e 2FA</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form panel -->
        <div class="flex w-full items-center justify-center bg-slate-100 px-4 py-10 lg:w-[58%]">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <AppLogo />
                </div>

                <h1 class="text-xl font-bold tracking-tight text-slate-900">Bem-vindo de volta</h1>
                <p class="mt-1 text-xs text-slate-500">Entre na sua conta para acessar seus workspaces.</p>

                <form class="mt-6 space-y-4" @submit.prevent="submit">
                    <div v-if="formError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-xs font-medium text-red-700">
                        {{ formError }}
                    </div>

                    <div>
                        <label class="label" for="email">E-mail</label>
                        <input id="email" v-model="email" type="email" autocomplete="username" class="field"
                            :class="fieldErrors.email ? 'border-red-400' : ''" placeholder="voce@empresa.com" required />
                        <p v-if="fieldErrors.email" class="field-error-text">{{ fieldErrors.email[0] }}</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="label !mb-0" for="password">Senha</label>
                            <a href="/forgot-password" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
                                Esqueceu a senha?
                            </a>
                        </div>
                        <input id="password" v-model="password" type="password" autocomplete="current-password"
                            class="field mt-1" :class="fieldErrors.password ? 'border-red-400' : ''"
                            placeholder="••••••••" required />
                        <p v-if="fieldErrors.password" class="field-error-text">{{ fieldErrors.password[0] }}</p>
                    </div>

                    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 -translate-y-1"
                        leave-active-class="transition duration-150" leave-to-class="opacity-0">
                        <div v-if="showCode">
                            <label class="label" for="code">Código de dois fatores</label>
                            <input id="code" v-model="code" inputmode="numeric" autocomplete="one-time-code" class="field"
                                placeholder="000000" />
                            <p v-if="fieldErrors.code" class="field-error-text">{{ fieldErrors.code[0] }}</p>
                        </div>
                    </Transition>

                    <button type="submit" class="btn btn-primary btn-block" :disabled="submitting">
                        <Icon v-if="submitting" name="refresh" class="h-4 w-4 animate-spin" />
                        {{ submitting ? 'Entrando…' : 'Entrar' }}
                    </button>
                </form>

                <div class="mt-7">
                    <div class="flex items-center gap-3">
                        <span class="h-px flex-1 bg-slate-200" />
                        <span class="text-[10px] font-semibold tracking-wider text-slate-400 uppercase">Contas demo</span>
                        <span class="h-px flex-1 bg-slate-200" />
                    </div>
                    <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                        <button v-for="account in demoAccounts" :key="account.label" type="button"
                            class="cursor-pointer rounded px-2.5 py-1 text-[11px] font-semibold ring-1 transition-colors duration-200"
                            :class="account.className" @click="fillDemo(account)">
                            {{ account.label }}
                        </button>
                    </div>
                    <p class="mt-2 text-center text-[11px] text-slate-400">
                        Senha de todas as contas demo: <code class="font-semibold text-slate-500">password</code>
                    </p>
                </div>

                <p class="mt-7 text-center text-xs text-slate-500">
                    Ainda não tem conta?
                    <RouterLink :to="{ name: 'register' }" class="font-semibold text-slate-900 hover:underline">
                        Criar conta
                    </RouterLink>
                </p>
            </div>
        </div>
    </div>
</template>
