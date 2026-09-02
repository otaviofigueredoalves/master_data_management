// Apresentação da trilha de auditoria para usuários de negócio.
// Espelha AuditLog::ENTITY_TYPE_LABELS (backend) — nunca exibir caminhos técnicos
// de classes (ex.: App\Models\MdmEntity) na interface.

export const ENTITY_TYPE_LABELS = {
    'App\\Models\\MdmEntity': 'Entidade MDM',
    'App\\Models\\MdmEntityRelation': 'Relacionamento',
    'App\\Models\\Integration': 'Integração',
    'App\\Models\\SyncJob': 'Sincronização',
    'App\\Models\\Tenant': 'Workspace',
    'App\\Models\\TenantUser': 'Membro',
    'App\\Models\\User': 'Usuário',
    'App\\Models\\TenantInvitation': 'Convite',
};

export const AUDIT_ACTIONS = [
    'entity.created',
    'entity.updated',
    'entity.deleted',
    'entity.normalized',
    'integration.created',
    'integration.updated',
    'integration.deleted',
    'integration.sync_started',
    'integration.sync_completed',
    'integration.sync_failed',
];

const ACTION_TRANSLATIONS = {
    'entity.created': 'Entidade criada',
    'entity.updated': 'Entidade atualizada',
    'entity.deleted': 'Entidade excluída',
    'entity.normalized': 'Entidade normalizada',
    'integration.created': 'Integração criada',
    'integration.updated': 'Integração atualizada',
    'integration.deleted': 'Integração excluída',
    'integration.sync_started': 'Sincronização iniciada',
    'integration.sync_completed': 'Sincronização concluída',
    'integration.sync_failed': 'Sincronização falhou',
};

/**
 * Rótulo amigável para um tipo de entidade auditada. Usa o rótulo enviado pelo
 * backend quando disponível e, como fallback, humaniza apenas o nome curto da
 * classe — nunca o caminho completo (namespace).
 */
export function entityTypeLabel(type = '') {
    if (!type) return '—';

    if (ENTITY_TYPE_LABELS[type]) return ENTITY_TYPE_LABELS[type];

    const base = String(type).split('\\').pop() || String(type);

    return base
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/^./, (char) => char.toUpperCase());
}

/** Rótulo amigável a partir de um registro de log (prefere entity_label da API). */
export function logEntityLabel(log) {
    return log?.entity_label || entityTypeLabel(log?.entity_type);
}

/** Rótulo amigável (PT-BR) para a chave técnica de ação. */
export function auditActionLabel(action) {
    if (!action) return '—';

    if (ACTION_TRANSLATIONS[action]) return ACTION_TRANSLATIONS[action];

    return String(action)
        .replace(/[._]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}
