import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Configuration pour Laravel Reverb
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'local',
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    }
});

// ✅ Correction : Vérifier que Echo et connector existent avant d'ajouter les listeners
if (window.Echo && window.Echo.connector) {
    // Attendre que la connexion soit établie
    window.Echo.connector.socket?.on('connect', () => {
        console.log('✅ Reverb connecté avec succès !');
    });
    
    window.Echo.connector.socket?.on('error', (error) => {
        console.error('❌ Erreur Reverb:', error);
    });
} else {
    console.warn('⚠️ Echo non initialisé, vérifiez votre configuration');
}

// Alternative plus sûre : utiliser setTimeout pour attendre l'initialisation
setTimeout(() => {
    if (window.Echo && window.Echo.connector && window.Echo.connector.socket) {
        window.Echo.connector.socket.on('connect', () => {
            console.log('✅ Reverb connecté (différé)');
        });
    }
}, 1000);