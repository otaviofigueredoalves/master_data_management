<script setup>
defineProps({
    showText: { type: Boolean, default: true },
    dark: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <span
            class="grid shrink-0 place-items-center rounded-md transition-colors duration-200"
            :class="[compact ? 'h-7 w-7' : 'h-8 w-8', dark ? 'bg-slate-100 text-slate-900' : 'bg-slate-900 text-white']"
        >
            <svg viewBox="0 0 24 24" fill="none" class="h-[58%] w-[58%]" aria-hidden="true">
                <rect x="3" y="3" width="7.5" height="7.5" rx="1" fill="currentColor" fill-opacity="0.5" />
                <rect x="13.5" y="3" width="7.5" height="7.5" rx="1" fill="currentColor" />
                <rect x="3" y="13.5" width="7.5" height="7.5" rx="1" fill="currentColor" />
                <path d="M13.5 21l4-7.5 4 7.5h-8z" fill="currentColor" fill-opacity="0.7" />
            </svg>
        </span>
        <span v-if="showText" class="leading-tight">
            <span class="block text-sm font-bold tracking-tight" :class="dark ? 'text-white' : 'text-slate-900'">
                MDM<span class="text-slate-400">SaaS</span>
            </span>
            <span v-if="!compact" class="block text-[10px] font-medium tracking-wide uppercase" :class="dark ? 'text-slate-500' : 'text-slate-400'">
                Master data
            </span>
        </span>
    </div>
</template>
