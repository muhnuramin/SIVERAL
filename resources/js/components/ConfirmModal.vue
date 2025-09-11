<script setup lang="ts">
import { AlertTriangle, X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title?: string;
        message?: string;
        confirmText?: string;
        cancelText?: string;
        variant?: 'danger' | 'info' | 'warning';
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl';
        hideButton?: boolean;
    }>(),
    {
        title: 'Konfirmasi',
        message: '',
        confirmText: 'Ya',
        cancelText: 'Batal',
        variant: 'warning',
        maxWidth: 'md',
        hideButton: false,
    },
);

const emit = defineEmits<{
    (e: 'cancel'): void;
    (e: 'confirm'): void;
}>();

const palette = {
    danger: {
        headerFrom: 'from-rose-500/10',
        icon: 'text-rose-600',
        button: 'bg-rose-600 hover:bg-rose-700 border-rose-600',
    },
    warning: {
        headerFrom: 'from-amber-500/10',
        icon: 'text-amber-600',
        button: 'bg-amber-600 hover:bg-amber-700 border-amber-600',
    },
    info: {
        headerFrom: 'from-sky-500/10',
        icon: 'text-sky-600',
        button: 'bg-sky-600 hover:bg-sky-700 border-sky-600',
    },
} as const;

const p = palette[props.variant ?? 'warning'];

const widthMap = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
} as const;
const widthClass = computed(() => widthMap[props.maxWidth ?? 'md']);
</script>

<template>
    <Teleport to="body">
        <!-- Backdrop fade -->
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="emit('cancel')"></div>
                <!-- Dialog scale/slide -->
                <Transition
                    appear
                    enter-active-class="duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-2 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-2 scale-95"
                >
                    <div
                        v-if="open"
                        class="relative mx-4 w-full overflow-visible rounded-xl border border-border/50 bg-card shadow-2xl"
                        :class="widthClass"
                    >
                        <div
                            class="flex items-center gap-2 border-b border-border/60 bg-gradient-to-r from-rose-500/10 to-orange-500/10 px-4 py-3"
                            :class="p.headerFrom"
                        >
                            <AlertTriangle class="h-5 w-5" :class="p.icon" />
                            <h3 class="text-base font-bold text-foreground">{{ title }}</h3>
                            <button class="ml-auto rounded p-1 text-muted-foreground hover:bg-muted" @click="emit('cancel')">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="space-y-2 px-4 py-4">
                            <p v-if="message" class="text-sm text-foreground">{{ message }}</p>
                            <div class="text-xs text-muted-foreground">
                                <slot />
                            </div>
                        </div>
                        <div v-if="!hideButton" class="flex items-center justify-end gap-2 border-t border-border/60 bg-muted/30 px-4 py-3">
                            <button
                                class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm font-semibold text-foreground hover:bg-muted"
                                @click="emit('cancel')"
                            >
                                {{ cancelText }}
                            </button>
                            <button class="rounded-lg px-3 py-1.5 text-sm font-semibold text-white shadow" :class="p.button" @click="emit('confirm')">
                                {{ confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
