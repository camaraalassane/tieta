<script setup>
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Tag from "primevue/tag";
import Card from "primevue/card";
import Badge from "primevue/badge";
import { ref, computed } from "vue";
import Tooltip from "primevue/tooltip";

const props = defineProps({
    resultats: Array,
});

const searchQuery = ref("");
const selectedYear = ref("all");
const sortOrder = ref("desc");

// Années disponibles pour le filtre
const availableYears = computed(() => {
    const years = props.resultats?.map(r => new Date(r.date_publication).getFullYear()) || [];
    return [...new Set(years)].sort((a, b) => b - a);
});

// Résultats filtrés et triés
const filteredResultats = computed(() => {
    let results = props.resultats || [];

    // Filtre par recherche
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase().trim();
        results = results.filter(
            (res) =>
                res.intitule?.toLowerCase().includes(query) ||
                res.concours_nom?.toLowerCase().includes(query) ||
                res.organisateur?.toLowerCase().includes(query)
        );
    }

    // Filtre par année
    if (selectedYear.value !== 'all') {
        results = results.filter(res => {
            const year = new Date(res.date_publication).getFullYear();
            return year === parseInt(selectedYear.value);
        });
    }

    // Tri par date
    results.sort((a, b) => {
        const dateA = new Date(a.date_publication);
        const dateB = new Date(b.date_publication);
        return sortOrder.value === 'desc' ? dateB - dateA : dateA - dateB;
    });

    return results;
});

// Statistiques
const stats = computed(() => ({
    total: props.resultats?.length || 0,
    filtered: filteredResultats.value.length,
    recent: props.resultats?.filter(r => {
        const date = new Date(r.date_publication);
        const now = new Date();
        const diffDays = Math.ceil((now - date) / (1000 * 60 * 60 * 24));
        return diffDays <= 30;
    }).length || 0
}));

const downloadFile = (id) => {
    if (!id) return;
    window.open(`/candidat-resultat/${id}/voir`, "_blank");
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Mes Résultats" />

        <div class="p-4 md:p-6 lg:p-8">
            <!-- En-tête avec statistiques -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-900">
                        <template #content>
                            <div class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4">
                                <div class="flex align-items-center gap-4">
                                    <div class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-center">
                                        <i class="pi pi-file-pdf text-white text-3xl"></i>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold text-900 m-0 mb-2">Mes Résultats</h1>
                                        <p class="text-600 m-0 flex align-items-center gap-2">
                                            <i class="pi pi-check-circle text-emerald-500"></i>
                                            Consultez les résultats officiels des concours
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Statistiques rapides -->
                                <div class="flex gap-4">
                                    <div class="text-center">
                                        <Badge :value="stats.total" severity="success" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Total</div>
                                    </div>
                                    <div class="text-center">
                                        <Badge :value="stats.recent" severity="info" class="mb-1"></Badge>
                                        <div class="text-xs text-500">30 jours</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Filtres avancés -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card class="shadow-sm">
                        <template #content>
                            <div class="flex flex-column md:flex-row align-items-center gap-4">
                                <!-- Recherche -->
                                <IconField iconPosition="left" class="flex-1 w-full">
                                    <InputIcon class="pi pi-search text-emerald-500" />
                                    <InputText
                                        v-model="searchQuery"
                                        placeholder="Rechercher par titre, concours ou organisateur..."
                                        class="w-full border-round-xl"
                                    />
                                </IconField>

                                <!-- Filtre année -->
                                <div class="flex gap-2 w-full md:w-auto">
                                    <select 
                                        v-model="selectedYear"
                                        class="p-2 border-1 surface-border border-round-lg bg-white dark:bg-gray-800 text-900"
                                    >
                                        <option value="all">Toutes les années</option>
                                        <option v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>

                                    <!-- Tri -->
                                    <Button 
                                        :icon="sortOrder === 'desc' ? 'pi pi-sort-amount-down' : 'pi pi-sort-amount-up-alt'"
                                        class="p-button-outlined p-button-secondary"
                                        @click="sortOrder = sortOrder === 'desc' ? 'asc' : 'desc'"
                                        v-tooltip.top="sortOrder === 'desc' ? 'Plus récent d\'abord' : 'Plus ancien d\'abord'"
                                    />
                                </div>
                            </div>

                            <!-- Résultat de la recherche -->
                            <div class="mt-3 text-sm text-500">
                                {{ stats.filtered }} résultat(s) trouvé(s)
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Grille des résultats -->
            <div v-if="filteredResultats.length > 0" class="grid">
                <div
                    v-for="res in filteredResultats"
                    :key="res.id"
                    class="col-12 md:col-6 lg:col-4 xl:col-3 p-3"
                >
                    <Card class="result-card h-full shadow-sm hover:shadow-xl transition-all cursor-pointer border-top-4" :class="'border-' + (res.fichier_url ? 'emerald-500' : 'gray-400')">
                        <template #header>
                            <div class="p-4 pb-0">
                                <div class="flex justify-content-between align-items-center">
                                    <Tag
                                        :severity="res.fichier_url ? 'success' : 'secondary'"
                                        :value="res.fichier_url ? 'PUBLIÉ' : 'EN ATTENTE'"
                                        rounded
                                        class="px-3 py-1 text-xs font-bold"
                                    />
                                    <span class="text-500 text-xs flex align-items-center gap-1">
                                        <i class="pi pi-calendar text-emerald-500"></i>
                                        {{ formatDate(res.date_publication) }}
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template #title>
                            <div class="px-4 mt-3">
                                <h3 class="text-lg font-bold text-900 m-0 line-clamp-2" style="min-height: 3.5rem;">
                                    {{ res.intitule }}
                                </h3>
                            </div>
                        </template>

                        <template #content>
                            <div class="px-4 pb-4">
                                <!-- Concours -->
                                <div class="flex align-items-start gap-2 mb-3">
                                    <i class="pi pi-link text-emerald-500 text-sm mt-1"></i>
                                    <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider line-clamp-2">
                                        {{ res.concours_nom }}
                                    </span>
                                </div>

                                <!-- Organisateur -->
                                <div class="bg-emerald-50 dark:bg-emerald-900/10 p-3 border-round-lg flex align-items-center gap-3">
                                    <i class="pi pi-building text-emerald-600"></i>
                                    <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300 line-clamp-2">
                                        {{ res.organisateur }}
                                    </span>
                                </div>
                            </div>
                        </template>

                        <template #footer>
                            <div class="px-4 pb-4 pt-2 border-top-1 surface-border">
                                <div class="flex justify-content-between align-items-center">
                                    <span class="text-xs text-500 flex align-items-center gap-1">
                                        <i class="pi pi-file-pdf"></i>
                                        PV Officiel
                                    </span>
                                    
                                    <Button
                                        v-if="res.fichier_url"
                                        @click="downloadFile(res.id)"
                                        icon="pi pi-download"
                                        label="Télécharger"
                                        class="p-button-sm p-button-rounded p-button-text text-emerald-600 hover:text-emerald-700"
                                        v-tooltip.top="'Télécharger le PV'"
                                    />
                                    <span v-else class="text-400 italic text-xs flex align-items-center gap-1">
                                        <i class="pi pi-clock"></i>
                                        Bientôt disponible
                                    </span>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- État vide amélioré -->
            <div v-else class="mt-4">
                <Card class="shadow-sm">
                    <template #content>
                        <div class="flex flex-column align-items-center py-8">
                            <div class="w-20 h-20 bg-50 border-circle flex align-items-center justify-content-center mb-4">
                                <i class="pi pi-search-minus text-5xl text-400"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-900 mb-2">Aucun résultat trouvé</h3>
                            <p class="text-500 text-center max-w-30rem mb-4">
                                Nous n'avons trouvé aucun résultat correspondant à vos critères de recherche.
                            </p>
                            <Button 
                                label="Réinitialiser les filtres"
                                icon="pi pi-filter-slash"
                                class="p-button-outlined p-button-secondary"
                                @click="searchQuery = ''; selectedYear = 'all'"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.result-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.result-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
}

/* Line clamp pour le texte multi-lignes - Version compatible */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;  /* Propriété standard */
    overflow: hidden;
    max-height: 3em; /* Fallback sécurité */
}

/* Style pour le select */
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px;
    padding-right: 2.5rem;
    min-width: 160px;
    cursor: pointer;
}

select:focus {
    outline: none;
    border-color: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

/* Animation d'entrée */
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

.col-12 {
    animation: fadeInUp 0.5s ease-out;
    animation-fill-mode: both;
}

/* Boucle d'animation - à écrire en SCSS ou avec classes individuelles */
.col-12:nth-child(1) { animation-delay: 0.05s; }
.col-12:nth-child(2) { animation-delay: 0.1s; }
.col-12:nth-child(3) { animation-delay: 0.15s; }
.col-12:nth-child(4) { animation-delay: 0.2s; }
.col-12:nth-child(5) { animation-delay: 0.25s; }
.col-12:nth-child(6) { animation-delay: 0.3s; }
.col-12:nth-child(7) { animation-delay: 0.35s; }
.col-12:nth-child(8) { animation-delay: 0.4s; }
.col-12:nth-child(9) { animation-delay: 0.45s; }
.col-12:nth-child(10) { animation-delay: 0.5s; }
.col-12:nth-child(11) { animation-delay: 0.55s; }
.col-12:nth-child(12) { animation-delay: 0.6s; }
.col-12:nth-child(13) { animation-delay: 0.65s; }
.col-12:nth-child(14) { animation-delay: 0.7s; }
.col-12:nth-child(15) { animation-delay: 0.75s; }
.col-12:nth-child(16) { animation-delay: 0.8s; }
.col-12:nth-child(17) { animation-delay: 0.85s; }
.col-12:nth-child(18) { animation-delay: 0.9s; }
.col-12:nth-child(19) { animation-delay: 0.95s; }
.col-12:nth-child(20) { animation-delay: 1s; }

/* Styles PrimeVue personnalisés */
:deep(.p-card) {
    border-radius: 16px;
    overflow: hidden;
}

:deep(.p-card .p-card-content) {
    padding: 0;
}

:deep(.p-inputtext:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

:deep(.p-button.p-button-text.p-button-sm) {
    padding: 0.5rem 1rem;
}

/* Dark mode */
:deep(.dark select) {
    background-color: #1e293b;
    border-color: #334155;
    color: #e2e8f0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
}

.cursor-pointer {
    cursor: pointer;
}
</style>