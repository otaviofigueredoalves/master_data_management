<script setup>
import BaseModal from './BaseModal.vue';
import Icon from './Icon.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: 'Tem certeza?' },
    message: { type: String, default: '' },
    confirmText: { type: String, default: 'Confirmar' },
    cancelText: { type: String, default: 'Cancelar' },
    danger: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

defineEmits(['confirm', 'close']);
</script>

<template>
    <BaseModal :open="open" size="sm" :title="title" @close="$emit('close')">
        <div class="flex items-start gap-4">
            <span
                class="grid h-10 w-10 shrink-0 place-items-center rounded-md"
                :class="danger ? 'bg-red-100 text-red-600' : 'bg-slate-100 text-slate-600'"
            >
                <Icon :name="danger ? 'alert' : 'info'" class="h-5 w-5" />
            </span>
            <p class="pt-1.5 text-sm leading-relaxed text-slate-600">
                <slot>{{ message }}</slot>
            </p>
        </div>

        <template #footer>
            <button type="button" class="btn btn-secondary" :disabled="loading" @click="$emit('close')">
                {{ cancelText }}
            </button>
            <button
                type="button"
                class="btn"
                :class="danger ? 'btn-danger' : 'btn-primary'"
                :disabled="loading"
                @click="$emit('confirm')"
            >
                <svg v-if="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z" />
                </svg>
                {{ confirmText }}
            </button>
        </template>
    </BaseModal>
</template>
