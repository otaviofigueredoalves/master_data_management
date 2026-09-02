<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import AppLogo from '../../components/ui/AppLogo.vue';
import Icon from '../../components/ui/Icon.vue';

const router = useRouter();
const auth = useAuthStore();
const toast = useToastStore();

const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const submitting = ref(false);
const formError = ref('');
const fieldErrors = ref({});

async function submit() {
    submitting.value = true;
    formError.value = '';
    fieldErrors.value = {};

    if (form.value.password !== form.value.password_confirmation) {
        formError.value = 'As senhas não conferem.';
        submitting.value = false;

        return;
    }

    try {
        await auth.register(form.value.name.trim(), form.value.email.trim(), form.value.password);
        toast.success('Conta criada com sucesso. Agora crie seu primeiro workspace.');
        router.push({ name: 'workspace.setup' });
    } catch (error) {
        formError.value = error.message;
        fieldErrors.value = error.errors ?? {};
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <AppLogo />
            </div>

            <div class="card p-8">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Criar conta</h1>
                <p class="mt-1.5 text-sm text-slate-500">Comece grátis e crie seu primeiro workspace em seguida.</p>

                <form class="mt-7 space-y-5" @submit.prevent="submit">
                    <div v-if="formError" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                        {{ formError }}
                    </div>

                    <div>
                        <label class="label" for="name">Nome</label>
                        <input id="name" v-model="form.name" type="text" autocomplete="name" class="field"
                            placeholder="Seu nome completo" required />
                        <p v-if="fieldErrors.name" class="field-error-text">{{ fieldErrors.name[0] }}</p>
                    </div>

                    <div>
                        <label class="label" for="email">E-mail</label>
                        <input id="email" v-model="form.email" type="email" autocomplete="email" class="field"
                            placeholder="voce@empresa.com" required />
                        <p v-if="fieldErrors.email" class="field-error-text">{{ fieldErrors.email[0] }}</p>
                    </div>

                    <div>
                        <label class="label" for="password">Senha</label>
                        <input id="password" v-model="form.password" type="password" autocomplete="new-password"
                            class="field" placeholder="Mínimo de 8 caracteres" required />
                        <p v-if="fieldErrors.password" class="field-error-text">{{ fieldErrors.password[0] }}</p>
                    </div>

                    <div>
                        <label class="label" for="password-confirmation">Confirmar senha</label>
                        <input id="password-confirmation" v-model="form.password_confirmation" type="password"
                            autocomplete="new-password" class="field" placeholder="Repita a senha" required />
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block" :disabled="submitting">
                        <Icon v-if="submitting" name="refresh" class="h-4 w-4 animate-spin" />
                        {{ submitting ? 'Criando conta…' : 'Criar conta' }}
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                Já tem conta?
                <RouterLink :to="{ name: 'login' }" class="font-semibold text-slate-600 hover:text-slate-700">
                    Entrar
                </RouterLink>
            </p>
        </div>
    </div>
</template>
