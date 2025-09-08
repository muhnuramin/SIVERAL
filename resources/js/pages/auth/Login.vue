<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// Ambil nama aplikasi dari Vite env (diambil dari .env APP_NAME lewat VITE_APP_NAME)
const appName = (import.meta.env as any).VITE_APP_NAME ?? 'Aplikasi';
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-green-100 via-white to-emerald-100 px-4 py-8">
        <div
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white/80 p-8 shadow-xl backdrop-blur-md transition-all duration-300 hover:shadow-2xl"
        >
            <Head :title="`Masuk | ${appName}`" />
            <div class="relative mb-8 text-center">
                <div class="absolute -top-6 right-4 hidden sm:block">
                    <div class="h-24 w-24 rounded-full bg-gradient-to-br from-green-300 to-emerald-400 opacity-30 blur-md"></div>
                </div>
                <h1 class="mb-2 text-3xl font-extrabold text-green-600 sm:text-4xl">{{ appName }}</h1>
                <p class="mb-2 text-sm text-gray-600">Sistem Perencanaan, Evaluasi, dan Pelaporan</p>
                <p class="text-gray-500">Selamat datang kembali — masukkan NIP dan kata sandi Anda untuk melanjutkan.</p>
            </div>

            <div v-if="status" class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-2 text-center text-sm font-medium text-green-700">
                {{ status }}
            </div>

            <Form method="post" :action="route('login')" :reset-on-success="['password']" v-slot="{ errors, processing }" class="flex flex-col gap-6">
                <div class="grid gap-4">
                    <div>
                        <Label for="nip" class="mb-1 block text-sm font-medium text-gray-700">NIP</Label>
                        <Input
                            id="nip"
                            type="text"
                            name="nip"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="NIP"
                            placeholder="NIP"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base transition focus:border-green-500 focus:ring-2 focus:ring-green-100"
                        />
                        <InputError :message="errors.nip" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-sm font-medium text-gray-700">Kata sandi</Label>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Kata sandi"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base transition focus:border-green-500 focus:ring-2 focus:ring-green-100"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="flex items-center">
                        <Checkbox id="remember" name="remember" :tabindex="3" class="mr-2" />
                        <Label for="remember" class="text-sm text-gray-600">Ingat saya</Label>
                    </div>

                    <Button
                        type="submit"
                        class="mt-2 w-full rounded-lg bg-gradient-to-r from-green-500 to-emerald-500 py-2 text-lg font-semibold text-white shadow-md transition hover:from-green-600 hover:to-emerald-600 focus:ring-2 focus:ring-green-200 focus:outline-none disabled:opacity-70"
                        :tabindex="4"
                        :disabled="processing"
                    >
                        <LoaderCircle v-if="processing" class="mr-2 inline h-5 w-5 animate-spin align-middle" />
                        <span v-if="!processing">Masuk</span>
                    </Button>
                </div>
            </Form>

            <!-- <div class="mt-6 text-center text-sm text-gray-500">
                Don't have an account?
                <TextLink :href="route('register')" :tabindex="5" class="text-green-600 hover:underline">Sign up</TextLink>
            </div> -->
        </div>
    </div>
</template>
