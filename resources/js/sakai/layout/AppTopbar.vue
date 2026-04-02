<script setup>
import { useLayout } from "@/sakai/layout/composables/layout";
import { computed, ref, onMounted, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppConfigurator from "./AppConfigurator.vue";
import NavLink from "@/Components/NavLink.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import AppMenu from "./AppMenu.vue";
import axios from "axios";

const { onMenuToggle, toggleDarkMode, isDarkTheme } = useLayout();
const page = usePage();

// État local
const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);

// --- DONNÉES COMPUTED ---
const notifications = computed(() => page.props.auth.user?.notifications || []);
const unreadNotifCount = computed(() => notifications.value.length);
const unreadMsgCount = computed(
    () => page.props.auth.user?.unread_messages_count || 0,
);

// Couleurs dynamiques basées sur le thème
const topbarClasses = computed(() => {
    return {
        "bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-b border-gray-200/50 dark:border-gray-800/50":
            !isScrolled.value,
        "bg-white dark:bg-gray-900 shadow-lg border-b border-gray-200 dark:border-gray-800":
            isScrolled.value,
    };
});

const logoTextClasses = computed(() => {
    return {
        "text-emerald-600 dark:text-emerald-400": !isDarkTheme.value,
        "text-emerald-400": isDarkTheme.value,
    };
});

// --- FONCTIONS ---
const markAllAsRead = () => {
    if (unreadNotifCount.value > 0) {
        axios
            .post(route("notifications.markAsRead"))
            .then(() => {
                page.props.auth.user.notifications = [];
            })
            .catch((error) => {
                console.error(
                    "Erreur lors du marquage des notifications :",
                    error,
                );
            });
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "short",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Détection du scroll
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

onMounted(() => {
    window.addEventListener("scroll", handleScroll);
});

// Synchronisation du thème
watch(isDarkTheme, (newVal) => {
    if (newVal) {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }
});

// Gestion du menu mobile
const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
    if (isMobileMenuOpen.value) {
        document.body.style.overflow = "hidden";
    } else {
        document.body.style.overflow = "";
    }
};

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false;
    document.body.style.overflow = "";
};
</script>

<template>
    <!-- Topbar avec tous les effets d'origine -->
    <div
        class="layout-topbar fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        :class="topbarClasses"
    >
        <div class="w-full px-3 sm:px-4 lg:px-6">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <!-- Section gauche - Logo et menu -->
                <div class="flex items-center gap-2 sm:gap-4 flex-1 min-w-0">
                    <!-- Bouton menu mobile -->
                    <button
                        class="lg:hidden w-8 h-8 sm:w-10 sm:h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors flex items-center justify-center flex-shrink-0 relative z-50"
                        @click="toggleMobileMenu"
                        :aria-label="
                            isMobileMenuOpen
                                ? 'Fermer le menu'
                                : 'Ouvrir le menu'
                        "
                    >
                        <i
                            class="pi text-sm sm:text-base"
                            :class="isMobileMenuOpen ? 'pi-times' : 'pi-bars'"
                        ></i>
                    </button>

                    <!-- Bouton menu desktop -->
                    <button
                        class="hidden lg:flex w-8 h-8 sm:w-10 sm:h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors items-center justify-center flex-shrink-0"
                        @click="onMenuToggle"
                    >
                        <i class="pi pi-bars text-sm sm:text-base"></i>
                    </button>

                    <!-- Logo -->
                    <NavLink
                        href="/dashboard"
                        class="flex items-center gap-2 sm:gap-3 group flex-shrink-0"
                        @click="closeMobileMenu"
                    >
                        <!-- Image avec effets -->
                        <div class="relative flex-shrink-0">
                            <div
                                class="absolute inset-0 rounded-full blur-xl transition-all duration-300"
                                :class="
                                    isDarkTheme
                                        ? 'bg-emerald-500/30'
                                        : 'bg-emerald-500/20'
                                "
                            ></div>
                            <img
                                src="/Images/DTTIA.jpeg"
                                alt="DTTIA"
                                class="h-8 sm:h-10 md:h-12 w-auto relative rounded-lg shadow-lg group-hover:shadow-emerald-500/20 group-hover:scale-105 transition-all duration-300"
                                :class="{ 'brightness-90': isDarkTheme }"
                            />
                        </div>

                        <!-- Texte du logo -->
                        <div class="flex flex-col">
                            <span
                                class="font-black text-sm sm:text-base md:text-xl tracking-tight leading-none transition-colors"
                                :class="logoTextClasses"
                            >
                                Recrutement
                            </span>
                            <span
                                class="font-medium text-xs sm:text-sm md:text-base tracking-wide transition-colors"
                                :class="
                                    isDarkTheme
                                        ? 'text-gray-300'
                                        : 'text-gray-700'
                                "
                            >
                                DTTIA
                            </span>
                        </div>
                    </NavLink>
                </div>

                <!-- Section droite - Actions (avec toutes les fonctionnalités) -->
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                    <!-- Contrôle du thème -->
                    <div
                        class="flex items-center gap-1 p-0.5 sm:p-1 bg-gray-100 dark:bg-gray-800 rounded-xl"
                    >
                        <button
                            @click="!isDarkTheme && toggleDarkMode()"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                            :class="
                                !isDarkTheme
                                    ? 'bg-white dark:bg-gray-900 shadow-sm text-emerald-600'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
                            "
                        >
                            <i class="pi pi-sun text-xs sm:text-sm"></i>
                        </button>
                        <button
                            @click="isDarkTheme && toggleDarkMode()"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                            :class="
                                isDarkTheme
                                    ? 'bg-white dark:bg-gray-900 shadow-sm text-emerald-400'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
                            "
                        >
                            <i class="pi pi-moon text-xs sm:text-sm"></i>
                        </button>
                    </div>

                    <!-- Notifications avec tous les effets -->
                    <div class="relative">
                        <button
                            type="button"
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors flex items-center justify-center relative"
                            v-styleclass="{
                                selector: '@next',
                                enterFromClass: 'hidden',
                                enterActiveClass: 'animate-scalein',
                                leaveToClass: 'hidden',
                                leaveActiveClass: 'animate-fadeout',
                                hideOnOutsideClick: true,
                            }"
                        >
                            <i class="pi pi-bell text-sm sm:text-base"></i>
                            <span
                                v-if="unreadNotifCount > 0"
                                class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[8px] sm:text-[10px] rounded-full h-3.5 w-3.5 sm:h-4 sm:w-4 flex items-center justify-center ring-2 ring-white dark:ring-gray-900"
                            >
                                {{
                                    unreadNotifCount > 9
                                        ? "9+"
                                        : unreadNotifCount
                                }}
                            </span>
                        </button>

                        <!-- Panneau des notifications -->
                        <div
                            class="hidden bg-white dark:bg-gray-800 shadow-xl absolute right-0 mt-2 w-72 sm:w-80 py-2 rounded-xl border dark:border-gray-700 z-50"
                        >
                            <div
                                class="px-3 sm:px-4 py-2 sm:py-3 border-b dark:border-gray-700 font-semibold text-xs sm:text-sm flex justify-between items-center"
                            >
                                <span>Notifications</span>
                                <button
                                    v-if="unreadNotifCount > 0"
                                    @click="markAllAsRead"
                                    class="text-xs text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 bg-transparent cursor-pointer font-medium"
                                >
                                    Tout marquer
                                </button>
                            </div>
                            <div class="max-h-80 sm:max-h-96 overflow-y-auto">
                                <div
                                    v-if="unreadNotifCount === 0"
                                    class="px-3 sm:px-4 py-6 sm:py-8 text-center text-gray-500 text-xs sm:text-sm"
                                >
                                    <i
                                        class="pi pi-bell-off text-xl sm:text-2xl mb-2 opacity-50"
                                    ></i>
                                    <p>Aucune notification</p>
                                </div>
                                <div
                                    v-for="notif in notifications"
                                    :key="notif.id"
                                    class="px-3 sm:px-4 py-2 sm:py-3 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                                >
                                    <div class="flex gap-2 sm:gap-3">
                                        <div class="flex-shrink-0">
                                            <div
                                                class="w-6 h-6 sm:w-8 sm:h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center"
                                            >
                                                <i
                                                    class="pi pi-info-circle text-emerald-600 dark:text-emerald-400 text-[10px] sm:text-xs"
                                                ></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p
                                                class="text-[10px] sm:text-xs text-gray-800 dark:text-gray-200 leading-relaxed line-clamp-2"
                                            >
                                                {{ notif.data.message }}
                                            </p>
                                            <span
                                                class="text-[8px] sm:text-[10px] text-gray-400 mt-0.5 block"
                                            >
                                                {{
                                                    formatDate(notif.created_at)
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu utilisateur complet -->
                    <div class="relative">
                        <button
                            type="button"
                            class="flex items-center gap-1 sm:gap-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            v-styleclass="{
                                selector: '@next',
                                enterFromClass: 'hidden',
                                enterActiveClass: 'animate-scalein',
                                leaveToClass: 'hidden',
                                leaveActiveClass: 'animate-fadeout',
                                hideOnOutsideClick: true,
                            }"
                        >
                            <div
                                class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg flex items-center justify-center text-white font-semibold text-xs sm:text-sm shadow-lg flex-shrink-0"
                            >
                                {{
                                    page.props.auth.user?.name?.charAt(0) || "U"
                                }}
                            </div>
                            <span
                                class="hidden md:block text-xs sm:text-sm font-medium max-w-[100px] truncate"
                            >
                                {{
                                    page.props.auth.user?.name?.split(" ")[0] ||
                                    "Utilisateur"
                                }}
                            </span>
                            <i
                                class="pi pi-chevron-down text-[10px] sm:text-xs text-gray-400 hidden sm:block"
                            ></i>
                        </button>

                        <!-- Menu déroulant -->
                        <div
                            class="hidden bg-white dark:bg-gray-800 shadow-xl absolute right-0 mt-2 w-44 sm:w-48 py-1 rounded-xl border dark:border-gray-700 z-50"
                        >
                            <div
                                class="px-3 sm:px-4 py-2 sm:py-3 border-b dark:border-gray-700"
                            >
                                <p
                                    class="text-xs sm:text-sm font-semibold truncate"
                                >
                                    {{ page.props.auth.user?.name }}
                                </p>
                                <p
                                    class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 truncate"
                                >
                                    {{ page.props.auth.user?.email }}
                                </p>
                            </div>
                            <DropdownLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="w-full text-left px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-red-600 dark:text-red-400 flex items-center gap-2"
                            >
                                <i
                                    class="pi pi-sign-out text-[10px] sm:text-xs"
                                ></i>
                                Déconnexion
                            </DropdownLink>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu mobile avec AppMenu -->
    <transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="-translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="-translate-x-full"
    >
        <div
            v-if="isMobileMenuOpen"
            class="fixed top-0 left-0 h-full w-72 bg-white dark:bg-gray-900 shadow-2xl z-[60] lg:hidden overflow-y-auto"
        >
            <AppMenu />
        </div>
    </transition>

    <!-- Overlay pour mobile -->
    <div
        v-if="isMobileMenuOpen"
        class="fixed inset-0 bg-black/50 z-[55] lg:hidden"
        @click="closeMobileMenu"
    ></div>
</template>

<style scoped>
.layout-topbar {
    transition: all 0.3s ease;
}

/* Garder les animations originales */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-panel {
    animation: slideIn 0.2s ease;
}

/* Garder le style du toggle du thème */
.theme-toggle {
    @apply relative overflow-hidden;
}

.theme-toggle::before {
    content: "";
    @apply absolute inset-0 bg-emerald-500/10 rounded-lg scale-0 transition-transform duration-300;
}

.theme-toggle:hover::before {
    @apply scale-100;
}

/* Cache le texte du logo sur très petits écrans */
@media (max-width: 380px) {
    .flex-col span:first-child {
        font-size: 0.875rem;
    }
    .flex-col span:last-child {
        font-size: 0.75rem;
    }
}

/* Dark mode */
.dark .layout-topbar {
    border-bottom-color: rgba(255, 255, 255, 0.05);
}

/* Line-clamp pour les notifications */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;
    overflow: hidden;
}
</style>
