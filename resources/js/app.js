import '../css/app.css';
import './bootstrap';
import './consoleMessage.js';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, defineAsyncComponent } from 'vue';
import { FontAwesomeIcon } from './fontAwesome';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Solarmax3Wiki';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // 异步组件加载
        app.component('WikiEditor', defineAsyncComponent(() =>
            import('./Components/Editor/WikiEditor.vue')
        ));

        app.component('WikiPreview', defineAsyncComponent(() =>
            import('./Components/Wiki/WikiPreview.vue')
        ));

        app.component('TableOfContents', defineAsyncComponent(() =>
            import('./Components/Wiki/TableOfContents.vue')
        ));

        app.component('RevisionCompare', defineAsyncComponent(() =>
            import('./Components/Wiki/Revision/RevisionCompare.vue')
        ));

        return app
            .use(plugin)
            .use(ZiggyVue)
            .component('font-awesome-icon', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#1e3a8a',
    },
});