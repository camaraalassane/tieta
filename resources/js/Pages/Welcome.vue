<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    concours: Array,
    resultats: Array,
});

// Texte dynamique pour la section hero
const heroTexts = [
    {
        titre: "Consulter vos candidatures en toute simplicité",
        sousTitre: "Une plateforme moderne pour centraliser vos inscriptions et suivre vos résultats en temps réel"
    },
    {
        titre: "Candidatez en quelques clics",
        sousTitre: "Déposez vos dossiers et suivez l'évolution de vos candidatures depuis votre espace personnel"
    },
    {
        titre: "Résultats instantanés",
        sousTitre: "Accédez rapidement aux listes des admis et téléchargez les PV officiels"
    }
];

const currentHeroIndex = ref(0);

// Rotation automatique du texte toutes les 5 secondes
onMounted(() => {
    setInterval(() => {
        currentHeroIndex.value = (currentHeroIndex.value + 1) % heroTexts.length;
    }, 5000);
});

const getFileName = (path) => {
    if (!path) return "";
    return path.split("/").pop();
};

// Animation pour les cartes
const getCardDelay = (index) => {
    return { animationDelay: `${index * 0.1}s` };
};
</script>

<template>
    <Head title="Accueil - Plateforme Concours DTTIA" />

    <div class="min-h-screen bg-gradient-to-b from-emerald-50/50 via-white to-white dark:from-gray-900 dark:via-gray-900 dark:to-gray-900">
        <!-- Navigation améliorée -->
        <nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-emerald-100 dark:border-gray-700 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
                <!-- Logo avec animation -->
<div class="flex items-center gap-3 group">
    <!-- Image avec cadre élégant -->
    <div class="relative">
        <!-- Effet de halo -->
        <div class="absolute inset-0 bg-emerald-500/20 rounded-2xl blur-xl group-hover:bg-emerald-500/30 transition-all duration-300"></div>
        
        <!-- Cadre décoratif -->
        <div class="relative p-0.5 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-xl">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-0.5">
                <img 
                    src="/images/DTTIA.jpeg" 
                    alt="DTTIA" 
                    class="h-11 w-auto rounded-lg transform group-hover:scale-110 transition-transform duration-300"
                />
            </div>
        </div>
    </div>
    
    <div class="flex items-baseline">
        <span class="text-2xl font-black tracking-tighter text-emerald-500">
            DTTIA
        </span>
        <span class="mx-2 text-gray-300 dark:text-gray-600">|</span>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Concours
        </span>
    </div>
</div>

                <!-- Menu de navigation -->
                <div v-if="canLogin" class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="relative group"
                    >
                        <span class="btn-primary">
                            <i class="pi pi-user mr-2"></i>
                            Mon Espace
                        </span>
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-gray-600 dark:text-gray-300 hover:text-emerald-500 font-medium transition-colors relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 after:bg-emerald-500 after:transition-all hover:after:w-full"
                        >
                            Connexion
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="btn-primary"
                        >
                            <i class="pi pi-user-plus mr-2"></i>
                            S'inscrire
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Hero Section Dynamique avec animation -->
            <section class="mb-16 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/10 to-transparent rounded-3xl"></div>
                <div class="relative bg-white/70 dark:bg-gray-800/70 backdrop-blur-sm p-8 md:p-12 rounded-3xl border border-emerald-100 dark:border-gray-700 shadow-2xl shadow-emerald-500/10">
                    <!-- Badge dynamique -->
                    <div class="inline-flex items-center gap-2 bg-emerald-500 text-white px-4 py-2 rounded-full text-sm font-bold mb-6 animate-bounce">
                        <span class="w-2 h-2 bg-white rounded-full animate-ping"></span>
                        Plateforme officielle des concours
                    </div>

                    <!-- Texte avec transition -->
                    <transition name="fade" mode="out-in">
                        <div :key="currentHeroIndex" class="space-y-4">
                            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold">
                                <span class="bg-gradient-to-r from-emerald-600 to-emerald-400 bg-clip-text text-transparent">
                                    {{ heroTexts[currentHeroIndex].titre }}
                                </span>
                            </h1>
                            <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300 max-w-3xl leading-relaxed">
                                {{ heroTexts[currentHeroIndex].sousTitre }}
                            </p>
                        </div>
                    </transition>

                    <!-- Indicateurs de slide -->
                    <div class="flex gap-2 mt-8">
                        <button
                            v-for="(_, index) in heroTexts"
                            :key="index"
                            @click="currentHeroIndex = index"
                            class="h-2 rounded-full transition-all duration-300"
                            :class="[
                                currentHeroIndex === index 
                                    ? 'w-8 bg-emerald-500' 
                                    : 'w-2 bg-gray-300 dark:bg-gray-600 hover:bg-emerald-400'
                            ]"
                        ></button>
                    </div>
                </div>
            </section>

            <!-- Section Concours ouverts -->
            <section class="mb-20">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <span class="w-1 h-8 bg-emerald-500 rounded-full"></span>
                        <span class="bg-gradient-to-r from-emerald-600 to-emerald-400 bg-clip-text text-transparent">
                            Concours ouverts
                        </span>
                    </h2>
                    <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-4 py-2 rounded-full text-sm font-bold">
                        {{ concours?.length || 0 }} concours actifs
                    </span>
                </div>

                <div v-if="concours && concours.length > 0" 
                     class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div
                        v-for="(item, index) in concours"
                        :key="item.id"
                        class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 hover:-translate-y-1"
                        :style="getCardDelay(index)"
                        :class="['animate-fadeInUp']"
                    >
                        <!-- Badge date limite -->
                        <div class="absolute -top-3 right-6 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-4 py-1 rounded-full text-xs font-bold shadow-lg">
                            {{ new Date(item.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) }}
                        </div>

                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider">
                                Inscription ouverte
                            </span>

                            <a
                                v-if="item.avis"
                                :href="'/storage/Uploads/Avis/' + getFileName(item.avis)"
                                target="_blank"
                                class="text-emerald-500 hover:text-emerald-600 text-sm font-medium flex items-center gap-1 group"
                            >
                                <i class="pi pi-file-pdf text-lg"></i>
                                <span class="border-b border-dashed border-emerald-500/30 group-hover:border-emerald-500">
                                    Avis
                                </span>
                            </a>
                        </div>

                        <h3 class="text-xl font-bold mb-3 text-gray-800 dark:text-white line-clamp-2 group-hover:text-emerald-500 transition-colors">
                            {{ item.intitule || item.nom }}
                        </h3>

                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6 line-clamp-3">
                            {{ item.description }}
                        </p>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <i class="pi pi-clock"></i>
                                <span>Limite inscription</span> 
                                 {{ new Date(item.date_limite).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' }) }}
                            </div>
                            <Link
                                :href="route('login')"
                                class="btn-outline group-hover:bg-emerald-500 group-hover:text-white transition-all duration-300"
                            >
                                Postuler
                                <i class="pi pi-arrow-right ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-16 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                    <i class="pi pi-info-circle text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">Aucun concours ouvert pour le moment</p>
                </div>
            </section>

            <!-- Section Résultats -->
            <section class="mb-12">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <span class="w-1 h-8 bg-emerald-500 rounded-full"></span>
                        <span class="bg-gradient-to-r from-emerald-600 to-emerald-400 bg-clip-text text-transparent">
                            Derniers résultats
                        </span>
                    </h2>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Concours
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr
                                    v-for="res in resultats"
                                    :key="res.id"
                                    class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/10 transition-colors group"
                                >
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-800 dark:text-white">
                                            {{ res.intitule }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium text-sm">
                                                {{ res.statut }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a
                                            v-if="res.url_fichier"
                                            :href="res.url_fichier"
                                            target="_blank"
                                            class="btn-primary inline-flex items-center"
                                        >
                                            <i class="pi pi-download mr-2"></i>
                                            Télécharger
                                        </a>
                                        <span v-else class="text-sm text-gray-400 italic">
                                            Bientôt disponible
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!resultats || resultats.length === 0">
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <i class="pi pi-file-pdf text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                        <p class="text-gray-500 dark:text-gray-400">
                                            Aucun résultat publié pour le moment
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="mt-20 text-center">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-3xl p-12 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 backdrop-blur-3xl"></div>
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-4">Prêt à commencer ?</h3>
                        <p class="text-emerald-50 mb-8 max-w-2xl mx-auto">
                            Rejoignez des milliers de candidats et postulez aux concours qui vous intéressent
                        </p>
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center gap-3 bg-white text-emerald-600 px-8 py-4 rounded-xl font-bold hover:bg-emerald-50 transition-all transform hover:scale-105 shadow-2xl"
                        >
                            <i class="pi pi-user-plus text-xl"></i>
                            Créer un compte gratuitement
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-800 mt-20 py-8">
            <div class="max-w-7xl mx-auto px-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                © 2026 Plateforme CƐTA - Tous droits réservés
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
    @apply bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold transition-all duration-300 shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 active:scale-95 inline-flex items-center text-sm;
}

.btn-outline {
    @apply border-2 border-emerald-500 text-emerald-500 hover:bg-emerald-500 hover:text-white px-5 py-2 rounded-xl font-bold transition-all duration-300 text-sm inline-flex items-center;
}

/* Améliorations responsives */
@media (max-width: 640px) {
    .btn-primary {
        @apply px-4 py-2 text-xs;
    }
    
    h1 {
        @apply text-3xl;
    }
}

/* Scrollbar personnalisée */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-800;
}

::-webkit-scrollbar-thumb {
    @apply bg-emerald-500/50 rounded-full hover:bg-emerald-500/70 transition-colors;
}
</style>