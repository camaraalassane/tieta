import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, Link, Head } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// PrimeVue Core
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Ripple from 'primevue/ripple';

// Icônes locales (Évite l'erreur ERR_INTERNET_DISCONNECTED)
import 'primeicons/primeicons.css';

createInertiaApp({
    // ⭐ Titre de l'onglet
    title: (title) => title ? `${title} - FAMa Recrutement` : 'FAMa Recrutement',
    
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Plugin Inertia
        app.use(plugin);
        
        // Ziggy pour les routes Laravel
        app.use(ZiggyVue);

        // PrimeVue
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '.dark',
                    cssLayer: {
                        name: 'primevue',
                        order: 'tailwind-base, primevue, tailwind-utilities'
                    }
                }
            },
            ripple: true
        });

        // Services PrimeVue
        app.use(ToastService);
        app.use(ConfirmationService);
        
        // Directive ripple
        app.directive('ripple', Ripple);

        // Enregistrement global des composants Inertia
        app.component('Link', Link);
        app.component('Head', Head);  // ⭐ AJOUTÉ

        // Montage de l'application
        app.mount(el);
    },
    progress: {
        color: '#10b981',
    },
});