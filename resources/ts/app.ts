import { createApp, h } from 'vue'
import { createInertiaApp,Head, Link } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'
import './bootstrap'
import { setupDateFns } from '@/lib/date-fns.ts';

const appName = import.meta.env.VITE_APP_NAME || 'Gamma'

createInertiaApp({
    title: (title: string) => `${title} - ${appName}`,
    // @ts-expect-error The correct type is returned
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue', { eager: true })),
    // @ts-expect-error Property 'el' does not exist on type 'InertiaAppOptions'.
    setup({ el, App, props, plugin }) {
        setupDateFns('en');

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('Head', Head)
            .component('Link', Link)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})
