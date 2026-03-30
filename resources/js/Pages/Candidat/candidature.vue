<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/sakai/layout/AppLayout.vue';
import DataTable from 'primevue/datatable'; 
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield'; 
import InputIcon from 'primevue/inputicon';
import { ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps({
    candidatures: Array
});

// Configuration des filtres pour la recherche globale
const dtFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

/**
 * Logique de couleur demandée :
 * Admis -> Vert (success)
 * Rejeté -> Rouge (danger)
 * Traitement -> Bleu (info)
 */
const getResultatSeverity = (res) => {
    switch (res) {
        case 'Admis': return 'success';
        case 'Rejeté': return 'danger';
        case 'Traitement': return 'info';
        default: return 'secondary';
    }
};

const viewDetails = (id) => {
    router.visit(route('candidature.show', id));
};
</script>


<template>
    <AppLayout>
        <Head title="Mes Candidatures" /> 

        <div class="grid">
            <div class="col-12">
                <div class="card border-none shadow-sm sakai-card">
                    <!-- Header -->
                    <div class="flex flex-column md:flex-row md:align-items-center justify-content-between mb-5 gap-3">
                        <div>
                            <h5 class="m-0 text-2xl font-light uppercase tracking-wider text-900">
                                <i class="pi pi-briefcase mr-2 text-emerald-500"></i>Suivi de mes candidatures
                            </h5>
                            <p class="text-surface-500 m-0 mt-1">État d'avancement de vos dossiers en temps réel.</p>
                        </div>

                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="dtFilters['global'].value" placeholder="Rechercher un dossier..." class="w-full md:w-20rem" />
                        </IconField>
                    </div>

                    <!-- Table -->
                    <DataTable 
                        :value="candidatures" 
                        :filters="dtFilters"
                        dataKey="id" 
                        :rows="10" 
                        :paginator="true" 
                        :globalFilterFields="['num_dossier', 'concours', 'motif']"
                        responsiveLayout="stack" 
                        breakpoint="960px"
                        stripedRows
                        class="p-datatable-sm"
                    >
                        <template #empty>
                            <div class="text-center p-8 border-round bg-surface-50">
                                <i class="pi pi-folder-open text-4xl block mb-3 text-400"></i>
                                <span class="text-surface-600">Aucun dossier trouvé.</span>
                            </div>
                        </template>

                        <Column field="num_dossier" header="N° DOSSIER" sortable>
                            <template #body="slotProps">
                                <span class="font-bold text-emerald-600">{{ slotProps.data.num_dossier }}</span>
                            </template>
                        </Column>

                        <Column field="concours" header="CONCOURS" sortable></Column>

                        <Column field="date" header="DATE SOUMISSION" sortable></Column>

                        <!-- Colonne Résultat avec les couleurs personnalisées -->
                        <Column field="resultat" header="RÉSULTAT">
                            <template #body="slotProps">
                                <Tag 
                                    :value="slotProps.data.resultat" 
                                    :severity="getResultatSeverity(slotProps.data.resultat)" 
                                    class="px-3 text-[10px] uppercase font-bold"
                                />
                            </template>
                        </Column>

                        <!-- Colonne Motif (placée après résultat) -->
                        <Column field="motif" header="MOTIF">
                            <template #body="slotProps">
                                <span class="text-sm text-surface-600 italic">
                                    {{ slotProps.data.motif || 'Aucun motif spécifié' }}
                                </span>
                            </template>
                        </Column>

                        <!-- Actions (Seulement le bouton de vue) -->
                        <Column headerStyle="width: 4rem" bodyStyle="text-align: center">
                            <template #body="slotProps">
                                <Button 
                                    icon="pi pi-arrow-right" 
                                    class="p-button-text p-button-rounded text-emerald-500" 
                                    @click="viewDetails(slotProps.data.id)" 
                                />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


<style lang="scss" scoped>
.sakai-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    padding: 2rem;
}

:deep(.p-datatable-thead > tr > th) {
    background-color: var(--surface-50);
    color: var(--surface-700);
    font-weight: 700;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 1rem;
}

:deep(.p-datatable-tbody > tr > td) {
    padding: 1rem;
}

/* Optimisation Mobile */
@media screen and (max-width: 960px) {
    :deep(.p-datatable.p-datatable-stack .p-datatable-tbody > tr > td) {
        .p-column-title {
            font-weight: 700;
            color: var(--emerald-600);
            text-transform: uppercase;
            font-size: 0.7rem;
            margin-bottom: 0.5rem;
        }
    }
}
</style>

