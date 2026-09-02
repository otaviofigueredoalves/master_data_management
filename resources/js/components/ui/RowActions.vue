<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import Icon from './Icon.vue';

defineProps({
    /** [{ key, label, icon?, danger?, divider? }] */
    items: { type: Array, default: () => [] },
});

const emit = defineEmits(['select']);

const open = ref(false);

function close() {
    open.value = false;
}

watch(open, (isOpen) => {
    if (isOpen) {
        window.addEventListener('click', close, { once: true });
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('click', close);
});
</script>

<template>
    <div class="relative" @click.stop>
        <button
            type="button"
            class="icon-btn"
            :aria-label="'Ações'"
            :aria-expanded="open"
            :class="open ? 'bg-slate-100 text-slate-900' : ''"
            @click="open = !open"
        >
            <Icon name="dots" class="h-4 w-4" />
        </button>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            leave-active-class="transition duration-75 ease-in"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="open"
                class="absolute top-full right-0 z-30 mt-1 min-w-44 overflow-hidden rounded-md border border-slate-200 bg-white py-1"
                role="menu"
            >
                <template v-for="(item, index) in items" :key="item.key ?? index">
                    <div v-if="item.divider" class="my-1 border-t border-slate-100" />
                    <button
                        v-else
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-xs font-medium transition-colors duration-150"
                        :class="item.danger ? 'text-red-600 hover:bg-red-50' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
                        @click="open = false; emit('select', item)"
                    >
                        <Icon v-if="item.icon" :name="item.icon" class="h-3.5 w-3.5" />
                        {{ item.label }}
                    </button>
                </template>
            </div>
        </Transition>
    </div>
</template>
