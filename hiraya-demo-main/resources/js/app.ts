import '../css/app.css';

import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Hiraya';
const pages = import.meta.glob<{ default: DefineComponent }>('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (! page) {
            throw new Error(`Page not found: ${name}`);
        }

        return page().then((module) => module.default);
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#E8B923',
    },
});
