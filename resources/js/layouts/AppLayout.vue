<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useTenantStore } from '../stores/tenant';
import { useToastStore } from '../stores/toast';
import SidebarNav from '../components/nav/SidebarNav.vue';
import Icon from '../components/ui/Icon.vue';
import BaseModal from '../components/ui/BaseModal.vue';

const auth = useAuthStore();
const tenant = useTenantStore();
const toast = useToastStore();

const mobileOpen = ref(false);
const tenantMenuOpen = ref(false);
const userMenuOpen = ref(false);
const createOpen = ref(false);
const creating = ref(false);
const newTenant = ref({ name: '', slug: '' });

const currentTenant = computed(() => tenant.currentTenant);

function slugify(value) {
    return value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

function watchSlug() {
    if (!newTenant.value.slug) {
        newTenant.value.slug = slugify(newTenant.value.name);
    }
}

async function switchTenant(id) {
    await tenant.setCurrent(id);
    tenantMenuOpen.value = false;
    toast.success('Workspace alterado.');
}

async function createWorkspace() {
    creating.value = true;
    try {
        await tenant.createTenant({
            name: newTenant.value.name.trim(),
            slug: slugify(newTenant.value.slug || newTenant.value.name),
        });
        createOpen.value = false;
        newTenant.value = { name: '', slug: '' };
        toast.success('Workspace criado com sucesso.');
    } catch (error) {
        toast.error(error.message);
    } finally {
        creating.value = false;
    }
}

function closeFloatingMenus() {
    tenantMenuOpen.value = false;
    userMenuOpen.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape') closeFloatingMenus();
}

onMounted(() => {
    document.addEventListener('click', closeFloatingMenus);
    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeFloatingMenus);
    window.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150" leave-to-class="opacity-0">
            <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="mobileOpen = false" />
        </Transition>

        <!-- Mobile sidebar -->
        <Transition enter-active-class="transition-transform duration-200 ease-out" enter-from-class="-translate-x-full"
            leave-active-class="transition-transform duration-150 ease-in" leave-to-class="-translate-x-full">
            <aside v-if="mobileOpen" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-sm lg:hidden">
                <SidebarNav @navigate="mobileOpen = false" />
            </aside>
        </Transition>

        <!-- Desktop sidebar -->
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-slate-200 bg-white lg:block">
            <SidebarNav />
        </aside>

        <div class="lg:pl-64">
            <header
                class="sticky top-0 z-20 flex h-14 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 sm:px-5">
                <button class="icon-btn lg:hidden" aria-label="Abrir menu" @click="mobileOpen = true">
                    <Icon name="menu" class="h-5 w-5" />
                </button>

                <!-- Tenant switcher -->
                <div class="relative min-w-0" @click.stop>
                    <button
                        class="flex max-w-full cursor-pointer items-center gap-2.5 rounded-md border border-slate-200 bg-white px-3 py-1.5 transition hover:border-slate-300 hover:bg-slate-50"
                        aria-haspopup="menu" :aria-expanded="tenantMenuOpen" @click="tenantMenuOpen = !tenantMenuOpen">
                        <span
                            class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-900 text-white">
                            <Icon name="building" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 text-left">
                            <span class="block max-w-40 truncate text-sm leading-tight font-semibold text-slate-900 sm:max-w-64">
                                {{ currentTenant?.name ?? 'Workspace' }}
                            </span>
                            <span class="block text-[11px] leading-tight text-slate-400">
                                {{ tenant.tenants.length }} {{ tenant.tenants.length === 1 ? 'workspace' : 'workspaces' }}
                            </span>
                        </span>
                        <Icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400" />
                    </button>

                    <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="translate-y-1 opacity-0"
                        leave-active-class="transition duration-100 ease-in" leave-to-class="translate-y-1 opacity-0">
                        <div v-if="tenantMenuOpen"
                            class="absolute top-full left-0 z-30 mt-2 w-72 overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
                            <p class="px-4 pt-3 pb-1 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                                Seus workspaces
                            </p>
                            <div class="max-h-72 overflow-y-auto p-1.5">
                                <button v-for="item in tenant.tenants" :key="item.id"
                                    class="flex w-full items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-left transition hover:bg-slate-50"
                                    @click="switchTenant(item.id)">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-slate-800">{{ item.name }}</span>
                                        <span class="text-[11px] text-slate-400">{{ item.slug }}</span>
                                    </span>
                                    <span v-if="item.id === tenant.currentTenantId"
                                        class="grid h-5 w-5 shrink-0 place-items-center rounded-md bg-slate-100 text-slate-600">
                                        <Icon name="check" class="h-3 w-3" />
                                    </span>
                                </button>
                            </div>
                            <div class="border-t border-slate-100 p-1.5">
                                <button
                                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                                    @click="createOpen = true; tenantMenuOpen = false">
                                    <Icon name="plus" class="h-4 w-4" />
                                    Criar novo workspace
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>

                <div class="flex items-center gap-1.5">
                    <a href="/docs" target="_blank"
                        class="hidden items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 sm:flex">
                        <Icon name="external" class="h-4 w-4" />
                        API Docs
                    </a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-4 sm:px-4 lg:px-8">
                <router-view />
            </main>
        </div>

        <!-- Create workspace modal -->
        <BaseModal :open="createOpen" title="Criar novo workspace"
            description="Cada workspace isola entidades, integrações e membros da sua organização." @close="createOpen = false">
            <div class="space-y-4">
                <div>
                    <label class="label" for="workspace-name">Nome da organização</label>
                    <input id="workspace-name" v-model="newTenant.name" class="field" placeholder="Ex.: Acme Corporation"
                        @input="watchSlug" />
                </div>
                <div>
                    <label class="label" for="workspace-slug">Slug</label>
                    <input id="workspace-slug" v-model="newTenant.slug" class="field font-mono" placeholder="acme-corporation" />
                    <p class="mt-1 text-xs text-slate-400">Identificador único, usado nas URLs do workspace.</p>
                </div>
            </div>
            <template #footer>
                <button class="btn btn-secondary" @click="createOpen = false">Cancelar</button>
                <button class="btn btn-primary" :disabled="creating || !newTenant.name.trim()" @click="createWorkspace">
                    <Icon v-if="creating" name="refresh" class="h-4 w-4 animate-spin" />
                    Criar workspace
                </button>
            </template>
        </BaseModal>
    </div>
</template>
