import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// IMPORTATION DU PLUGIN ZIGGY
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
// import { ZiggyVue } from './ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Nexa';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue) // <--- AJOUTE CETTE LIGNE
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
    },
});