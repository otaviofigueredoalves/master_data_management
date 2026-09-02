<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    size: { type: String, default: 'md' }, // sm | md | lg | xl
    closeOnBackdrop: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const sizes = { sm: 'max-w-md', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };

function onKeydown(event) {
    if (event.key === 'Escape' && props.open) {
        emit('close');
    }
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

watch(
    () => props.open,
    (isOpen) => {
        document.body.style.overflow = isOpen ? 'hidden' : '';

        if (isOpen) {
            window.requestAnimationFrame(() => document.querySelector('.modal-panel')?.focus());
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
            appear
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-900/60 p-0 sm:items-center sm:p-4"
                @mousedown.self="closeOnBackdrop && emit('close')"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-4 scale-[0.98] opacity-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-to-class="translate-y-4 scale-[0.98] opacity-0"
                    appear
                >
                    <div
                        class="modal-panel flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-lg bg-white shadow-sm outline-none sm:rounded-lg"
                        :class="sizes[size]"
                        role="dialog"
                        aria-modal="true"
                        tabindex="-1"
                    >
                        <header v-if="title" class="flex items-start justify-between gap-4 border-b border-slate-200 px-4 py-3">
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-slate-900">{{ title }}</h2>
                                <p v-if="description" class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
                            </div>
                            <button class="icon-btn -mr-2 -mt-1" aria-label="Fechar" @click="emit('close')">
                                <Icon name="x" class="h-5 w-5" />
                            </button>
                        </header>

                        <div class="overflow-y-auto px-4 py-4">
                            <slot />
                        </div>

                        <footer v-if="$slots.footer" class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3">
                            <slot name="footer" />
                        </footer>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
