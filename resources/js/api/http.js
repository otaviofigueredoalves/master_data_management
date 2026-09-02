import axios from 'axios';

export const TOKEN_KEY = 'mdm.token';
export const TENANT_KEY = 'mdm.tenant.id';
export const USER_KEY = 'mdm.user';

export const storage = {
    get(key) {
        try {
            return localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch {
            /* private mode */
        }
    },
    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch {
            /* noop */
        }
    },
};

const statusMessages = {
    400: 'Requisição inválida.',
    401: 'Sua sessão expirou. Entre novamente.',
    403: 'Você não tem permissão para realizar esta ação.',
    404: 'Recurso não encontrado.',
    419: 'Sessão expirada. Recarregue a página.',
    422: 'Não foi possível salvar. Verifique os campos.',
    429: 'Muitas tentativas. Aguarde um minuto e tente novamente.',
};

/**
 * Axios instance pointed at the versioned API. Requests automatically attach
 * the bearer token and the active `X-Tenant-ID` header. Responses are unwrapped
 * from the `{ success, message, data }` envelope; errors are normalized into a
 * predictable ApiError (`status`, `message`, `errors`).
 */
const http = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    timeout: 30000,
});

http.interceptors.request.use((config) => {
    const token = storage.get(TOKEN_KEY);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    const tenantId = storage.get(TENANT_KEY);
    if (tenantId) {
        config.headers['X-Tenant-ID'] = tenantId;
    }

    return config;
});

http.interceptors.response.use(
    (response) => {
        const body = response.data;

        if (body && typeof body === 'object' && 'success' in body && 'data' in body) {
            return body.data;
        }

        return body;
    },
    (error) => {
        const { response, request } = error ?? {};

        if (response?.status === 401) {
            storage.remove(TOKEN_KEY);
            storage.remove(USER_KEY);

            window.dispatchEvent(new CustomEvent('mdm:unauthorized'));
        }

        const apiError = new Error();
        apiError.name = 'ApiError';
        apiError.status = response?.status ?? (request ? 'network' : 'client');
        apiError.errors = response?.data?.errors ?? {};

        // Prefer the first field-level validation message over the generic English one.
        const firstError = Object.values(apiError.errors).flat()[0];
        const hasGenericMessage = !response?.data?.message || response.data.message === 'The given data was invalid.';
        apiError.message = firstError && hasGenericMessage
            ? String(firstError)
            : (response?.data?.message ?? statusMessages[apiError.status] ?? 'Falha de comunicação com o servidor.');

        apiError.raw = error;

        return Promise.reject(apiError);
    },
);

const api = {
    get: (url, params = {}) => http.get(url, { params }),
    post: (url, data) => http.post(url, data),
    put: (url, data) => http.put(url, data),
    del: (url) => http.delete(url),
};

export { api, http };
