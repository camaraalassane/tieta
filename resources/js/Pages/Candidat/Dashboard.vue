<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Link, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import { ref, computed } from 'vue';

const props = defineProps({
    concours_disponibles: { type: Array, default: () => [] },
    mes_candidatures: { type: Array, default: () => [] },
    resultats_publies: { type: Array, default: () => [] },
    stats: { 
        type: Object, 
        default: () => ({ total: 0, admis: 0, traitement: 0, rejete: 0 }) 
    }
});

// État pour afficher tous les résultats
const showAllResults = ref(false);

// Résultats à afficher (limités ou tous)
const displayedResults = computed(() => {
    if (showAllResults.value) {
        return props.resultats_publies;
    }
    return props.resultats_publies.slice(0, 4);
});

// Nombre de résultats cachés
const hiddenResultsCount = computed(() => {
    return Math.max(0, props.resultats_publies.length - 4);
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const getSeverity = (resultat) => {
    if (!resultat) return 'info';
    const r = resultat.toLowerCase();
    if (r.includes('admis')) return 'success'; 
    if (r.includes('rejété') || r.includes('rejeté')) return 'danger'; 
    if (r.includes('traitement') || r.includes('attente')) return 'warn';
    return 'info';
};

const getSeverityProps = (resultat) => {
    const severity = getSeverity(resultat);
    return {
        severity,
        class: severity === 'success' ? 'bg-green-100 text-green-700' :
               severity === 'danger' ? 'bg-red-100 text-red-700' :
               severity === 'warn' ? 'bg-orange-100 text-orange-700' :
               'bg-blue-100 text-blue-700'
    };
};

const downloadFile = (id) => {
    if (!id) return;
    window.open(`/candidat-resultat/${id}/voir`, "_blank");
};

const toggleResults = () => {
    showAllResults.value = !showAllResults.value;
};

const expandedRows = ref([]);
</script>

<template>
    <app-layout>
        <div class="p-fluid px-3 sm:px-4 md:px-5 lg:px-6">
            <!-- Hero Card amélioré -->
            <div class="grid">
                <div class="col-12 mb-4">
                    <Card class="shadow-lg border-none overflow-hidden">
                        <template #content>
                            <div class="flex flex-column md:flex-row align-items-center justify-content-between gap-4">
                                <div class="flex flex-column sm:flex-row align-items-center gap-4 w-full md:w-auto">
                                    <div class="w-16 h-16 bg-emerald-100 border-round-xl flex align-items-center justify-center flex-shrink-0">
                                        <i class="pi pi-star text-3xl text-emerald-500"></i>
                                    </div>
                                    <div class="text-center sm:text-left">
                                        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-900 m-0 mb-2">Bienvenue</h1>
                                        <p class="text-600 text-base sm:text-lg md:text-xl m-0">Suivez vos dossiers et découvrez les nouveaux concours.</p>
                                    </div>
                                </div>
                            
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Statistiques Cards améliorées -->
            <div class="stats-grid mb-4">
                <!-- Candidatures -->
                <div class="stat-card-wrapper">
                    <Card class="shadow-md hover:shadow-xl transition-all cursor-pointer border-left-4 border-blue-500">
                        <template #content>
                            <div class="stat-content-container">
                                <div class="text-block">
                                    <span class="stat-title text-blue-600 font-medium">Candidatures</span>
                                    <div class="stat-value text-900 font-bold">{{ props.stats.total }}</div>
                                    <small class="text-500 hidden sm:block">Total des dossiers</small>
                                </div>
                                <div class="icon-circle bg-blue-100">
                                    <i class="pi pi-file text-blue-500 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Admissions -->
                <div class="stat-card-wrapper">
                    <Card class="shadow-md hover:shadow-xl transition-all cursor-pointer border-left-4 border-green-500">
                        <template #content>
                            <div class="stat-content-container">
                                <div class="text-block">
                                    <span class="stat-title text-green-600 font-medium">Admissions</span>
                                    <div class="stat-value text-900 font-bold">{{ props.stats.admis }}</div>
                                    <small class="text-500 hidden sm:block">Candidatures retenues</small>
                                </div>
                                <div class="icon-circle bg-green-100">
                                    <i class="pi pi-check-circle text-green-500 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- En traitement -->
                <div class="stat-card-wrapper">
                    <Card class="shadow-md hover:shadow-xl transition-all cursor-pointer border-left-4 border-orange-500">
                        <template #content>
                            <div class="stat-content-container">
                                <div class="text-block">
                                    <span class="stat-title text-orange-600 font-medium">En traitement</span>
                                    <div class="stat-value text-900 font-bold">{{ props.stats.traitement }}</div>
                                    <small class="text-500 hidden sm:block">En cours d'examen</small>
                                </div>
                                <div class="icon-circle bg-orange-100">
                                    <i class="pi pi-spinner text-orange-500 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Rejetées -->
                <div class="stat-card-wrapper">
                    <Card class="shadow-md hover:shadow-xl transition-all cursor-pointer border-left-4 border-red-500">
                        <template #content>
                            <div class="stat-content-container">
                                <div class="text-block">
                                    <span class="stat-title text-red-600 font-medium">Rejetées</span>
                                    <div class="stat-value text-900 font-bold">{{ props.stats.rejete }}</div>
                                    <small class="text-500 hidden sm:block">Non retenues</small>
                                </div>
                                <div class="icon-circle bg-red-100">
                                    <i class="pi pi-times-circle text-red-500 text-xl"></i>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Section principale - Tableaux optimisés -->
            <div class="grid">
                <!-- Suivi des dossiers - Sans scroll horizontal -->
                <div class="col-12 lg:col-8 mt-2">
                    <Card class="shadow-lg h-full">
                        <template #title>
                            <div class="flex flex-column sm:flex-row align-items-start sm:align-items-center gap-2">
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-list text-emerald-500"></i>
                                    <span class="text-lg sm:text-xl font-semibold">Suivi des dossiers</span>
                                </div>
                                <Badge :value="props.mes_candidatures.length" class="sm:ml-auto"></Badge>
                            </div>
                        </template>
                        <template #content>
                            <!-- Version Desktop : Tableau -->
                            <div class="hidden md:block">
                                <DataTable 
                                    :value="props.mes_candidatures" 
                                    :rows="5"
                                    v-model:expandedRows="expandedRows"
                                    dataKey="id"
                                    class="p-datatable-sm"
                                    stripedRows
                                    showGridlines
                                >
                                    <Column field="concour.intitule" header="Concours">
                                        <template #body="slotProps">
                                            <div class="flex align-items-center gap-2">
                                                <i class="pi pi-briefcase text-emerald-500"></i>
                                                <span class="font-medium">{{ slotProps.data.concour?.intitule }}</span>
                                            </div>
                                        </template>
                                    </Column>
                                    
                                    <Column header="Date">
                                        <template #body="slotProps">
                                            <div class="flex align-items-center gap-2">
                                                <i class="pi pi-calendar text-500"></i>
                                                <span class="text-sm text-600">{{ formatDate(slotProps.data.created_at) }}</span>
                                            </div>
                                        </template>
                                    </Column>
                                    
                                    <Column header="État">
                                        <template #body="slotProps">
                                            <Tag 
                                                :severity="getSeverity(slotProps.data.resultat)" 
                                                :value="slotProps.data.resultat?.toUpperCase() || 'EN ATTENTE'" 
                                                rounded
                                                :class="getSeverityProps(slotProps.data.resultat).class"
                                                class="px-3 py-1"
                                            />
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>
                            
                            <!-- Version Mobile : Cartes -->
                            <div class="md:hidden">
                                <div v-if="props.mes_candidatures.length === 0" class="text-center py-5">
                                    <i class="pi pi-inbox text-4xl text-300 mb-3"></i>
                                    <p class="text-500">Aucune candidature</p>
                                </div>
                                <div v-else class="flex flex-column gap-3">
                                    <div 
                                        v-for="candidature in props.mes_candidatures.slice(0, 5)" 
                                        :key="candidature.id"
                                        class="p-3 border-1 surface-border border-round-lg hover:surface-hover cursor-pointer transition-all"
                                    >
                                        <div class="flex align-items-center gap-3">
                                            <div class="w-10 h-10 bg-emerald-100 border-round-lg flex align-items-center justify-center flex-shrink-0">
                                                <i class="pi pi-briefcase text-emerald-500"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-900 m-0 truncate">{{ candidature.concour?.intitule }}</p>
                                                <div class="flex align-items-center gap-2 mt-1">
                                                    <i class="pi pi-calendar text-xs text-400"></i>
                                                    <span class="text-xs text-500">{{ formatDate(candidature.created_at) }}</span>
                                                </div>
                                            </div>
                                            <Tag 
                                                :severity="getSeverity(candidature.resultat)" 
                                                :value="candidature.resultat?.toUpperCase() || 'ATTENTE'" 
                                                rounded
                                                :class="getSeverityProps(candidature.resultat).class"
                                                class="px-2 py-1 text-xs"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Avis & Résultats - Avec bouton Voir plus fonctionnel -->
                <div class="col-12 lg:col-4 mt-2">
                    <Card class="shadow-lg h-full border-top-4 border-orange-400">
                        <template #title>
                            <div class="flex flex-column sm:flex-row align-items-start sm:align-items-center gap-2">
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-file-pdf text-orange-500"></i>
                                    <span class="text-lg sm:text-xl font-semibold">Avis & Résultats</span>
                                </div>
                                <Badge :value="props.resultats_publies.length" severity="warn" class="sm:ml-auto"></Badge>
                            </div>
                        </template>
                        <template #content>
                            <div v-if="props.resultats_publies.length === 0" class="text-center py-5">
                                <i class="pi pi-file-pdf text-4xl text-300 mb-3"></i>
                                <p class="text-500">Aucun résultat disponible</p>
                            </div>
                            <div v-else class="flex flex-column gap-2">
                                <!-- Version Desktop : Grille compacte -->
                                <div class="hidden md:block">
                                    <div 
                                        v-for="res in displayedResults" 
                                        :key="res.id" 
                                        @click="downloadFile(res.id)" 
                                        class="result-item p-3 border-1 surface-border border-round-lg flex align-items-center cursor-pointer hover:bg-orange-50 transition-all mb-2"
                                    >
                                        <div class="w-10 h-10 bg-orange-100 border-round-lg flex align-items-center justify-center mr-3 flex-shrink-0">
                                            <i class="pi pi-file-pdf text-orange-500 text-xl"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="m-0 font-semibold text-900 text-sm truncate">{{ res.intitule }}</p>
                                            <div class="flex align-items-center gap-2 mt-1">
                                                <i class="pi pi-calendar text-xs text-400 flex-shrink-0"></i>
                                                <small class="text-500 text-xs truncate">{{ formatDate(res.updated_at) }}</small>
                                            </div>
                                        </div>
                                        <i class="pi pi-download text-400 hover:text-orange-500 transition-colors ml-2 flex-shrink-0"></i>
                                    </div>
                                </div>
                                
                                <!-- Version Mobile : Cartes simples -->
                                <div class="md:hidden">
                                    <div 
                                        v-for="res in displayedResults" 
                                        :key="res.id" 
                                        @click="downloadFile(res.id)" 
                                        class="p-3 border-1 surface-border border-round-lg flex align-items-center cursor-pointer hover:bg-orange-50 transition-all mb-2"
                                    >
                                        <div class="w-8 h-8 bg-orange-100 border-round-lg flex align-items-center justify-center mr-2 flex-shrink-0">
                                            <i class="pi pi-file-pdf text-orange-500 text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="m-0 font-medium text-900 text-sm truncate">{{ res.intitule }}</p>
                                            <small class="text-500 text-xs">{{ formatDate(res.updated_at) }}</small>
                                        </div>
                                        <i class="pi pi-download text-400 ml-2 flex-shrink-0 text-xs"></i>
                                    </div>
                                </div>
                                
                                <!-- Bouton Voir plus / Voir moins -->
                                <div v-if="props.resultats_publies.length > 4" class="text-center pt-2">
                                    <Button 
                                        :label="showAllResults ? 'Voir moins' : `Voir plus (${hiddenResultsCount})`" 
                                        icon="pi pi-chevron-down"
                                        :iconPos="showAllResults ? 'right' : 'right'"
                                        :class="showAllResults ? 'pi-chevron-up' : ''"
                                        link 
                                        class="p-button-sm text-orange-500 hover:text-orange-600"
                                        @click="toggleResults"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<style scoped>
.stats-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
}

.stat-card-wrapper {
    flex: 1 1 calc(25% - 1rem);
    min-width: 200px;
}

/* Conteneur des statistiques */
.stat-content-container {
    display: flex;
    align-items: center;
    width: 100%;
    justify-content: space-between;
    padding: 0.25rem 0;
}

/* Bloc texte */
.text-block {
    display: flex;
    flex-direction: column;
}

.stat-title {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.75rem;
    line-height: 1.2;
    margin-bottom: 0.125rem;
}

/* Cercle d'icône */
.icon-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 14px;
    flex-shrink: 0;
    transition: transform 0.2s;
}

.stat-card-wrapper:hover .icon-circle {
    transform: scale(1.1) rotate(5deg);
}

/* Items de résultats */
.result-item {
    transition: all 0.2s;
}

.result-item:hover {
    transform: translateX(4px);
    border-color: #f97316 !important;
}

/* Animation pour l'icône */
.pi-chevron-down, .pi-chevron-up {
    transition: transform 0.3s;
}

/* Responsive */
@media screen and (max-width: 1200px) {
    .stat-card-wrapper { 
        flex: 1 1 calc(50% - 1rem); 
        min-width: 180px;
    }
}

@media screen and (max-width: 768px) {
    .stat-card-wrapper { 
        flex: 1 1 100%; 
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .icon-circle {
        width: 40px;
        height: 40px;
    }
}

@media screen and (max-width: 576px) {
    .stat-title {
        font-size: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.25rem;
    }
    
    .icon-circle {
        width: 36px;
        height: 36px;
    }
    
    .icon-circle i {
        font-size: 1rem !important;
    }
}

.h-full { height: 100% !important; }
.truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Styles PrimeVue personnalisés */
:deep(.p-card) {
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

:deep(.p-card:hover) {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #1e293b;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 1rem;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: #f0fdf4;
}

:deep(.p-tag) {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
}

/* Dark mode */
:deep(.dark .p-datatable .p-datatable-thead > tr > th) {
    background: #1e293b;
    color: #e2e8f0;
}

:deep(.dark .p-datatable .p-datatable-tbody > tr:hover) {
    background: #1e293b;
}
</style>