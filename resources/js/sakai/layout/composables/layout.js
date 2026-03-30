import { computed, reactive, readonly, watch } from 'vue';

// Clé unique pour isoler les réglages dans le navigateur
const STORAGE_KEY = 'ceta_user_customization';

// 1. Charger les réglages sauvegardés au démarrage
const savedConfig = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};

const layoutConfig = reactive({
    preset: savedConfig.preset || 'Aura',
    primary: savedConfig.primary || 'emerald',
    surface: savedConfig.surface || null,
    darkTheme: savedConfig.darkTheme || false,
    menuMode: 'static'
});

const layoutState = reactive({
    staticMenuDesktopInactive: false,
    overlayMenuActive: false,
    profileSidebarVisible: false,
    configSidebarVisible: false,
    staticMenuMobileActive: false,
    menuHoverActive: false,
    activeMenuItem: null
});

// 2. Écouter les changements pour les sauvegarder instantanément
watch(layoutConfig, (newVal) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
}, { deep: true });

export function useLayout() {
    const setPrimary = (value) => {
        layoutConfig.primary = value;
    };

    const setSurface = (value) => {
        layoutConfig.surface = value;
    };

    const setPreset = (value) => {
        layoutConfig.preset = value;
    };

    const setActiveMenuItem = (item) => {
        layoutState.activeMenuItem = item.value || item;
    };

    const setMenuMode = (mode) => {
        layoutConfig.menuMode = mode;
    };

    const toggleDarkMode = () => {
        if (!document.startViewTransition) {
            executeDarkModeToggle();
            return;
        }
        document.startViewTransition(() => executeDarkModeToggle());
    };

    const executeDarkModeToggle = () => {
        layoutConfig.darkTheme = !layoutConfig.darkTheme;
        // On applique la classe au document pour que le CSS de PrimeVue réagisse
        if (layoutConfig.darkTheme) {
            document.documentElement.classList.add('app-dark');
        } else {
            document.documentElement.classList.remove('app-dark');
        }
    };

    const onMenuToggle = () => {
        if (layoutConfig.menuMode === 'overlay') {
            layoutState.overlayMenuActive = !layoutState.overlayMenuActive;
        }

        if (window.innerWidth > 991) {
            layoutState.staticMenuDesktopInactive = !layoutState.staticMenuDesktopInactive;
        } else {
            layoutState.staticMenuMobileActive = !layoutState.staticMenuMobileActive;
        }
    };

    const resetMenu = () => {
        layoutState.overlayMenuActive = false;
        layoutState.staticMenuMobileActive = false;
        layoutState.menuHoverActive = false;
    };

    const isSidebarActive = computed(() => layoutState.overlayMenuActive || layoutState.staticMenuMobileActive);
    const isDarkTheme = computed(() => layoutConfig.darkTheme);
    const getPrimary = computed(() => layoutConfig.primary);
    const getSurface = computed(() => layoutConfig.surface);

    return { 
        layoutConfig: readonly(layoutConfig), 
        layoutState: readonly(layoutState), 
        onMenuToggle, 
        isSidebarActive, 
        isDarkTheme, 
        getPrimary, 
        getSurface, 
        setActiveMenuItem, 
        toggleDarkMode, 
        setPrimary, 
        setSurface, 
        setPreset, 
        resetMenu, 
        setMenuMode 
    };
}