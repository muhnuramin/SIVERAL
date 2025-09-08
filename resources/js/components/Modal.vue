<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-50" @click="$emit('close')"></div>
        <div class="relative w-full max-w-lg rounded bg-white p-4 shadow-lg">
            <div class="mb-4 text-lg font-semibold">{{ title }}</div>
            <div class="mb-4 text-sm text-slate-700" v-html="message"></div>
            <div class="flex justify-end gap-2">
                <button v-if="cancelText" @click="$emit('cancel')" class="rounded border px-3 py-1 text-sm">{{ cancelText }}</button>
                <button v-if="okText" @click="$emit('ok')" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white">{{ okText }}</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';
const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    okText: { type: String, default: 'OK' },
    cancelText: { type: String, default: '' },
});
</script>
