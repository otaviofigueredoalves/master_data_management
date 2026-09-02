<script setup>
import { useToastStore } from '../../stores/toast';
import Icon from './Icon.vue';

const toast = useToastStore();

const styles = {
    success: {
        ring: 'ring-emerald-200',
        chip: 'bg-emerald-100 text-emerald-700',
        icon: 'check-circle',
        title: 'Sucesso',
    },
    error: {
        ring: 'ring-red-200',
        chip: 'bg-red-100 text-red-700',
        icon: 'alert',
        title: 'Erro',
    },
    info: {
        ring: 'ring-slate-200',
        chip: 'bg-slate-100 text-slate-700',
        icon: 'info',
        title: 'Atenção',
    },
};
</script>

<template>
    <div class="pointer-events-none fixed top-4 right-4 z-[100] flex w-[min(92vw,380px)] flex-col gap-2.5">
        <TransitionGroup enter-active-class="transition duration-200 ease-out" enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in" leave-to-class="translate-y-1 opacity-0">
            <div v-for="item in toast.items" :key="item.id"
                class="pointer-events-auto flex items-start gap-3 rounded-md bg-white p-3.5 shadow-sm ring-1"
                :class="styles[item.type].ring">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg" :class="styles[item.type].chip">
                    <Icon :name="styles[item.type].icon" class="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p class="text-sm font-semibold text-slate-900">{{ styles[item.type].title }}</p>
                    <p class="mt-0.5 text-sm leading-snug break-words text-slate-600">{{ item.message }}</p>
                </div>
                <button class="icon-btn -mr-1 -mt-1" aria-label="Fechar" @click="toast.dismiss(item.id)">
                    <Icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
