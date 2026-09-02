<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import { useTenantStore } from '../../stores/tenant';
import AppLogo from '../ui/AppLogo.vue';
import Icon from '../ui/Icon.vue';
import Avatar from '../ui/Avatar.vue';
import { roleBadgeStyle, roleLabel } from '../../utils/rbac';

const emit = defineEmits(['navigate']);

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tenant = useTenantStore();

const navSections = [
    {
        label: 'Workspace',
        items: [
            { name: 'dashboard', label: 'Dashboard', icon: 'grid' },
            { name: 'entities', label: 'Entidades MDM', icon: 'layers' },
            { name: 'integrations', label: 'Integrações', icon: 'plug' },
            { name: 'sync-jobs', label: 'Sincronizações', icon: 'refresh' },
        ],
    },
    {
        label: 'Governança',
        items: [
            { name: 'members', label: 'Membros', icon: 'users' },
            { name: 'audit', label: 'Auditoria', icon: 'shield' },
        ],
    },
    {
        label: 'Conta',
        items: [{ name: 'settings', label: 'Configurações', icon: 'sliders' }],
    },
];

function isActive(name) {
    return route.name === name;
}

async function logout() {
    tenant.reset();
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex h-14 items-center border-b border-slate-200 px-4">
            <RouterLink to="/" @click="emit('navigate')">
                <AppLogo />
            </RouterLink>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            <div v-for="section in navSections" :key="section.label">
                <p class="px-3 pb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">{{ section.label }}</p>
                <div class="space-y-0.5">
                    <RouterLink
                        v-for="item in section.items"
                        :key="item.name"
                        :to="{ name: item.name }"
                        class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive(item.name)
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        @click="emit('navigate')"
                    >
                        <Icon
                            :name="item.icon"
                            class="h-[18px] w-[18px]"
                            :class="isActive(item.name) ? 'text-slate-900' : 'text-slate-400 group-hover:text-slate-600'"
                        />
                        {{ item.label }}
                    </RouterLink>
                </div>
            </div>

            <div class="pt-2">
                <p class="px-3 pb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">Recursos</p>
                <a
                    href="/docs"
                    target="_blank"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
                >
                    <Icon name="external" class="h-[18px] w-[18px] text-slate-400" />
                    Documentação da API
                </a>
            </div>
        </nav>

        <div class="border-t border-slate-100 p-3">
            <div class="flex items-center gap-3 rounded-md px-2 py-2">
                <Avatar :name="auth.user?.name ?? '?'" size="md" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ auth.user?.name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ auth.user?.email }}</p>
                </div>
                <button class="icon-btn" title="Sair da conta" aria-label="Sair da conta" @click="logout">
                    <Icon name="logout" class="h-[18px] w-[18px]" />
                </button>
            </div>
            <div v-if="tenant.currentRoleName" class="mt-1 flex items-center justify-between px-3">
                <span class="text-[11px] text-slate-400">Papel no workspace</span>
                <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1" :class="roleBadgeStyle(tenant.currentRoleName)">
                    {{ roleLabel(tenant.currentRoleName) }}
                </span>
            </div>
        </div>
    </div>
</template>
