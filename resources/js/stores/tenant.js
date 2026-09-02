import { defineStore } from 'pinia';
import { api, storage, TENANT_KEY } from '../api/http';
import { useAuthStore } from './auth';
import { ROLE_FALLBACK_PERMISSIONS } from '../utils/rbac';

export const useTenantStore = defineStore('tenant', {
    state: () => ({
        tenants: [],
        roles: [],
        currentTenantId: Number(storage.get(TENANT_KEY)) || null,
        ready: false,
        loading: false,
    }),

    getters: {
        currentTenant: (state) => state.tenants.find((tenant) => tenant.id === state.currentTenantId) ?? null,

        /** Role the authenticated user holds inside the active tenant. */
        currentRoleName() {
            return this.currentTenant?.membership_role ?? null;
        },

        /** `true` when the platform super-admin is acting without a membership role. */
        isUnrestricted() {
            return !this.currentRoleName;
        },

        currentRolePermissions() {
            const roleName = this.currentRoleName;
            const role = this.roles.find((item) => item.name === roleName);

            if (this.isUnrestricted) {
                return ROLE_FALLBACK_PERMISSIONS.admin;
            }

            return (role?.permissions ?? ROLE_FALLBACK_PERMISSIONS[roleName] ?? []).map((p) => p.name ?? p);
        },

        hasTenants: (state) => state.tenants.length > 0,
    },

    actions: {
        persistCurrent() {
            if (this.currentTenantId) {
                storage.set(TENANT_KEY, String(this.currentTenantId));
            } else {
                storage.remove(TENANT_KEY);
            }
        },

        /**
         * Normalizes `/tenants` — regular members receive an array with a
         * `membership_role` name string, platform super-admins a paginator
         * without one (treated as unrestricted by `can()`).
         */
        normalizeTenants(payload) {
            const list = Array.isArray(payload) ? payload : (payload?.data ?? []);

            return list.map(({ pivot, membership_role, ...tenant }) => ({
                ...tenant,
                membership_role: typeof membership_role === 'string' ? membership_role : null,
            }));
        },

        async loadTenants() {
            const payload = await api.get('/tenants', { per_page: 100 });

            this.tenants = this.normalizeTenants(payload);
        },

        async loadRoles() {
            const payload = await api.get('/roles');

            this.roles = payload ?? [];
        },

        async refresh() {
            const auth = useAuthStore();

            if (!auth.isAuthenticated) {
                this.reset();

                return;
            }

            this.loading = true;
            try {
                await Promise.all([this.loadTenants(), this.loadRoles()]);

                const ids = this.tenants.map((tenant) => tenant.id);
                const userPreferred = auth.user?.current_tenant_id;

                if (this.currentTenantId && ids.includes(this.currentTenantId)) {
                    this.setCurrent(this.currentTenantId, { silent: true });
                } else if (userPreferred && ids.includes(userPreferred)) {
                    this.setCurrent(userPreferred, { silent: true });
                } else if (ids.length) {
                    this.setCurrent(ids[0], { silent: true });
                } else {
                    this.currentTenantId = null;
                    this.persistCurrent();
                }

                this.ready = true;
            } finally {
                this.loading = false;
            }
        },

        async setCurrent(id, { silent = false } = {}) {
            this.currentTenantId = Number(id);
            this.persistCurrent();

            if (!silent) {
                try {
                    await api.post('/tenants/switch', { tenant_id: Number(id) });
                } catch {
                    /* the header already scopes requests; ignore failures here */
                }
            }
        },

        async createTenant(payload) {
            const auth = useAuthStore();

            await api.post('/tenants', payload);

            // The backend sets the freshly created workspace as `current_tenant_id`.
            await auth.fetchMe();
            await this.loadTenants();

            const target = auth.user?.current_tenant_id ?? this.tenants.at(-1)?.id;

            if (target) {
                await this.setCurrent(target);
            }

            this.ready = true;
        },

        can(permission) {
            if (this.isUnrestricted) {
                return true;
            }

            return this.currentRolePermissions.includes(permission);
        },

        reset() {
            this.tenants = [];
            this.roles = [];
            this.ready = false;
            this.currentTenantId = null;
            storage.remove(TENANT_KEY);
        },
    },
});
