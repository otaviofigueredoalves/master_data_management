<script setup>
import { computed } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    meta: { type: Object, default: null },
});

const emit = defineEmits(['page-change']);

const totalPages = computed(() => props.meta?.last_page ?? 1);
const currentPage = computed(() => props.meta?.current_page ?? 1);

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const pages = [];

    if (total <= 7) {
        for (let i = 1; i <= total; i += 1) pages.push(i);
        return pages;
    }

    pages.push(1);
    const start = Math.max(2, current - 1);
    const end = Math.min(total - 1, current + 1);

    if (start > 2) pages.push('…');
    for (let i = start; i <= end; i += 1) pages.push(i);
    if (end < total - 1) pages.push('…');
    pages.push(total);

    return pages;
});

function go(page) {
    if (page < 1 || page > totalPages.value || page === currentPage.value) return;
    emit('page-change', page);
}
</script>

<template>
    <div v-if="meta && totalPages > 1" class="flex items-center justify-between gap-4">
        <p class="muted hidden sm:block">
            Exibindo {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} de {{ meta.total }} itens
        </p>

        <nav class="flex items-center gap-1" aria-label="Paginação">
            <button class="btn btn-secondary btn-sm !px-2" :disabled="currentPage <= 1" aria-label="Anterior" @click="go(currentPage - 1)">
                <Icon name="chevron-left" class="h-4 w-4" />
            </button>

            <button
                v-for="(page, index) in visiblePages"
                :key="`${page}-${index}`"
                class="h-7 min-w-7 rounded px-2 text-xs font-semibold transition-colors duration-150"
                :class="page === currentPage ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                :disabled="page === '…'"
                @click="go(page)"
            >
                {{ page }}
            </button>

            <button class="btn btn-secondary btn-sm !px-2" :disabled="currentPage >= totalPages" aria-label="Próxima" @click="go(currentPage + 1)">
                <Icon name="chevron-right" class="h-4 w-4" />
            </button>
        </nav>
    </div>
</template>
