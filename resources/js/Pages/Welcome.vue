<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import Button from "primevue/button";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    concours: Array,
    resultats: Array,
    communiquesActifs: Array,
});

// Images pour le carrousel
const backgroundImages = [
    "/Images/Voiture.jpg",
    "/Images/Direction.jpg",
    "/Images/antenne.JPG",
    "/Images/Technicien.JPG",
    "/Images/Batiment.JPG",
];

// Texte explicatif du fonctionnement de l'application
const heroTexts = [
    {
        titre: "Inscrivez-vous gratuitement",
        sousTitre:
            "Créez votre compte en quelques clics pour accéder à toutes les fonctionnalités de la plateforme",
    },
    {
        titre: "Connectez-vous à votre espace",
        sousTitre:
            "Accédez à votre tableau de bord personnalisé et gérez vos candidatures",
    },
    {
        titre: "Complétez votre profil",
        sousTitre:
            "Renseignez vos informations personnelles, votre parcours et vos documents",
    },
    {
        titre: "Postulez aux concours",
        sousTitre:
            "Choisissez parmi les concours ouverts et déposez votre candidature en ligne",
    },
    {
        titre: "Suivez vos candidatures en temps réel",
        sousTitre:
            "Consultez l'état d'avancement de vos dossiers et recevez des notifications",
    },
    {
        titre: "Consultez et téléchargez les résultats",
        sousTitre:
            "Accédez aux listes d'admis et téléchargez les procès-verbaux officiels",
    },
    {
        titre: "Restez informé des actualités",
        sousTitre:
            "Consultez les communiqués et annonces de lancement de nouveaux concours",
    },
];

const currentHeroIndex = ref(0);
const currentImageIndex = ref(0);
let heroInterval = null;
let imageInterval = null;

// ⭐ Dialog pour afficher le contenu complet du communiqué
const showCommuniqueDialog = ref(false);
const selectedCommunique = ref(null);

// Rotation automatique du texte toutes les 5 secondes
onMounted(() => {
    heroInterval = setInterval(() => {
        currentHeroIndex.value =
            (currentHeroIndex.value + 1) % heroTexts.length;
    }, 5000);

    // Rotation des images toutes les 4 secondes
    imageInterval = setInterval(() => {
        currentImageIndex.value =
            (currentImageIndex.value + 1) % backgroundImages.length;
    }, 4000);
});

onUnmounted(() => {
    if (heroInterval) clearInterval(heroInterval);
    if (imageInterval) clearInterval(imageInterval);
});

const getFileName = (path) => {
    if (!path) return "";
    return path.split("/").pop();
};

const getCardDelay = (index) => {
    return { animationDelay: `${index * 0.1}s` };
};

// ⭐ Ouvrir le dialog avec le contenu complet du communiqué
const openCommunique = (communique) => {
    selectedCommunique.value = communique;
    showCommuniqueDialog.value = true;
};

// ⭐ Tronquer le texte pour l'aperçu
const truncateText = (text, maxLength = 100) => {
    if (!text) return "";
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + "...";
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
            <!-- Hero Section avec images en arrière-plan défilantes -->
            <section
                class="relative h-[90vh] min-h-[600px] flex items-center overflow-hidden"
            >
                <!-- Images de fond avec transition -->
                <div
                    v-for="(img, index) in backgroundImages"
                    :key="index"
                    class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                    :class="{
                        'opacity-100 z-0': currentImageIndex === index,
                        'opacity-0 z-0': currentImageIndex !== index,
                    }"
                >
                    <div class="absolute inset-0 bg-black/60 z-10"></div>
                    <img
                        :src="img"
                        :alt="'Image ' + (index + 1)"
                        class="w-full h-full object-cover"
                    />
                </div>

                <!-- Contenu Hero -->
                <div
                    class="relative z-20 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center"
                >
                    <div class="max-w-4xl mx-auto">
                        <!-- Badge dynamique -->
                        <div
                            class="inline-flex items-center gap-2 bg-emerald-500 text-white px-4 py-2 rounded-full text-sm font-bold mb-6 animate-bounce"
                        >
                            <span
                                class="w-2 h-2 bg-white rounded-full animate-ping"
                            ></span>
                            Plateforme officielle des concours
                        </div>

                        <!-- Texte avec transition -->
                        <transition name="fade" mode="out-in">
                            <div :key="currentHeroIndex" class="space-y-4">
                                <h1
                                    class="text-3xl md:text-5xl lg:text-6xl font-extrabold text-emerald-400 drop-shadow-lg"
                                    style="
                                        text-shadow: 2px 2px 4px
                                            rgba(0, 0, 0, 0.5);
                                    "
                                >
                                    {{ heroTexts[currentHeroIndex].titre }}
                                </h1>
                                <p
                                    class="text-base md:text-xl text-white max-w-3xl mx-auto leading-relaxed drop-shadow-lg"
                                    style="
                                        text-shadow: 1px 1px 2px
                                            rgba(0, 0, 0, 0.5);
                                    "
                                >
                                    {{ heroTexts[currentHeroIndex].sousTitre }}
                                </p>
                            </div>
                        </transition>

                        <!-- Indicateurs de slide -->
                        <div class="flex gap-2 justify-center mt-8">
                            <button
                                v-for="(_, index) in heroTexts"
                                :key="index"
                                @click="currentHeroIndex = index"
                                class="h-2 rounded-full transition-all duration-300"
                                :class="[
                                    currentHeroIndex === index
                                        ? 'w-8 bg-emerald-500'
                                        : 'w-2 bg-white/60 hover:bg-emerald-400',
                                ]"
                            ></button>
                        </div>

                        <!-- Bouton CTA -->
                        <div class="mt-8">
                            <Link
                                v-if="canRegister && !$page.props.auth.user"
                                :href="route('register')"
                                class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-6 md:px-8 py-3 rounded-xl font-bold transition-all transform hover:scale-105 shadow-2xl"
                            >
                                <i class="pi pi-user-plus text-lg"></i>
                                Commencer maintenant
                            </Link>
                        </div>
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
/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
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

/* Boutons personnalisés */
.btn-primary {
    @apply bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 md:px-4 md:py-2 rounded-xl font-bold transition-all duration-300 shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 active:scale-95 inline-flex items-center;
}

.btn-outline {
    @apply border-2 border-emerald-500 text-emerald-500 hover:bg-emerald-500 hover:text-white px-3 py-1.5 md:px-4 md:py-2 rounded-xl font-bold transition-all duration-300 inline-flex items-center;
}

/* Line clamp */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Scrollbar */
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

/* Style du dialog */
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
</style>
