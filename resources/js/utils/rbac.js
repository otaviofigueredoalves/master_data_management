/**
 * Mirror of `RolePermissionSeeder` used as an offline fallback while the
 * authoritative catalog (`GET /roles`) is being fetched. The SPA never relies
 * on this for security — the backend enforces permissions — it only drives
 * visibility of actions in the interface.
 */
export const ROLE_FALLBACK_PERMISSIONS = {
    admin: [
        'create users',
        'update users',
        'delete users',
        'invite users',
        'manage integrations',
        'create mdm entities',
        'update mdm entities',
        'delete mdm entities',
        'normalize mdm entities',
        'run sync',
        'view audit logs',
        'view dashboard',
    ],
    manager: [
        'create mdm entities',
        'update mdm entities',
        'normalize mdm entities',
        'run sync',
        'view audit logs',
        'view dashboard',
    ],
    user: ['view dashboard'],
};

export const PERMISSION_LABELS = {
    'create users': 'Adicionar membros',
    'update users': 'Alterar papel de membros',
    'delete users': 'Remover membros',
    'invite users': 'Convidar membros',
    'manage integrations': 'Gerenciar integrações',
    'create mdm entities': 'Criar entidades MDM',
    'update mdm entities': 'Editar entidades MDM',
    'delete mdm entities': 'Excluir entidades MDM',
    'normalize mdm entities': 'Normalizar e promover golden records',
    'run sync': 'Executar sincronizações',
    'view audit logs': 'Visualizar trilha de auditoria',
    'view dashboard': 'Visualizar dashboard',
};

export const ROLE_LABELS = {
    admin: 'Admin',
    manager: 'Manager',
    user: 'Viewer',
};

export const ROLE_DESCRIPTIONS = {
    admin: 'Acesso total: gerencia membros, integrações, entidades e auditoria.',
    manager: 'Opera o domínio: cria/edita/normaliza entidades e executa sincronizações.',
    user: 'Leitura do workspace e dashboard.',
};

export function permissionLabel(name) {
    return PERMISSION_LABELS[name] ?? name;
}

export function roleLabel(name) {
    return ROLE_LABELS[name] ?? name;
}

export const ROLE_BADGE_STYLES = {
    admin: 'bg-slate-900 text-white',
    manager: 'bg-teal-50 text-teal-800 ring-teal-200',
    user: 'bg-slate-100 text-slate-600 ring-slate-200',
    'super-admin': 'bg-amber-50 text-amber-800 ring-amber-200',
};

export function roleBadgeStyle(name) {
    return ROLE_BADGE_STYLES[name] ?? 'bg-slate-100 text-slate-600 ring-slate-200';
}
