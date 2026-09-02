import { defineStore } from 'pinia';
import { api, storage, TOKEN_KEY, USER_KEY } from '../api/http';

function readStoredUser() {
    try {
        return JSON.parse(storage.get(USER_KEY) ?? 'null');
    } catch {
        return null;
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: storage.get(TOKEN_KEY),
        user: readStoredUser(),
        loading: false,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        tenants: (state) => state.user?.tenants ?? [],
    },

    actions: {
        persistUser() {
            // Persist the token together with the user: the axios interceptor
            // reads `TOKEN_KEY` from storage on every request, so an in-memory
            // token alone would send authenticated calls without the header
            // (401 "sessão expirada" on the very next request).
            if (this.token) {
                storage.set(TOKEN_KEY, this.token);
            } else {
                storage.remove(TOKEN_KEY);
            }

            storage.set(USER_KEY, JSON.stringify(this.user ?? null));
        },

        async login(email, password, code = null) {
            this.loading = true;
            try {
                const payload = await api.post('/auth/login', { email, password, ...(code ? { code } : {}) });

                this.token = payload.token;
                this.user = payload.user;
                this.persistUser();

                return payload;
            } finally {
                this.loading = false;
            }
        },

        async register(name, email, password) {
            this.loading = true;
            try {
                const payload = await api.post('/auth/register', { name, email, password });

                this.token = payload.token;
                this.user = payload.user;
                this.persistUser();

                return payload;
            } finally {
                this.loading = false;
            }
        },

        /** Refresh the profile + tenant memberships from the server. */
        async fetchMe() {
            const user = await api.get('/me');

            this.user = user;
            this.persistUser();

            return user;
        },

        async updateProfile(payload) {
            // `PUT /me` returns the user without the `tenants` relation, so merge
            // over the previous snapshot instead of replacing it entirely.
            const updated = await api.put('/me', payload);

            this.user = { ...this.user, ...updated };
            this.persistUser();

            return this.user;
        },

        clearSession() {
            this.token = null;
            this.user = null;
            storage.remove(TOKEN_KEY);
            storage.remove(USER_KEY);
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/auth/logout');
                }
            } catch {
                /* token already revoked — ignore */
            } finally {
                this.clearSession();
            }
        },
    },
});
