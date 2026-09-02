<script setup>
import { computed, ref, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    /** Whole document mode (root) or a single labelled node. */
    root: { type: Boolean, default: false },
    label: { type: [String, Number], default: null },
    value: { type: [Object, Array, String, Number, Boolean], default: null },
    depth: { type: Number, default: 0 },
    /** Auto-expand the first N levels so content is visible on open. */
    expandLevel: { type: Number, default: 1 },
});

const opened = ref(props.depth < props.expandLevel);

watch(
    () => props.expandLevel,
    (level) => {
        if (props.depth < level) opened.value = true;
    },
);

const isContainer = computed(() => props.value !== null && typeof props.value === 'object');
const isArray = computed(() => Array.isArray(props.value));
const keys = computed(() => (isArray.value ? props.value.map((_, index) => index) : Object.keys(props.value ?? {})));

function typeOf(item) {
    if (item === null) return 'null';
    if (Array.isArray(item)) return 'array';
    return typeof item;
}

const valueClass = (item) => ({
    'text-emerald-600': typeof item === 'string',
    'text-sky-600': typeof item === 'number',
    'text-amber-600': typeof item === 'boolean',
    'text-slate-400 italic': item === null,
});

function displayValue(item) {
    if (item === null) return 'null';
    if (typeof item === 'string') return `"${item}"`;
    return String(item);
}

const summary = computed(() => (isArray.value ? `[${keys.length}]` : `{${keys.length}}`));
</script>

<template>
    <!-- Root document: one labelled row per top-level property. -->
    <div v-if="root" class="font-mono text-[13px] leading-relaxed">
        <template v-if="isContainer">
            <div v-for="(key, index) in keys" :key="index" class="flex items-start gap-2 py-0.5">
                <JsonViewer :label="key" :value="value[key]" :depth="depth + 1" :expand-level="expandLevel" />
            </div>
        </template>
        <span v-else :class="valueClass(value)">{{ displayValue(value) }}</span>
    </div>

    <!-- Single labelled node. -->
    <div v-else-if="isContainer" class="min-w-0 flex-1">
        <button
            type="button"
            class="group inline-flex items-center gap-1.5 rounded-md py-0.5 pr-1.5 text-left hover:bg-slate-100"
            @click="opened = !opened"
        >
            <Icon
                name="chevron-right"
                class="h-3.5 w-3.5 text-slate-400 transition-transform duration-150"
                :class="opened ? 'rotate-90' : ''"
            />
            <span class="font-semibold text-slate-700">{{ label }}:</span>
            <span class="text-slate-400">{{ summary }}</span>
        </button>

        <div v-if="opened" class="ml-2 border-l border-slate-200 pl-3">
            <div v-for="(key, index) in keys" :key="index" class="flex items-start gap-2 py-0.5">
                <JsonViewer :label="key" :value="value[key]" :depth="depth + 1" :expand-level="expandLevel" />
            </div>
        </div>
    </div>

    <!-- Primitive leaf. -->
    <div v-else class="min-w-0 flex-1 py-0.5 break-all">
        <span class="font-semibold text-slate-700">{{ label }}:</span>
        <span :class="valueClass(value)">{{ displayValue(value) }}</span>
    </div>
</template>
