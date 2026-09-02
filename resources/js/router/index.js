import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useTenantStore } from '../stores/tenant';
import { useToastStore } from '../stores/toast';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('../views/landing/LandingView.vue'),
        meta: { title: 'Plataforma MDM', public: true },
    },
    {
        path: '/entrar',
        name: 'login',
        component: () => import('../views/auth/LoginView.vue'),
        meta: { title: 'Entrar', guestOnly: true },
    },
    {
        path: '/criar-conta',
        name: 'register',
        component: () => import('../views/auth/RegisterView.vue'),
        meta: { title: 'Criar conta', guestOnly: true },
    },
    {
        path: '/convite/:token',
        name: 'invite-accept',
        component: () => import('../views/invites/AcceptInviteView.vue'),
        meta: { title: 'Aceitar convite', public: true },
    },
    {
        path: '/onboarding',
        name: 'workspace.setup',
        component: () => import('../views/onboarding/WorkspaceSetupView.vue'),
        meta: { title: 'Criar workspace', requiresAuth: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true, requiresTenant: true },
        children: [
            { path: 'dashboard', name: 'dashboard', component: () => import('../views/dashboard/DashboardView.vue'), meta: { title: 'Dashboard' } },
            { path: 'entidades', name: 'entities', component: () => import('../views/entities/EntitiesView.vue'), meta: { title: 'Entidades MDM' } },
            { path: 'integracoes', name: 'integrations', component: () => import('../views/integrations/IntegrationsView.vue'), meta: { title: 'Integrações' } },
            { path: 'sincronizacoes', name: 'sync-jobs', component: () => import('../views/sync/SyncJobsView.vue'), meta: { title: 'Sincronizações' } },
            { path: 'membros', name: 'members', component: () => import('../views/members/MembersView.vue'), meta: { title: 'Membros' } },
            { path: 'auditoria', name: 'audit', component: () => import('../views/audit/AuditLogsView.vue'), meta: { title: 'Auditoria' } },
            { path: 'configuracoes', name: 'settings', component: () => import('../views/settings/SettingsView.vue'), meta: { title: 'Configurações' } },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: () => ({ name: 'home' }) },
];

export const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

async function ensureAuthenticated() {
    const auth = useAuthStore();

    if (!auth.token) {
        return false;
    }

    if (!auth.user) {
        try {
            await auth.fetchMe();
        } catch {
            auth.clearSession();

            return false;
        }
    }

    return true;
}

async function ensureTenantContext() {
    const tenant = useTenantStore();

    if (!tenant.ready) {
        await tenant.refresh().catch(() => null);
    }

    return tenant.hasTenants;
}

router.beforeEach(async (to) => {
    document.title = to.meta.title
        ? `${to.meta.title} · ${window.__MDM_CONFIG__?.appName ?? 'MDM SaaS'}`
        : window.__MDM_CONFIG__?.appName ?? 'MDM SaaS';

    const auth = useAuthStore();
    const authenticated = await ensureAuthenticated();

    // Marketing homepage: public, but members go straight to the workspace.
    if (to.name === 'home') {
        return authenticated ? { name: 'dashboard' } : true;
    }

    // Authenticated users never see the login/register screens.
    if (to.meta.guestOnly && authenticated) {
        return { name: 'dashboard' };
    }

    // Guests are allowed on login, register and public invite pages.
    if (!authenticated) {
        if (to.meta.guestOnly || to.meta.public) {
            return true;
        }

        const toast = useToastStore();
        toast.info('Entre na sua conta para continuar.');

        return { name: 'login', query: { redirect: to.fullPath } };
    }

    // Authenticated — decide between onboarding (no workspaces) and the app.
    const hasTenant = await ensureTenantContext();

    if (to.meta.requiresTenant && !hasTenant) {
        return { name: 'workspace.setup' };
    }

    if (to.name === 'workspace.setup' && hasTenant) {
        return { name: 'dashboard' };
    }

    return true;
});

router.afterEach(() => {
    window.scrollTo(0, 0);
});
