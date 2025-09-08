import '../css/app.css';
// Font Awesome (free) - provides fa icons used across the app
import '@fortawesome/fontawesome-free/css/all.css';

// Toastr notifications (CSS + JS)
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Expose toastr globally so Blade/inline scripts can call it
// @ts-ignore
(window as any).toastr = toastr;
if ((window as any).toastr) {
    (window as any).toastr.options = {
        closeButton: true,
        progressBar: true,
        newestOnTop: true,
        positionClass: 'toast-top-right',
        timeOut: '5000',
    };
}

// Drain any queued flashes pushed by the Blade partial
try {
    // @ts-ignore
    const q = (window as any).__laravel_toastr_queue;
    if (q && Array.isArray(q) && (window as any).toastr) {
        q.forEach((it: any) => {
            try {
                const type = it.type || 'info';
                const msg = it.message || '';
                // @ts-ignore
                (window as any).toastr[type](String(msg));
            } catch (e) {
                // ignore per-item errors
            }
        });
        // clear queue
        (window as any).__laravel_toastr_queue = [];
    }
} catch (e) {
    // ignore
}
