<script setup lang="ts">
// Ambil nama aplikasi dari Vite env (diambil dari .env APP_NAME lewat VITE_APP_NAME)
const appName = (import.meta.env as any).VITE_APP_NAME ?? 'Aplikasi';
</script>

<template>
    <div class="flex h-full w-full items-center justify-center">
        <span class="truncate text-3xl leading-tight font-bold">{{ appName }}</span>
    </div>
</template>
