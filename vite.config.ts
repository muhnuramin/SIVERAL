import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.ts',
                // Auth pages
                'resources/js/pages/auth/Login.vue',
                'resources/js/pages/auth/Register.vue',
                'resources/js/pages/auth/ForgotPassword.vue',
                'resources/js/pages/auth/ResetPassword.vue',
                'resources/js/pages/auth/VerifyEmail.vue',
                'resources/js/pages/auth/ConfirmPassword.vue',
                // Main pages
                'resources/js/pages/Dashboard.vue',
                'resources/js/pages/Welcome.vue',
                // Master pages
                'resources/js/pages/master/Program.vue',
                'resources/js/pages/master/KegiatanIndex.vue',
                'resources/js/pages/master/SubKegiatanIndex.vue',
                'resources/js/pages/master/SubSubKegiatan.vue',
                'resources/js/pages/master/Akun.vue',
                'resources/js/pages/Satuan/Index.vue',
                // Anggaran pages
                'resources/js/pages/anggaran/Perencanaan.vue',
                'resources/js/pages/anggaran/RekeningDetail.vue',
                'resources/js/pages/anggaran/RincianBelanja.vue',
                'resources/js/pages/evaluasi/Index.vue',
                // User management
                'resources/js/pages/user/User.vue',
                // Settings pages
                'resources/js/pages/settings/Profile.vue',
                'resources/js/pages/settings/Password.vue',
                'resources/js/pages/settings/Appearance.vue',
            ],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 2500, // Increased to accommodate PDF library
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    // Node modules chunking
                    if (id.includes('node_modules')) {
                        // Vue ecosystem
                        if (id.includes('vue') || id.includes('@inertiajs/vue3')) {
                            return 'vue';
                        }

                        // UI and component libraries
                        if (id.includes('reka-ui') || id.includes('lucide-vue-next') || id.includes('@vueuse/core')) {
                            return 'ui';
                        }

                        // PDF handling - keep as separate chunk due to size
                        if (id.includes('pdfmake')) {
                            return 'pdf';
                        }

                        // Styling utilities
                        if (id.includes('class-variance-authority') || id.includes('clsx') || id.includes('tailwind-merge')) {
                            return 'style';
                        }

                        // Date/time utilities
                        if (id.includes('moment')) {
                            return 'time';
                        }

                        // Notifications and icons
                        if (id.includes('toastr') || id.includes('@fortawesome/fontawesome-free')) {
                            return 'notifications';
                        }

                        // Laravel specific
                        if (id.includes('ziggy-js')) {
                            return 'laravel';
                        }

                        // All other vendor packages
                        return 'vendor';
                    }

                    // Application pages chunking
                    if (id.includes('resources/js/pages')) {
                        // Group related pages
                        if (id.includes('auth/') || id.includes('Auth/')) {
                            return 'pages-auth';
                        }
                        if (id.includes('anggaran/') || id.includes('Anggaran/')) {
                            return 'pages-anggaran';
                        }
                        if (id.includes('dashboard/') || id.includes('Dashboard/')) {
                            return 'pages-dashboard';
                        }
                        // Default pages chunk
                        return 'pages';
                    }

                    // Components chunking
                    if (id.includes('resources/js/components')) {
                        return 'components';
                    }

                    // Composables chunking
                    if (id.includes('resources/js/composables')) {
                        return 'composables';
                    }
                },
            },
        },
    },
});
