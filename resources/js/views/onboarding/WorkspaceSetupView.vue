<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useTenantStore } from '../../stores/tenant';
import { useToastStore } from '../../stores/toast';
import AppLogo from '../../components/ui/AppLogo.vue';
import Icon from '../../components/ui/Icon.vue';

const router = useRouter();
const tenant = useTenantStore();
const toast = useToastStore();

const form = ref({ name: '', slug: '' });
const creating = ref(false);
const formError = ref('');

function slugify(value) {
    return value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function watchSlug() {
    if (!form.value.slug) form.value.slug = slugify(form.value.name);
}

async function create() {
    creating.value = true;
    formError.value = '';
    try {
        await tenant.createTenant({
            name: form.value.name.trim(),
            slug: slugify(form.value.slug || form.value.name),
        });
        toast.success('Workspace criado! Você agora é o admin.');
        router.push({ name: 'dashboard' });
    } catch (error) {
        formError.value = error.message;
    } finally {
        creating.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
        <div class="w-full max-w-lg">
            <div class="mb-8 flex justify-center">
                <AppLogo />
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="bg-slate-900 px-8 py-4">
                    <span class="grid h-11 w-11 place-items-center rounded-md bg-white/15 text-white">
                        <Icon name="sparkles" class="h-6 w-6" />
                    </span>
                    <h1 class="mt-4 text-xl font-bold text-white">Crie seu primeiro workspace</h1>
                    <p class="mt-1 text-sm text-slate-100">
                        Um workspace isola entidades, integrações, membros e auditoria da sua organização.
                    </p>
                </div>

                <div class="p-8">
                    <p class="text-sm text-slate-600">
                        Você ainda não pertence a nenhuma organização. Crie uma agora — você se tornará o
                        <strong class="font-semibold">admin</strong> dela. Já foi convidado? Use o link do convite
                        para entrar em um workspace existente.
                    </p>

                    <form class="mt-6 space-y-4" @submit.prevent="create">
                        <div v-if="formError" class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                            {{ formError }}
                        </div>

                        <div>
                            <label class="label" for="tenant-name">Nome da organização</label>
                            <input id="tenant-name" v-model="form.name" class="field" placeholder="Ex.: Acme Corporation"
                                required @input="watchSlug" />
                        </div>
                        <div>
                            <label class="label" for="tenant-slug">Slug</label>
                            <input id="tenant-slug" v-model="form.slug" class="field font-mono"
                                placeholder="acme-corporation" required />
                            <p class="mt-1 text-xs text-slate-400">Identificador único usado nas URLs.</p>
                        </div>
                        <button class="btn btn-primary btn-lg btn-block mt-6" :disabled="creating || !form.name.trim()">
                            <Icon v-if="creating" name="refresh" class="h-4 w-4 animate-spin" />
                            {{ creating ? 'Criando…' : 'Criar workspace' }}
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                Já tem um convite?
                <a href="/docs" class="font-semibold text-slate-600 hover:text-slate-700">Consulte a documentação</a>
                para saber como aceitá-lo.
            </p>
        </div>
    </div>
</template>
