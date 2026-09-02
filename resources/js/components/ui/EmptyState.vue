<script setup>
import Icon from './Icon.vue';

defineProps({
    icon: { type: String, default: 'box' },
    title: { type: String, default: 'Nada por aqui ainda' },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-4 py-14 text-center">
        <span class="grid h-14 w-14 place-items-center rounded-lg bg-slate-100 text-slate-400 ring-1 ring-slate-200">
            <Icon :name="icon" class="h-7 w-7" />
        </span>
        <h3 class="mt-4 text-base font-bold text-slate-900">{{ title }}</h3>
        <p v-if="description" class="mt-1.5 max-w-sm text-sm leading-relaxed text-slate-500">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
