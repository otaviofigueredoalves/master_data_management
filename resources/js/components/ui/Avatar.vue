<script setup>
import { computed } from 'vue';
import { initials } from '../../utils/format';

const props = defineProps({
    name: { type: String, default: '?' },
    size: { type: String, default: 'md' }, // sm | md | lg
});

const sizes = {
    sm: 'h-7 w-7 text-[10px]',
    md: 'h-8 w-8 text-xs',
    lg: 'h-10 w-10 text-sm',
};

const TONES = ['bg-slate-700', 'bg-slate-500', 'bg-slate-800', 'bg-slate-600'];

function tone(seed = '') {
    let hash = 0;
    for (const char of String(seed)) {
        hash = (hash * 31 + char.charCodeAt(0)) >>> 0;
    }

    return TONES[hash % TONES.length];
}

const classes = computed(() => sizes[props.size] ?? sizes.md);
</script>

<template>
    <span class="inline-grid shrink-0 place-items-center rounded font-bold text-white select-none" :class="[classes, tone(name)]">
        {{ initials(name) }}
    </span>
</template>
