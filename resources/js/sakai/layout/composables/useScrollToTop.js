// composables/useScrollToTop.js
import { onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { router as inertiaRouter } from '@inertiajs/vue3';

export function useScrollToTop() {
    const vueRouter = useRouter();
    
    // Fonction pour scroller en haut
    const scrollToTop = () => {
        setTimeout(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 50);
    };
    
    onMounted(() => {
        // Pour Vue Router
        const removeVueGuard = vueRouter.afterEach(() => {
            scrollToTop();
        });
        
        // Pour Inertia
        const removeInertiaListener = inertiaRouter.on('navigate', () => {
            scrollToTop();
        });
        
        // Nettoyage
        onUnmounted(() => {
            removeVueGuard();
            if (removeInertiaListener) removeInertiaListener();
        });
    });
}