<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span v-if="icon" class="grid h-11 w-11 place-items-center rounded-md bg-slate-50 text-slate-600 ring-1 ring-slate-100">
                <slot name="icon"><span class="sr-only">{{ icon }}</span></slot>
            </span>
            <div>
                <h1 class="page-title">{{ title }}</h1>
                <p v-if="description" class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
            </div>
        </div>

        <div v-if="$slots.default" class="flex flex-wrap items-center gap-2.5">
            <slot />
        </div>
    </div>
</template>
