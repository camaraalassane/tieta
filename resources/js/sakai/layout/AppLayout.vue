<script setup>
import { useLayout } from "@/sakai/layout/composables/layout";
import { computed, ref, watch, onMounted, onUnmounted } from "vue";
import AppFooter from "./AppFooter.vue";
import AppSidebar from "./AppSidebar.vue";
import AppTopbar from "./AppTopbar.vue";

const { layoutConfig, layoutState, isSidebarActive, resetMenu } = useLayout();

const outsideClickListener = ref(null);
const isMobile = ref(window.innerWidth < 768);

// Gestion du redimensionnement
const handleResize = () => {
    isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
    window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
    unbindOutsideClickListener();
});

watch(isSidebarActive, (newVal) => {
    if (newVal && isMobile.value) {
        bindOutsideClickListener();
    } else {
        unbindOutsideClickListener();
    }
});

const containerClass = computed(() => {
    return {
        "layout-overlay": layoutConfig.menuMode === "overlay",
        "layout-static": layoutConfig.menuMode === "static",
        "layout-static-inactive":
            layoutState.staticMenuDesktopInactive &&
            layoutConfig.menuMode === "static",
        "layout-overlay-active": layoutState.overlayMenuActive,
        "layout-mobile-active": layoutState.staticMenuMobileActive,
    };
});

const bindOutsideClickListener = () => {
    if (!outsideClickListener.value) {
        // ⭐ Utiliser setTimeout pour éviter que le clic qui a ouvert le menu ne le ferme
        setTimeout(() => {
            outsideClickListener.value = (event) => {
                if (isOutsideClicked(event)) {
                    resetMenu();
                }
            };
            document.addEventListener("click", outsideClickListener.value);
        }, 100);
    }
};

const unbindOutsideClickListener = () => {
    if (outsideClickListener.value) {
        document.removeEventListener("click", outsideClickListener.value);
        outsideClickListener.value = null;
    }
};

const isOutsideClicked = (event) => {
    const sidebarEl = document.querySelector(".layout-sidebar");
    const topbarEl = document.querySelector(".layout-menu-button");
    const mobileMenuEl = document.querySelector(".fixed.top-0.left-0.h-full"); // Le menu mobile

    // Vérifier si on clique sur le bouton menu mobile
    const isMenuButton =
        topbarEl?.contains(event.target) ||
        event.target.closest(".lg\\:hidden.w-8");

    // Si on clique sur le bouton menu, ne pas fermer
    if (isMenuButton) {
        return false;
    }

    // Vérifier si on clique en dehors du menu
    const isOutsideSidebar = !sidebarEl?.contains(event.target);
    const isOutsideMobileMenu = !mobileMenuEl?.contains(event.target);

    return isOutsideSidebar && isOutsideMobileMenu;
};
</script>

<template>
    <div class="layout-wrapper" :class="containerClass">
        <app-topbar></app-topbar>
        <app-sidebar></app-sidebar>
        <div class="layout-main-container">
            <div class="layout-main">
                <slot></slot>
            </div>
            <app-footer></app-footer>
        </div>
        <div class="layout-mask animate-fadein"></div>
    </div>
    <Toast />
</template>
