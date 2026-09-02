export function formatDateTime(value) {
    if (!value) return '—';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export function formatDate(value) {
    if (!value) return '—';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

export function timeAgo(value) {
    if (!value) return '—';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    const seconds = Math.round((date.getTime() - Date.now()) / 1000);
    const abs = Math.abs(seconds);
    const units = [
        ['ano', 31536000],
        ['mês', 2592000],
        ['semana', 604800],
        ['dia', 86400],
        ['hora', 3600],
        ['minuto', 60],
        ['segundo', 1],
    ];

    for (const [label, size] of units) {
        if (abs >= size) {
            const amount = Math.round(abs / size);
            const suffix = amount > 1 && label !== 'mês' ? `${label}s` : label;

            return seconds < 0 ? `há ${amount} ${suffix}` : `em ${amount} ${suffix}`;
        }
    }

    return 'agora';
}

export function initials(name = '') {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

const AVATAR_GRADIENTS = [
    'from-brand-500 to-violet-500',
    'from-cyan-500 to-sky-500',
    'from-emerald-500 to-teal-500',
    'from-amber-500 to-orange-500',
    'from-pink-500 to-rose-500',
    'from-indigo-500 to-blue-500',
];

export function avatarGradient(seed = '') {
    let hash = 0;
    for (const char of String(seed)) {
        hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
    }

    return AVATAR_GRADIENTS[hash % AVATAR_GRADIENTS.length];
}

export function compactNumber(value) {
    const number = Number(value ?? 0);

    return new Intl.NumberFormat('pt-BR', { notation: 'compact', maximumFractionDigits: 1 }).format(number);
}
