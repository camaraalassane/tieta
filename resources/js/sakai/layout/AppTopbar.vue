<script setup>
import { useLayout } from "@/sakai/layout/composables/layout";
import { computed, ref, onMounted, watch, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router as inertiaRouter } from "@inertiajs/vue3";
import NavLink from "@/Components/NavLink.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import AppMenu from "./AppMenu.vue";
import axios from "axios";

// ⭐ 1. PAGE EN PREMIER
const page = usePage();
const { onMenuToggle, layoutState, resetMenu } = useLayout();

// ⭐ 2. PALETTE DE COULEURS
const colorPalette = [
    { name: "Émeraude", value: "emerald", bg: "bg-emerald-500" },
    { name: "Bleu", value: "blue", bg: "bg-blue-500" },
    { name: "Violet", value: "purple", bg: "bg-purple-500" },
    { name: "Orange", value: "orange", bg: "bg-orange-500" },
    { name: "Rose", value: "pink", bg: "bg-pink-500" },
    { name: "Rouge", value: "red", bg: "bg-red-500" },
    { name: "Cyan", value: "cyan", bg: "bg-cyan-500" },
    { name: "Ambre", value: "amber", bg: "bg-amber-500" },
];

// ⭐ 3. THÈME
const isDarkTheme = ref(false);
const currentThemeColor = ref(page.props.auth.user?.theme_color || "emerald");

const changeThemeColor = async (color) => {
    currentThemeColor.value = color;
    document.documentElement.setAttribute("data-theme-color", color);
    try {
        await axios.post(route("theme.color"), { color });
    } catch (error) {
        console.error("Erreur sauvegarde couleur:", error);
    }
};

const toggleDarkMode = async () => {
    isDarkTheme.value = !isDarkTheme.value;
    if (isDarkTheme.value) {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }
    try {
        await axios.post(route("theme.toggle"));
    } catch (error) {
        console.error("Erreur sauvegarde thème:", error);
    }
};

// ⭐ Vérifier si l'utilisateur est un candidat (operator)
const isOperator = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Avec Spatie, les rôles sont des objets avec une propriété 'name'
    if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
        return user.roles.some((role) => {
            // Les rôles peuvent être des objets {id, name, guard_name} ou des strings
            if (typeof role === "object" && role !== null) {
                return role.name === "operator";
            }
            if (typeof role === "string") {
                return role === "operator";
            }
            return false;
        });
    }

    return false;
});
// ⭐ 4. ÉTAT LOCAL
const isScrolled = ref(false);
const realtimeNotifications = ref([]);
const deleteDialog = ref(false);

// ⭐ 5. COMPUTED
const userService = computed(() => {
    const user = page.props.auth.user;
    if (!user) return null;
    if (user.roles?.includes("gerant") || user.roles?.includes("admin")) {
        return user.service || null;
    }
    return null;
});

const appLogo = computed(() => {
    const user = page.props.auth.user;
    if (!user) return "/Images/DTTIA.jpeg";
    if (
        user.roles?.includes("superadmin") ||
        user.roles?.includes("operator")
    ) {
        return "/Images/Fama.png";
    }
    if (userService.value?.logo_url) {
        return userService.value.logo_url;
    }
    return "/Images/DTTIA.jpeg";
});

const appPrimaryName = computed(() => {
    const user = page.props.auth.user;
    if (!user) return "Recrutement";
    if (
        user.roles?.includes("superadmin") ||
        user.roles?.includes("operator")
    ) {
        return "FAMa";
    }
    if (userService.value?.nom) {
        return userService.value.nom;
    }
    return "Recrutement";
});

const appSecondaryName = computed(() => "Recrutement");

const isMobileMenuOpen = computed(() => layoutState.staticMenuMobileActive);

const notifications = computed(() => {
    const backendNotifs = page.props.auth.user?.notifications || [];
    const allNotifs = [...realtimeNotifications.value, ...backendNotifs];
    return allNotifs.filter(
        (v, i, a) => a.findIndex((t) => t.id === v.id) === i,
    );
});

const unreadNotifCount = computed(
    () => notifications.value.filter((n) => !n.read_at).length,
);

const topbarClasses = computed(() => ({
    "bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-b border-gray-200/50 dark:border-gray-800/50":
        !isScrolled.value,
    "bg-white dark:bg-gray-900 shadow-lg border-b border-gray-200 dark:border-gray-800":
        isScrolled.value,
}));

const logoTextClasses = computed(() => ({
    "text-emerald-600 dark:text-emerald-400": !isDarkTheme.value,
    "text-emerald-400": isDarkTheme.value,
}));

// ⭐ 6. FONCTIONS
const scrollToTop = () =>
    setTimeout(() => window.scrollTo({ top: 0, behavior: "smooth" }), 100);
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};
const toggleMobileMenu = (event) => {
    event.stopPropagation();
    onMenuToggle();
};
const closeMobileMenu = () => {
    if (layoutState.staticMenuMobileActive) resetMenu();
};

const markAllAsRead = () => {
    if (unreadNotifCount.value > 0) {
        axios.post(route("notifications.markAsRead")).then(() => {
            page.props.auth.user.notifications = [];
            realtimeNotifications.value = [];
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

let removeNavigationListener = null;
let echoChannel = null;

const initRealtimeNotifications = () => {
    const userId = page.props.auth.user?.id;
    if (!userId || !window.Echo) return;
    const waitForEcho = setInterval(() => {
        if (
            window.Echo &&
            window.Echo.connector &&
            window.Echo.connector.socket
        ) {
            clearInterval(waitForEcho);
            echoChannel = window.Echo.private(
                `App.Models.User.${userId}`,
            ).notification((notification) => {
                realtimeNotifications.value.unshift({
                    id: Date.now(),
                    data: notification.data || notification,
                    created_at: new Date().toISOString(),
                    read_at: null,
                });
            });
        }
    }, 500);
};

// ⭐ 7. LIFECYCLE
onMounted(() => {
    document.documentElement.setAttribute(
        "data-theme-color",
        currentThemeColor.value,
    );
    const userTheme = page.props.auth.user?.theme;
    if (userTheme === "dark") {
        isDarkTheme.value = true;
        document.documentElement.classList.add("dark");
    }
    window.addEventListener("scroll", handleScroll);
    removeNavigationListener = inertiaRouter.on("success", () => {
        if (layoutState.staticMenuMobileActive) resetMenu();
        scrollToTop();
    });
    initRealtimeNotifications();
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
    if (removeNavigationListener) removeNavigationListener();
    if (echoChannel) echoChannel.stopListening();
});

watch(isDarkTheme, (newVal) => {
    if (newVal) document.documentElement.classList.add("dark");
    else document.documentElement.classList.remove("dark");
});
</script>
<template>
    <div
        class="layout-topbar fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        :class="topbarClasses"
    >
        <div class="w-full px-3 sm:px-4 lg:px-6">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <!-- Section gauche - Logo et menu -->
                <div class="flex items-center gap-2 sm:gap-4 flex-1 min-w-0">
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

                    <button
                        class="hidden lg:flex w-8 h-8 sm:w-10 sm:h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors items-center justify-center flex-shrink-0"
                        @click="onMenuToggle"
                    >
                        <i class="pi pi-bars text-sm sm:text-base"></i>
                    </button>

                    <NavLink
                        href="/dashboard"
                        class="flex items-center gap-2 sm:gap-3 group flex-shrink-0"
                        @click="closeMobileMenu"
                    >
                        <div class="relative flex-shrink-0">
                            <div
                                class="absolute inset-0 rounded-full blur-xl transition-all duration-300 theme-bg-primary opacity-20 dark:opacity-30"
                            ></div>
                            <img
                                :src="appLogo"
                                :alt="appPrimaryName"
                                class="h-8 sm:h-10 md:h-12 w-auto relative rounded-lg shadow-lg group-hover:scale-105 transition-all duration-300"
                                :class="{ 'brightness-90': isDarkTheme }"
                                @error="
                                    (e) => (e.target.src = '/Images/Fama.png')
                                "
                            />
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="font-black text-sm sm:text-base md:text-xl tracking-tight leading-none transition-colors theme-text-primary"
                            >
                                {{ appPrimaryName }}
                            </span>
                            <span
                                class="font-medium text-xs sm:text-sm md:text-base tracking-wide transition-colors"
                                :class="
                                    isDarkTheme
                                        ? 'text-gray-300'
                                        : 'text-gray-700'
                                "
                            >
                                {{ appSecondaryName }}
                            </span>
                        </div>
                    </NavLink>
                </div>

                <!-- Section droite - Actions -->
                <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
                    <div
                        class="flex items-center gap-1 p-0.5 sm:p-1 bg-gray-100 dark:bg-gray-800 rounded-xl"
                    >
                        <!-- LUNE : activer le mode nuit -->
                        <button
                            @click="!isDarkTheme && toggleDarkMode()"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                            :class="
                                !isDarkTheme
                                    ? 'bg-white dark:bg-gray-900 shadow-sm theme-text-primary'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
                            "
                        >
                            <i class="pi pi-moon text-xs sm:text-sm"></i>
                        </button>
                        <!-- SOLEIL : activer le mode jour -->
                        <button
                            @click="isDarkTheme && toggleDarkMode()"
                            class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                            :class="
                                isDarkTheme
                                    ? 'bg-white dark:bg-gray-900 shadow-sm theme-text-primary'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
                            "
                        >
                            <i class="pi pi-sun text-xs sm:text-sm"></i>
                        </button>
                    </div>

                    <!-- Notifications -->
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
                                    class="text-xs theme-text-primary hover:underline bg-transparent cursor-pointer font-medium"
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
                                                class="w-6 h-6 sm:w-8 sm:h-8 theme-bg-light rounded-full flex items-center justify-center"
                                            >
                                                <i
                                                    class="pi pi-info-circle theme-text-primary text-[10px] sm:text-xs"
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
                                                >{{
                                                    formatDate(notif.created_at)
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Menu utilisateur -->
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
                                class="w-7 h-7 sm:w-8 sm:h-8 theme-bg-primary rounded-lg flex items-center justify-center text-white font-semibold text-xs sm:text-sm shadow-lg flex-shrink-0"
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
                            <!-- ⭐ Palette de couleurs - Visible uniquement pour les non-operators -->
                            <div
                                v-if="!isOperator"
                                class="px-3 sm:px-4 py-2 border-b dark:border-gray-700"
                            >
                                <p
                                    class="text-[10px] sm:text-xs text-gray-500 mb-2"
                                >
                                    Couleur du thème
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="color in colorPalette"
                                        :key="color.value"
                                        @click="changeThemeColor(color.value)"
                                        :class="[
                                            'w-5 h-5 sm:w-6 sm:h-6 rounded-full transition-all duration-200',
                                            color.bg,
                                            currentThemeColor === color.value
                                                ? 'ring-2 ring-offset-2 ring-gray-400 scale-110'
                                                : 'hover:scale-110',
                                        ]"
                                        :title="color.name"
                                    ></button>
                                </div>
                            </div>
                            <DropdownLink
                                :href="route('profile.edit')"
                                as="a"
                                class="w-full text-left px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300 flex items-center gap-2"
                            >
                                <i class="pi pi-cog text-[10px] sm:text-xs"></i>
                                Paramètres
                            </DropdownLink>
                            <div
                                class="border-t dark:border-gray-700 my-1"
                            ></div>
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

    <!-- Menu mobile -->
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
            @click.stop
        >
            <AppMenu />
        </div>
    </transition>
    <div
        v-if="isMobileMenuOpen"
        class="fixed inset-0 bg-black/50 z-[55] lg:hidden"
        @click="closeMobileMenu"
    ></div>
</template>

<style scoped>
/* Vos styles inchangés */
.layout-topbar {
    transition: all 0.3s ease;
}

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

@media (max-width: 380px) {
    .flex-col span:first-child {
        font-size: 0.875rem;
    }
    .flex-col span:last-child {
        font-size: 0.75rem;
    }
}

.dark .layout-topbar {
    border-bottom-color: rgba(255, 255, 255, 0.05);
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;
    overflow: hidden;
}
</style>
