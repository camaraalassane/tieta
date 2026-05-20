<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import Button from "primevue/button";

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    concours: Array,
    resultats: Array,
    communiquesActifs: Array,
    servicesWithImages: Array,
});

// ⭐ Images par défaut (si un service n'a pas d'images)
const defaultServiceImages = ["/Images/Fama.png", "/Images/armee1.jpg"];

// ⭐ Couleurs des cards de service
const cardColors = [
    "from-emerald-600 to-emerald-800",
    "from-blue-600 to-blue-800",
    "from-purple-600 to-purple-800",
    "from-orange-600 to-orange-800",
    "from-rose-600 to-rose-800",
    "from-cyan-600 to-cyan-800",
    "from-amber-600 to-amber-800",
    "from-indigo-600 to-indigo-800",
];

// ⭐ Tous les services pour les cards
const allServices = computed(() => {
    if (props.servicesWithImages && props.servicesWithImages.length > 0) {
        return props.servicesWithImages;
    }
    return [
        {
            id: 0,
            nom: "Plateforme officielle des concours",
            description: "Rejoignez la plateforme de recrutement des FAMa",
            images: [],
        },
    ];
});

// ⭐ Indices d'images pour chaque service
const serviceImageIndices = ref({});
// ⭐ Intervalles pour chaque service
const serviceIntervals = ref({});
// ⭐ État de la face de chaque card (false = face images, true = face concours)
const flippedCards = ref({});
// ⭐ Concours filtrés pour chaque service
const serviceConcours = ref({});
// ⭐ Chargement des concours
const loadingConcours = ref({});

// ⭐ Obtenir les images d'un service
const getServiceImages = (service) => {
    if (service.images && service.images.length > 0) {
        return service.images.map((img) => img.url);
    }
    return defaultServiceImages;
};

// ⭐ Démarrer le cycle d'images pour un service spécifique
const startServiceImageCycle = (serviceIndex) => {
    if (flippedCards.value[serviceIndex]) return; // Ne pas défiler si retourné

    if (serviceImageIndices.value[serviceIndex] === undefined) {
        serviceImageIndices.value[serviceIndex] = 0;
    }

    if (serviceIntervals.value[serviceIndex]) {
        clearInterval(serviceIntervals.value[serviceIndex]);
    }

    const service = allServices.value[serviceIndex];
    const images = getServiceImages(service);

    if (images.length <= 1) return;

    serviceIntervals.value[serviceIndex] = setInterval(() => {
        serviceImageIndices.value[serviceIndex] =
            (serviceImageIndices.value[serviceIndex] + 1) % images.length;
    }, 3000);
};

// ⭐ Arrêter le cycle d'images pour un service
const stopServiceImageCycle = (serviceIndex) => {
    if (serviceIntervals.value[serviceIndex]) {
        clearInterval(serviceIntervals.value[serviceIndex]);
        serviceIntervals.value[serviceIndex] = null;
    }
};

// ⭐ Obtenir la couleur de fond pour une card
const getCardColor = (index) => {
    return cardColors[index % cardColors.length];
};

// ⭐ Cliquer sur une card pour la retourner
const flipCard = (service, index) => {
    // Arrêter le cycle d'images
    stopServiceImageCycle(index);

    if (flippedCards.value[index]) {
        // Revenir à la face images
        flippedCards.value[index] = false;
        startServiceImageCycle(index);
    } else {
        // Passer à la face concours
        flippedCards.value[index] = true;
        loadServiceConcours(service, index);
    }
};

// ⭐ Charger les concours d'un service
const loadServiceConcours = async (service, index) => {
    if (serviceConcours.value[service.id]) return; // Déjà chargé

    loadingConcours.value[service.id] = true;

    try {
        const response = await fetch(`/api/service/${service.id}/concours`);
        const data = await response.json();
        serviceConcours.value[service.id] = data.concours || [];
    } catch (error) {
        console.error("Erreur chargement concours:", error);
        serviceConcours.value[service.id] = [];
    } finally {
        loadingConcours.value[service.id] = false;
    }
};

// ⭐ Obtenir les concours d'un service (depuis les props ou chargés)
const getServiceConcours = (serviceId) => {
    // D'abord chercher dans les concours déjà chargés
    if (serviceConcours.value[serviceId]) {
        return serviceConcours.value[serviceId];
    }
    // Sinon filtrer depuis les props
    return props.concours?.filter((c) => c.service_id === serviceId) || [];
};

// ⭐ Formater la date limite
const formatDateLimite = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
    });
};

// Communiqués
const showCommuniqueDialog = ref(false);
const selectedCommunique = ref(null);

// ⭐ Initialiser tous les services au montage
onMounted(() => {
    allServices.value.forEach((_, index) => {
        serviceImageIndices.value[index] = 0;
        flippedCards.value[index] = false;
        startServiceImageCycle(index);
    });
});

// ⭐ Nettoyer les intervalles
onUnmounted(() => {
    Object.values(serviceIntervals.value).forEach((interval) => {
        if (interval) clearInterval(interval);
    });
});

// Utilitaires
const getFileName = (path) => {
    if (!path) return "";
    return path.split("/").pop();
};

const getCardDelay = (index) => ({ animationDelay: `${index * 0.1}s` });

const openCommunique = (communique) => {
    selectedCommunique.value = communique;
    showCommuniqueDialog.value = true;
};

const truncateText = (text, maxLength = 100) => {
    if (!text) return "";
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + "...";
};

// ⭐ URL de postulation avec concours présélectionné
const getPostulerUrl = (concourId) => {
    if (props.canLogin) {
        // Si connecté, rediriger vers la page postuler avec le concours en paramètre
        return route("candidat-postuler.index", { concour_id: concourId });
    }
    // Sinon, rediriger vers login
    return route("login");
};
</script>

<template>
    <Head title="Accueil - Plateforme Concours FAMa" />

    <div
        class="min-h-screen bg-gradient-to-b from-emerald-50/50 via-white to-white dark:from-gray-900 dark:via-gray-900 dark:to-gray-900"
    >
        <!-- Navigation - Logo à gauche, boutons à droite -->
        <nav
            class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-emerald-100 dark:border-gray-700 sticky top-0 z-50"
        >
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-2">
                    <!-- Logo et titre - À GAUCHE -->
                    <div class="flex items-center gap-2 group flex-shrink-0">
                        <div class="relative">
                            <div
                                class="absolute inset-0 bg-emerald-500/20 rounded-full blur-xl group-hover:bg-emerald-500/30 transition-all duration-300"
                            ></div>
                            <div
                                class="relative p-0.5 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-xl"
                            >
                                <div
                                    class="bg-white dark:bg-gray-800 rounded-full p-0.5"
                                >
                                    <img
                                        src="/Images/Fama.png"
                                        alt="FAMa"
                                        class="h-8 w-8 md:h-10 md:w-10 rounded-full object-cover transform group-hover:scale-110 transition-transform duration-300"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-start">
                            <span
                                class="text-sm md:text-xl font-black tracking-tighter text-emerald-500 leading-tight"
                                >FAMa</span
                            >
                            <span
                                class="text-[8px] md:text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                >Recrutement</span
                            >
                        </div>
                    </div>

                    <!-- Menu de navigation - À DROITE -->
                    <div class="flex items-center gap-2 md:gap-4">
                        <template v-if="canLogin">
                            <Link
                                v-if="$page.props.auth.user"
                                :href="route('dashboard')"
                                class="btn-primary text-xs md:text-sm"
                            >
                                <i class="pi pi-user mr-1 text-xs"></i>
                                <span class="hidden sm:inline">Mon Espace</span>
                                <span class="sm:hidden">Espace</span>
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="text-gray-600 dark:text-gray-300 hover:text-emerald-500 font-medium transition-colors text-xs md:text-sm whitespace-nowrap"
                                >
                                    <span class="hidden sm:inline"
                                        >Se connecter</span
                                    >
                                    <span class="sm:hidden">Connexion</span>
                                </Link>
                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    class="btn-primary text-xs md:text-sm whitespace-nowrap"
                                >
                                    <i class="pi pi-user-plus mr-1 text-xs"></i>
                                    <span class="hidden sm:inline"
                                        >S'inscrire</span
                                    >
                                    <span class="sm:hidden">Inscription</span>
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <!-- ⭐ NOUVEAU : Hero Section avec fond unique et cards de service -->
            <section
                class="relative min-h-screen flex items-center overflow-hidden"
            >
                <!-- Fond unique Fama.png -->
                <div class="absolute inset-0">
                    <div class="absolute inset-0 bg-black/50 z-10"></div>
                    <img
                        src="/Images/FamaWell.png"
                        alt="Fond FAMa"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Contenu Hero -->
                <div
                    class="relative z-20 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20"
                >
                    <!-- ⭐ Titre principal -->
                    <div class="text-center mb-12 md:mb-16">
                        <h1
                            class="text-3xl md:text-5xl lg:text-6xl font-extrabold mb-4 drop-shadow-lg mx-auto"
                            style="
                                color: #d1d5db;
                                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
                            "
                        >
                            Plateforme officielle des concours
                        </h1>
                        <p
                            class="text-lg md:text-xl drop-shadow-md text-center w-full"
                            style="
                                color: #9ca3af;
                                text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.4);
                            "
                        >
                            Rejoignez la plateforme de recrutement des FAMa
                        </p>
                    </div>

                    <!-- ⭐ Cards des services - DOUBLE FACE -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8"
                    >
                        <div
                            v-for="(service, index) in allServices"
                            :key="service.id"
                            class="service-card-wrapper"
                            :style="getCardDelay(index)"
                        >
                            <!-- Conteneur de la card avec perspective -->
                            <div
                                class="service-card-inner relative w-full"
                                :class="{ 'is-flipped': flippedCards[index] }"
                                style="perspective: 1000px; min-height: 320px"
                            >
                                <!-- ⭐ FACE AVANT : Images du service -->
                                <div
                                    class="service-card-front absolute inset-0 rounded-2xl overflow-hidden shadow-2xl transition-all duration-700"
                                    style="
                                        backface-visibility: hidden;
                                        transform-style: preserve-3d;
                                    "
                                    @mouseenter="
                                        !flippedCards[index] &&
                                            startServiceImageCycle(index)
                                    "
                                    @mouseleave="
                                        !flippedCards[index] &&
                                            stopServiceImageCycle(index)
                                    "
                                    @click="flipCard(service, index)"
                                >
                                    <!-- Défilement des images -->
                                    <div class="absolute inset-0">
                                        <div
                                            v-for="(
                                                img, imgIndex
                                            ) in getServiceImages(service)"
                                            :key="imgIndex"
                                            class="absolute inset-0 transition-opacity duration-700 ease-in-out"
                                            :class="{
                                                'opacity-100':
                                                    serviceImageIndices[
                                                        index
                                                    ] === imgIndex,
                                                'opacity-0':
                                                    serviceImageIndices[
                                                        index
                                                    ] !== imgIndex,
                                            }"
                                        >
                                            <img
                                                :src="img"
                                                :alt="service.nom"
                                                class="w-full h-full object-cover"
                                            />
                                        </div>
                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-black/30"
                                        ></div>
                                    </div>

                                    <!-- Contenu face avant -->
                                    <div
                                        class="relative z-10 p-6 md:p-8 h-full flex flex-col min-h-[320px]"
                                    >
                                        <div class="text-center mb-4">
                                            <h3
                                                class="text-xl md:text-2xl font-extrabold drop-shadow-lg"
                                                style="
                                                    background: linear-gradient(
                                                        to right,
                                                        #22c55e,
                                                        #eab308,
                                                        #ef4444
                                                    );
                                                    -webkit-background-clip: text;
                                                    -webkit-text-fill-color: transparent;
                                                    background-clip: text;
                                                "
                                            >
                                                {{ service.nom }}
                                            </h3>
                                        </div>
                                        <div class="flex-1"></div>
                                        <div
                                            class="flex gap-1.5 justify-center mb-4"
                                        >
                                            <span
                                                v-for="(
                                                    img, dotIndex
                                                ) in getServiceImages(service)"
                                                :key="dotIndex"
                                                class="h-1.5 rounded-full transition-all duration-300"
                                                :class="[
                                                    serviceImageIndices[
                                                        index
                                                    ] === dotIndex
                                                        ? 'w-6 bg-white'
                                                        : 'w-1.5 bg-white/50',
                                                ]"
                                            ></span>
                                        </div>
                                        <p
                                            class="text-sm md:text-base text-gray-200 line-clamp-2 drop-shadow-md text-center"
                                        >
                                            {{
                                                service.description ||
                                                "Service de recrutement"
                                            }}
                                        </p>
                                        <!-- Indicateur cliquable -->
                                        <div class="text-center mt-3">
                                            <span
                                                class="text-xs text-white/60 flex items-center justify-center gap-1"
                                            >
                                                <i
                                                    class="pi pi-arrow-right text-[10px]"
                                                ></i>
                                                Cliquez pour voir les concours
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- ⭐ FACE ARRIÈRE : Concours du service -->
                                <div
                                    class="service-card-back absolute inset-0 rounded-2xl overflow-hidden shadow-2xl transition-all duration-700"
                                    style="
                                        backface-visibility: hidden;
                                        transform: rotateY(180deg);
                                    "
                                >
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-gray-900 to-gray-800"
                                    ></div>

                                    <div
                                        class="relative z-10 p-4 md:p-6 h-full flex flex-col"
                                    >
                                        <!-- En-tête fixe avec bouton retour -->
                                        <div
                                            class="flex justify-between items-center mb-4 flex-shrink-0"
                                        >
                                            <h4
                                                class="text-white font-bold text-sm md:text-base truncate flex-1 mr-2"
                                            >
                                                {{ service.nom }}
                                            </h4>
                                            <Button
                                                icon="pi pi-arrow-left"
                                                class="p-button-rounded p-button-text p-button-sm !text-white hover:!bg-white/20 flex-shrink-0"
                                                @click.stop="
                                                    flipCard(service, index)
                                                "
                                                v-tooltip.left="
                                                    'Retour aux images'
                                                "
                                            />
                                        </div>

                                        <!-- ⭐ Zone scrollable pour les concours -->
                                        <div
                                            class="flex-1 overflow-y-auto pr-1 custom-scrollbar min-h-0"
                                        >
                                            <!-- Chargement -->
                                            <div
                                                v-if="
                                                    loadingConcours[service.id]
                                                "
                                                class="flex items-center justify-center h-full"
                                            >
                                                <i
                                                    class="pi pi-spinner pi-spin text-white text-2xl"
                                                ></i>
                                            </div>

                                            <!-- Aucun concours -->
                                            <div
                                                v-else-if="
                                                    getServiceConcours(
                                                        service.id,
                                                    ).length === 0
                                                "
                                                class="flex items-center justify-center h-full"
                                            >
                                                <div class="text-center">
                                                    <i
                                                        class="pi pi-inbox text-white/40 text-3xl mb-2"
                                                    ></i>
                                                    <p
                                                        class="text-white/60 text-xs"
                                                    >
                                                        Aucun concours pour ce
                                                        service
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Liste des concours -->
                                            <div
                                                v-else
                                                class="flex flex-col gap-2 md:gap-3"
                                            >
                                                <div
                                                    v-for="concour in getServiceConcours(
                                                        service.id,
                                                    )"
                                                    :key="concour.id"
                                                    class="bg-white/10 backdrop-blur-sm rounded-xl p-2.5 md:p-3 border border-white/10 hover:bg-white/20 hover:border-white/30 transition-all group"
                                                >
                                                    <!-- Titre -->
                                                    <h5
                                                        class="text-white font-semibold text-xs md:text-sm mb-1.5 line-clamp-2 group-hover:text-emerald-300 transition-colors"
                                                    >
                                                        {{
                                                            concour.intitule ||
                                                            concour.nom
                                                        }}
                                                    </h5>

                                                    <!-- Infos -->
                                                    <div
                                                        class="flex flex-wrap gap-x-3 gap-y-1 text-[10px] md:text-xs text-white/60 mb-2"
                                                    >
                                                        <span
                                                            v-if="
                                                                concour.diplome_min
                                                            "
                                                            class="flex items-center gap-1"
                                                        >
                                                            <i
                                                                class="pi pi-book text-[10px]"
                                                            ></i>
                                                            {{
                                                                concour.diplome_min
                                                            }}
                                                        </span>
                                                        <span
                                                            v-if="concour.age"
                                                            class="flex items-center gap-1"
                                                        >
                                                            <i
                                                                class="pi pi-user text-[10px]"
                                                            ></i>
                                                            {{ concour.age }}
                                                            ans max
                                                        </span>
                                                        <span
                                                            v-if="
                                                                concour.date_limite
                                                            "
                                                            class="flex items-center gap-1"
                                                        >
                                                            <i
                                                                class="pi pi-clock text-[10px]"
                                                            ></i>
                                                            {{
                                                                formatDateLimite(
                                                                    concour.date_limite,
                                                                )
                                                            }}
                                                        </span>
                                                    </div>

                                                    <!-- Bouton Postuler -->
                                                    <div
                                                        class="flex justify-end"
                                                    >
                                                        <Link
                                                            :href="
                                                                getPostulerUrl(
                                                                    concour.id,
                                                                )
                                                            "
                                                            class="inline-flex items-center gap-1 bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-[10px] md:text-xs font-bold transition-all shadow-lg hover:shadow-emerald-500/30 active:scale-95"
                                                        >
                                                            <i
                                                                class="pi pi-send text-[10px]"
                                                            ></i>
                                                            Postuler
                                                        </Link>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ⭐ Pied de page fixe -->
                                        <div
                                            class="mt-2 pt-2 border-t border-white/10 flex-shrink-0"
                                        >
                                            <div
                                                class="flex items-center justify-between text-[10px] text-white/40"
                                            >
                                                <span
                                                    >{{
                                                        getServiceConcours(
                                                            service.id,
                                                        ).length
                                                    }}
                                                    concours</span
                                                >
                                                <span
                                                    class="flex items-center gap-1"
                                                >
                                                    <i
                                                        class="pi pi-chevron-down text-[8px]"
                                                        v-if="
                                                            getServiceConcours(
                                                                service.id,
                                                            ).length > 3
                                                        "
                                                    ></i>
                                                    Défilez
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Indicateurs de défilement (optionnel) -->
                    <div class="flex gap-2 justify-center mt-8">
                        <button
                            v-for="(service, index) in allServices"
                            :key="'dot-' + service.id"
                            class="h-2 rounded-full transition-all duration-300"
                            :class="['w-2 bg-white/40 hover:bg-white/60']"
                        ></button>
                    </div>

                    <!-- Bouton CTA -->
                    <div class="text-center mt-12">
                        <Link
                            v-if="canRegister && !$page.props.auth.user"
                            :href="route('register')"
                            class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-4 rounded-xl font-bold transition-all transform hover:scale-105 shadow-2xl"
                        >
                            <i class="pi pi-user-plus text-lg"></i>
                            Commencer maintenant
                        </Link>
                    </div>
                </div>
            </section>
            <!-- Contenu principal -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
                <!-- Section Communiqués -->
                <div class="mb-12 md:mb-16">
                    <div class="flex items-center gap-2 mb-6">
                        <i class="pi pi-megaphone text-emerald-500 text-xl"></i>
                        <h2 class="text-xl md:text-2xl font-bold m-0">
                            Communiqués officiels
                        </h2>
                    </div>

                    <div
                        v-if="communiquesActifs && communiquesActifs.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <div
                            v-for="communique in communiquesActifs"
                            :key="communique.id"
                            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 cursor-pointer"
                            @click="openCommunique(communique)"
                        >
                            <div
                                class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-4"
                            >
                                <i
                                    class="pi pi-megaphone text-white text-xl"
                                ></i>
                            </div>
                            <div class="p-5">
                                <div
                                    class="flex items-center justify-between mb-2"
                                >
                                    <h3
                                        class="font-bold text-lg text-gray-800 dark:text-white"
                                    >
                                        {{ communique.titre }}
                                    </h3>
                                    <Tag
                                        value="Nouveau"
                                        severity="info"
                                        size="small"
                                    />
                                </div>
                                <div
                                    class="text-sm text-gray-500 dark:text-gray-400 mb-2"
                                >
                                    <i class="pi pi-calendar mr-1"></i>
                                    {{ communique.published_at }}
                                </div>
                                <!-- ⭐ Service du concours -->
                                <div
                                    v-if="communique.service_nom"
                                    class="text-sm text-gray-500 dark:text-gray-400 mb-2"
                                >
                                    <i class="pi pi-building mr-1"></i>
                                    Service : {{ communique.service_nom }}
                                </div>
                                <div
                                    class="text-sm text-gray-500 dark:text-gray-400 mb-3"
                                >
                                    <i class="pi pi-tag mr-1"></i> Concours :
                                    {{ communique.concour_intitule }}
                                </div>
                                <div
                                    class="text-gray-600 dark:text-gray-300 whitespace-pre-wrap line-clamp-3"
                                >
                                    {{ truncateText(communique.contenu, 120) }}
                                </div>
                                <!-- ⭐ Indicateur de fichier joint -->
                                <div
                                    v-if="communique.fichier_url"
                                    class="mt-3 flex items-center gap-1 text-emerald-500 text-sm"
                                >
                                    <i class="pi pi-paperclip"></i>
                                    <span>Pièce jointe disponible</span>
                                </div>
                                <div
                                    class="mt-3 text-emerald-500 text-sm flex items-center gap-1"
                                >
                                    <span>Cliquez pour voir plus</span>
                                    <i class="pi pi-arrow-right text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-2xl"
                    >
                        <i class="pi pi-inbox text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">
                            Aucun communiqué disponible pour le moment.
                        </p>
                    </div>
                </div>

                <!-- Section Concours ouverts -->
                <section class="mb-12 md:mb-16">
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6"
                    >
                        <h2
                            class="text-xl md:text-2xl font-bold flex items-center gap-3"
                        >
                            <span
                                class="w-1 h-6 md:h-8 bg-emerald-500 rounded-full"
                            ></span>
                            <span
                                class="bg-gradient-to-r from-emerald-600 to-emerald-400 bg-clip-text text-transparent"
                                >Concours ouverts</span
                            >
                        </h2>
                        <span
                            class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-4 py-2 rounded-full text-sm font-bold whitespace-nowrap"
                        >
                            {{ concours?.length || 0 }} concours actifs
                        </span>
                    </div>

                    <div
                        v-if="concours && concours.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <div
                            v-for="(item, index) in concours"
                            :key="item.id"
                            class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 hover:-translate-y-1"
                            :style="getCardDelay(index)"
                        >
                            <div
                                class="absolute -top-3 right-6 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-4 py-1 rounded-full text-xs font-bold shadow-lg"
                            >
                                {{
                                    new Date(
                                        item.created_at,
                                    ).toLocaleDateString("fr-FR", {
                                        day: "numeric",
                                        month: "short",
                                    })
                                }}
                            </div>
                            <div class="flex justify-between items-start mb-4">
                                <span
                                    class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider"
                                    >Inscription ouverte</span
                                >
                                <a
                                    v-if="item.avis"
                                    :href="
                                        '/storage/Uploads/Avis/' +
                                        getFileName(item.avis)
                                    "
                                    target="_blank"
                                    class="text-emerald-500 hover:text-emerald-600 text-sm font-medium flex items-center gap-1 group"
                                >
                                    <i class="pi pi-file-pdf text-lg"></i>
                                    <span
                                        class="border-b border-dashed border-emerald-500/30 group-hover:border-emerald-500"
                                        >Avis</span
                                    >
                                </a>
                            </div>
                            <h3
                                class="text-lg md:text-xl font-bold mb-3 text-gray-800 dark:text-white line-clamp-2 group-hover:text-emerald-500 transition-colors"
                            >
                                {{ item.intitule || item.nom }}
                            </h3>
                            <!-- ⭐ Service du concours -->
                            <div
                                v-if="item.service"
                                class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 mb-2"
                            >
                                <i class="pi pi-building"></i>
                                <span>{{ item.service.nom }}</span>
                            </div>
                            <p
                                class="text-gray-500 dark:text-gray-400 text-sm mb-6 line-clamp-3"
                            >
                                {{ item.description }}
                            </p>
                            <div
                                class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700"
                            >
                                <div
                                    class="flex items-center gap-2 text-xs text-gray-400"
                                >
                                    <i class="pi pi-clock"></i>
                                    <span>Limite</span>
                                    {{
                                        new Date(
                                            item.date_limite,
                                        ).toLocaleDateString("fr-FR", {
                                            day: "numeric",
                                            month: "short",
                                        })
                                    }}
                                </div>
                                <Link
                                    :href="route('login')"
                                    class="btn-outline text-sm"
                                    >Postuler
                                    <i
                                        class="pi pi-arrow-right ml-2 text-xs group-hover:translate-x-1 transition-transform"
                                    ></i
                                ></Link>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-16 bg-gray-50 dark:bg-gray-800/50 rounded-2xl"
                    >
                        <i
                            class="pi pi-info-circle text-4xl text-gray-400 mb-4"
                        ></i>
                        <p class="text-gray-500 dark:text-gray-400">
                            Aucun concours ouvert pour le moment
                        </p>
                    </div>
                </section>

                <!-- Section Résultats -->
                <section class="mb-12">
                    <h2
                        class="text-xl md:text-2xl font-bold flex items-center gap-3 mb-6"
                    >
                        <span
                            class="w-1 h-6 md:h-8 bg-emerald-500 rounded-full"
                        ></span>
                        <span
                            class="bg-gradient-to-r from-emerald-600 to-emerald-400 bg-clip-text text-transparent"
                            >Derniers résultats</span
                        >
                    </h2>

                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-xl"
                    >
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th
                                            class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Concours
                                        </th>
                                        <!-- ⭐ Colonne Service AVANT Statut -->
                                        <th
                                            class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Service
                                        </th>
                                        <th
                                            class="px-4 md:px-6 py-3 md:py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Statut
                                        </th>
                                        <th
                                            class="px-4 md:px-6 py-3 md:py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                                        >
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 dark:divide-gray-700"
                                >
                                    <tr
                                        v-for="res in resultats"
                                        :key="res.id"
                                        class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-colors"
                                    >
                                        <td class="px-4 md:px-6 py-3 md:py-4">
                                            <div
                                                class="font-semibold text-gray-800 dark:text-white text-sm md:text-base"
                                            >
                                                {{ res.intitule }}
                                            </div>
                                        </td>
                                        <!-- ⭐ Colonne Service -->
                                        <td class="px-4 md:px-6 py-3 md:py-4">
                                            <div
                                                class="text-gray-600 dark:text-gray-300 text-sm"
                                            >
                                                <span
                                                    v-if="res.service_nom"
                                                    class="flex items-center gap-1"
                                                >
                                                    <i
                                                        class="pi pi-building text-emerald-500 text-xs"
                                                    ></i>
                                                    {{ res.service_nom }}
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-gray-400 italic text-xs"
                                                    >-</span
                                                >
                                            </div>
                                        </td>
                                        <td class="px-4 md:px-6 py-3 md:py-4">
                                            <div
                                                class="inline-flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"
                                                ></span>
                                                <span
                                                    class="text-emerald-600 dark:text-emerald-400 font-medium text-sm"
                                                    >{{ res.statut }}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 md:px-6 py-3 md:py-4 text-right"
                                        >
                                            <a
                                                v-if="res.url_fichier"
                                                :href="res.url_fichier"
                                                target="_blank"
                                                class="btn-primary text-sm inline-flex items-center"
                                            >
                                                <i
                                                    class="pi pi-download mr-2 text-xs"
                                                ></i>
                                                Télécharger
                                            </a>
                                            <span
                                                v-else
                                                class="text-sm text-gray-400 italic"
                                                >Bientôt disponible</span
                                            >
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !resultats || resultats.length === 0
                                        "
                                    >
                                        <td
                                            colspan="4"
                                            class="px-6 py-12 text-center"
                                        >
                                            <i
                                                class="pi pi-file-pdf text-4xl text-gray-300 dark:text-gray-600 mb-3"
                                            ></i>
                                            <p
                                                class="text-gray-500 dark:text-gray-400"
                                            >
                                                Aucun résultat publié pour le
                                                moment
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Call to Action -->
                <section class="mt-12 md:mt-20 text-center">
                    <div
                        class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-3xl p-8 md:p-12 text-white relative overflow-hidden"
                    >
                        <div
                            class="absolute inset-0 bg-white/10 backdrop-blur-3xl"
                        ></div>
                        <div class="relative z-10">
                            <h3 class="text-2xl md:text-3xl font-bold mb-4">
                                Prêt à commencer ?
                            </h3>
                            <p
                                class="text-emerald-50 mb-6 md:mb-8 max-w-2xl mx-auto text-sm md:text-base"
                            >
                                Rejoignez des milliers de candidats et postulez
                                aux concours qui vous intéressent
                            </p>
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="inline-flex items-center gap-2 md:gap-3 bg-white text-emerald-600 px-6 md:px-8 py-3 md:py-4 rounded-xl font-bold hover:bg-emerald-50 transition-all transform hover:scale-105 shadow-2xl text-sm md:text-base"
                            >
                                <i class="pi pi-user-plus text-lg"></i>
                                Créer un compte gratuitement
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- ⭐ Dialog pour afficher le contenu complet du communiqué -->
        <Dialog
            v-model:visible="showCommuniqueDialog"
            :header="selectedCommunique?.titre || 'Communiqué'"
            modal
            :style="{ width: '90vw', maxWidth: '700px' }"
            class="communique-dialog"
        >
            <div v-if="selectedCommunique" class="p-2">
                <!-- En-tête avec métadonnées -->
                <div
                    class="flex flex-wrap items-center gap-3 mb-4 pb-3 border-b border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-1 text-sm text-gray-500">
                        <i class="pi pi-calendar"></i>
                        <span>{{ selectedCommunique.published_at }}</span>
                    </div>
                    <div
                        v-if="selectedCommunique.service_nom"
                        class="flex items-center gap-1 text-sm text-gray-500"
                    >
                        <i class="pi pi-building"></i>
                        <span>{{ selectedCommunique.service_nom }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-sm text-gray-500">
                        <i class="pi pi-tag"></i>
                        <span>{{ selectedCommunique.concour_intitule }}</span>
                    </div>
                    <Tag value="Officiel" severity="success" size="small" />
                </div>

                <!-- Contenu complet -->
                <div class="prose prose-sm max-w-none dark:prose-invert mb-4">
                    <p
                        class="whitespace-pre-wrap text-gray-700 dark:text-gray-300 leading-relaxed"
                    >
                        {{ selectedCommunique.contenu }}
                    </p>
                </div>

                <!-- ⭐ Fichier joint -->
                <div
                    v-if="selectedCommunique.fichier_url"
                    class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <h4
                        class="text-sm font-semibold mb-2 flex items-center gap-2"
                    >
                        <i class="pi pi-paperclip text-emerald-500"></i>
                        Pièce jointe
                    </h4>
                    <a
                        :href="selectedCommunique.fichier_url"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors"
                    >
                        <i class="pi pi-file-pdf text-red-500"></i>
                        <span>{{
                            selectedCommunique.fichier_nom ||
                            "Télécharger le fichier"
                        }}</span>
                        <i class="pi pi-external-link text-xs"></i>
                    </a>
                </div>

                <!-- Date limite si présente -->
                <div
                    v-if="selectedCommunique.date_limite"
                    class="mt-4 text-sm text-gray-500"
                >
                    <i class="pi pi-clock mr-1"></i>
                    Date limite : {{ selectedCommunique.date_limite }}
                </div>
            </div>

            <template #footer>
                <Button
                    label="Fermer"
                    icon="pi pi-times"
                    @click="showCommuniqueDialog = false"
                    outlined
                    severity="secondary"
                />
            </template>
        </Dialog>

        <!-- Footer -->
        <footer
            class="border-t border-gray-200 dark:border-gray-800 mt-12 md:mt-20 py-6 md:py-8"
        >
            <div
                class="max-w-7xl mx-auto px-4 text-center text-gray-500 dark:text-gray-400 text-xs md:text-sm"
            >
                © 2026 FAMa Recrutement - Tous droits réservés
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* ============================================ */
/* ⭐ ANIMATIONS */
/* ============================================ */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeInUp {
    animation: fadeInUp 0.6s ease-out forwards;
    opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* ============================================ */
/* ⭐ SERVICE CARDS */
/* ============================================ */
.service-card {
    animation: fadeInUp 0.6s ease-out backwards;
    cursor: pointer;
}

.service-card:hover {
    transform: scale(1.05) translateY(-4px);
}

/* ============================================ */
/* ⭐ BOUTONS */
/* ============================================ */
.btn-primary {
    @apply bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-xl font-bold transition-all duration-300 shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 active:scale-95 inline-flex items-center;
}

.btn-outline {
    @apply border-2 border-emerald-500 text-emerald-500 hover:bg-emerald-500 hover:text-white px-3 py-1.5 md:px-4 md:py-2 rounded-xl font-bold transition-all duration-300 inline-flex items-center;
}

/* ============================================ */
/* ⭐ LINE CLAMP */
/* ============================================ */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* ============================================ */
/* ⭐ SCROLLBAR */
/* ============================================ */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #059669;
}

/* Dark mode scrollbar */
.dark ::-webkit-scrollbar-track {
    background: #374151;
}

/* ============================================ */
/* ⭐ DIALOG COMMUNIQUÉ */
/* ============================================ */
:deep(.communique-dialog .p-dialog-header) {
    background: linear-gradient(to right, #10b981, #059669);
    color: white;
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

:deep(.communique-dialog .p-dialog-header .p-dialog-title) {
    color: white;
    font-weight: 700;
}

:deep(.communique-dialog .p-dialog-header .p-dialog-header-icon) {
    color: white;
}

:deep(.communique-dialog .p-dialog-header .p-dialog-header-icon:hover) {
    background: rgba(255, 255, 255, 0.2);
}

/* ============================================ */
/* ⭐ RESPONSIVE */
/* ============================================ */
@media (max-width: 640px) {
    .service-card {
        min-height: 280px;
    }
}
/* ⭐ SERVICE CARDS - DOUBLE FACE */
.service-card-wrapper {
    perspective: 1000px;
}

.service-card-inner {
    transition: transform 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    transform-style: preserve-3d;
    min-height: 320px;
}

.service-card-inner.is-flipped {
    transform: rotateY(180deg);
}

.service-card-front,
.service-card-back {
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.service-card-back {
    transform: rotateY(180deg);
}

/* Scrollbar face arrière */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 2px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2px;
}

/* Animation */
.service-card-wrapper {
    animation: fadeInUp 0.6s ease-out backwards;
}
/* ⭐ Amélioration scroll face arrière */
.custom-scrollbar::-webkit-scrollbar {
    width: 3px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Indicateur de défilement */
@keyframes scrollHint {
    0%,
    100% {
        opacity: 0.4;
    }
    50% {
        opacity: 0.8;
    }
}

.pi-chevron-down {
    animation: scrollHint 2s ease-in-out infinite;
}
</style>
