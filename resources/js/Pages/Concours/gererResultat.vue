<script setup>
import { ref, computed, watch } from "vue"; // ⭐ AJOUTER computed ici
import { useToast } from "primevue/usetoast";
import { router, Head, useForm, Link } from "@inertiajs/vue3";
import axios from "axios";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Card from 'primevue/card';
import Badge from 'primevue/badge';
import Tooltip from 'primevue/tooltip';
import Toast from "primevue/toast";
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({ resultats: Object });
const toast = useToast();
const confirm = useConfirm();

// --- ETATS ---
const detailsDialog = ref(false);
const confirmPublierDialog = ref(false);
const deleteDialog = ref(false);
const editResultDialog = ref(false);

const loading = ref(false);
const selectedId = ref(null);
const selectedResultatSync = ref(null);
const searchQuery = ref('');

// Formulaire pour la modification
const form = useForm({ id: null, intitule: "" });

// Data pour la pagination du modal
const candidatsPagination = ref({ data: [], total: 0 });
const lazyParams = ref({ page: 1, search: '' });

// ⭐ Statistiques (utilise computed)
const stats = computed(() => {
    if (!props.resultats?.data) return { total: 0, publies: 0, brouillons: 0 };
    const data = props.resultats.data;
    return {
        total: data.length,
        publies: data.filter(r => r.statut === 'Publié').length,
        brouillons: data.filter(r => r.statut !== 'Publié').length
    };
});

// Debounce pour la recherche
let searchTimeout;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        lazyParams.value.search = newVal;
        lazyParams.value.page = 1;
        loadCandidats();
    }, 300);
});

// --- LOGIQUE CHARGEMENT CANDIDATS ---
const loadCandidats = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/concours/${selectedId.value}/candidats`, {
            params: { page: lazyParams.value.page, search: lazyParams.value.search }
        });
        candidatsPagination.value = response.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur de chargement', life: 3000 });
    } finally {
        loading.value = false;
    }
};

const ouvrirDetails = (data) => {
    selectedId.value = data.id;
    selectedResultatSync.value = data;
    lazyParams.value = { page: 1, search: '' };
    searchQuery.value = '';
    detailsDialog.value = true;
    loadCandidats();
};

const onPage = (event) => {
    lazyParams.value.page = event.page + 1;
    loadCandidats();
};

const exporterPdf = () => {
    window.open(`/concour-resultat/${selectedId.value}/export`, '_blank');
    toast.add({ severity: 'info', summary: 'PDF', detail: 'Génération du PDF en cours...', life: 2000 });
};

// --- LOGIQUE EDIT / DELETE ---
const modifierResultat = (data) => {
    selectedId.value = data.id;
    form.id = data.id;
    form.intitule = data.intitule;
    editResultDialog.value = true;
};

const updateResultat = () => {
    form.put(`/concour-gererResultat/${form.id}`, {
        only: ["resultats"],
        preserveScroll: true,
        onSuccess: () => {
            editResultDialog.value = false;
            toast.add({ severity: "success", summary: "Succès", detail: "Intitulé mis à jour", life: 3000 });
        },
    });
};

const confirmDelete = (id, intitule) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer le résultat "${intitule}" ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(`/concour-gererResultat/${id}`, {
                onSuccess: () => {
                    toast.add({ severity: "success", summary: "Succès", detail: "Supprimé avec succès", life: 3000 });
                },
            });
        }
    });
};

// --- LOGIQUE PUBLICATION ---
const publierResultat = () => {
    router.put(`/concour-gererResultat/${selectedId.value}`, { action: "publier" }, {
        onSuccess: () => {
            confirmPublierDialog.value = false;
            detailsDialog.value = false;
            toast.add({ severity: "success", summary: "Succès", detail: "Résultats publiés !", life: 3000 });
        },
    });
};

const changerStatut = async (candidat, nouveauStatut) => {
    try {
        await axios.put(`/concour-gererResultat/${selectedId.value}`, {
            candidature_id: candidat.id,
            nouveau_statut: nouveauStatut,
            motif: candidat.motif
        });
        candidat.resultat = nouveauStatut;
        toast.add({ severity: 'success', summary: 'Succès', detail: 'Mis à jour', life: 800 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur de mise à jour', life: 3000 });
    }
};

const getSeverity = (statut) => {
    return statut === 'Publié' ? 'success' : 'info';
};
</script>

<template>
    <AppLayout>
        <Head title="Gérer les Résultats" />
        <Toast />
        <ConfirmDialog />

        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-900">
                        <template #content>
                            <div class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4">
                                <div class="flex align-items-center gap-4">
                                    <div class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-center">
                                        <i class="pi pi-chart-line text-white text-3xl"></i>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold text-900 m-0 mb-2">Gestion des résultats</h1>
                                        <p class="text-600 m-0 flex align-items-center gap-2">
                                            <i class="pi pi-check-circle text-emerald-500"></i>
                                            Gérez les résultats des concours
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-4 flex-wrap justify-content-center">
                                    <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.total" severity="info" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Total</div>
                                    </div>
                                    <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.publies" severity="success" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Publiés</div>
                                    </div>
                                    <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.brouillons" severity="warn" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Brouillons</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Tableau des résultats -->
            <Card class="shadow-md">
                <template #title>
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-list text-emerald-500"></i>
                        <span class="text-lg font-semibold">Liste des résultats</span>
                    </div>
                </template>
                <template #content>
                    <DataTable 
                        :value="resultats.data" 
                        class="p-datatable-sm"
                        stripedRows
                        showGridlines
                        responsiveLayout="stack"
                    >
                        <Column field="intitule" header="Intitulé" sortable>
                            <template #body="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-file-pdf text-emerald-500"></i>
                                    <span class="font-medium">{{ slotProps.data.intitule }}</span>
                                </div>
                            </template>
                        </Column>
                        
                        <Column header="Fichier PDF">
                            <template #body="slotProps">
                                <a
                                    v-if="slotProps.data.fichier"
                                    :href="'/resultats/' + slotProps.data.id + '/view'"
                                    target="_blank"
                                    class="flex align-items-center gap-2 text-red-500 hover:text-red-600 no-underline"
                                    v-tooltip.top="'Consulter le PDF'"
                                >
                                    <i class="pi pi-file-pdf"></i>
                                    <span class="text-sm">Consulter</span>
                                </a>
                                <span v-else class="text-400 italic text-sm flex align-items-center gap-2">
                                    <i class="pi pi-file-excel"></i>
                                    Non généré
                                </span>
                            </template>
                        </Column>

                        <Column field="statut" header="Statut" sortable>
                            <template #body="slotProps">
                                <Tag 
                                    :value="slotProps.data.statut" 
                                    :severity="getSeverity(slotProps.data.statut)"
                                    rounded
                                    class="px-3 py-1"
                                />
                            </template>
                        </Column>
                        
                        <Column field="nbAdmis" header="Admis" sortable>
                            <template #body="slotProps">
                                <Badge :value="slotProps.data.nbAdmis" severity="success"></Badge>
                            </template>
                        </Column>
                        
                        <Column header="Actions" style="width: 12rem">
                            <template #body="slotProps">
                                <div class="flex gap-1">
                                    <Button
                                        icon="pi pi-pencil"
                                        class="p-button-rounded p-button-text p-button-sm text-emerald-600 hover:text-emerald-700"
                                        @click="modifierResultat(slotProps.data)"
                                        v-tooltip.top="'Modifier l\'intitulé'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="p-button-rounded p-button-text p-button-sm text-red-600 hover:text-red-700"
                                        @click="confirmDelete(slotProps.data.id, slotProps.data.intitule)"
                                        v-tooltip.top="'Supprimer'"
                                    />
                                    <Button
                                        icon="pi pi-list"
                                        label="Détails"
                                        class="p-button-sm"
                                        @click="ouvrirDetails(slotProps.data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="flex align-items-center justify-content-center mt-4 gap-1">
                        <Link 
                            v-for="link in resultats.links" 
                            :key="link.label"
                            :href="link.url || '#'"
                            class="p-button p-button-sm no-underline"
                            :class="{
                                'p-button-primary': link.active,
                                'p-button-outlined p-button-secondary': !link.active,
                                'opacity-50 pointer-events-none': !link.url
                            }"
                            v-html="link.label"
                        />
                    </div>
                </template>
            </Card>

            <!-- Dialog des détails -->
            <Dialog 
                v-model:visible="detailsDialog" 
                :style="{ width: '95vw', maxWidth: '1400px' }" 
                header="Interface de décision" 
                modal 
                maximizable
                class="p-fluid"
            >
                <div v-if="selectedResultatSync" class="p-2">
                    <!-- En-tête du dialog -->
                    <div class="flex flex-column lg:flex-row lg:justify-content-between lg:align-items-center mb-4 gap-3 p-3 bg-emerald-50 border-round-lg">
                        <div>
                            <h3 class="text-xl font-bold text-900 m-0">{{ selectedResultatSync.intitule }}</h3>
                            <div class="flex align-items-center gap-2 mt-2">
                                <Badge :value="selectedResultatSync.statut" :severity="getSeverity(selectedResultatSync.statut)"></Badge>
                                <span class="text-500 text-sm">
                                    <i class="pi pi-users mr-1"></i>
                                    {{ candidatsPagination.total }} candidats
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap align-items-center gap-2">
                            <IconField iconPosition="left">
                                <InputIcon class="pi pi-search" />
                                <InputText 
                                    v-model="searchQuery" 
                                    placeholder="Rechercher un candidat..." 
                                    class="p-inputtext-sm"
                                />
                            </IconField>
                            
                            <Button 
                                label="Exporter PDF" 
                                icon="pi pi-file-pdf" 
                                severity="secondary"
                                class="p-button-sm"
                                @click="exporterPdf" 
                            />
                            <Button 
                                label="Publier" 
                                icon="pi pi-send" 
                                severity="warning"
                                class="p-button-sm"
                                @click="confirmPublierDialog = true" 
                                :disabled="selectedResultatSync.statut === 'Publié'" 
                            />
                        </div>
                    </div>

                    <!-- Tableau des candidats -->
                    <DataTable 
                        :value="candidatsPagination.data" 
                        lazy 
                        paginator 
                        :rows="50" 
                        :totalRecords="candidatsPagination.total" 
                        @page="onPage"
                        :loading="loading" 
                        class="p-datatable-sm" 
                        stripedRows
                        showGridlines
                        responsiveLayout="scroll"
                    >
                        <Column field="num_dossier" header="N° Dossier" style="width: 10%"></Column>
                        <Column field="profil.prenom" header="Prénom" style="width: 15%"></Column>
                        <Column field="profil.nom" header="Nom" style="width: 15%"></Column>
                        <Column header="Résultat" style="width: 15%">
                            <template #body="sp">
                                <Tag 
                                    :value="sp.data.resultat" 
                                    :severity="sp.data.resultat === 'Admis' ? 'success' : 'danger'"
                                    rounded
                                />
                            </template>
                        </Column>
                        <Column header="Observations" style="width: 25%">
                            <template #body="sp">
                                <InputText 
                                    v-model="sp.data.motif" 
                                    class="p-inputtext-sm w-full" 
                                    @blur="changerStatut(sp.data, sp.data.resultat)" 
                                    placeholder="Motif..."
                                />
                            </template>
                        </Column>
                        <Column header="Actions" style="width: 20%" class="text-center">
                            <template #body="sp">
                                <div class="flex gap-1 justify-content-center">
                                    <Button 
                                        v-if="sp.data.resultat === 'Admis'" 
                                        label="Rejeter" 
                                        icon="pi pi-times" 
                                        severity="danger" 
                                        text
                                        class="p-button-sm"
                                        @click="changerStatut(sp.data, 'Rejété')"
                                        v-tooltip.top="'Rejeter la candidature'"
                                    />
                                    <Button 
                                        v-else 
                                        label="Accepter" 
                                        icon="pi pi-check" 
                                        severity="success" 
                                        text
                                        class="p-button-sm"
                                        @click="changerStatut(sp.data, 'Admis')"
                                        v-tooltip.top="'Accepter la candidature'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </Dialog>

            <!-- Dialog de confirmation publication -->
            <Dialog 
                v-model:visible="confirmPublierDialog" 
                header="Confirmation" 
                modal 
                :style="{ width: '450px' }"
                class="p-fluid"
            >
                <div class="flex flex-column align-items-center gap-4 p-4">
                    <div class="w-16 h-16 bg-orange-100 border-circle flex align-items-center justify-content-center">
                        <i class="pi pi-exclamation-triangle text-3xl text-orange-600"></i>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">Publier?</h3>
                        <p class="text-sm text-600">Les résultats seront visibles par tous les candidats.</p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-content-center gap-2">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-outlined p-button-secondary" @click="confirmPublierDialog = false" />
                        <Button label="Oui, publier" icon="pi pi-send" class="p-button-warning" @click="publierResultat" />
                    </div>
                </template>
            </Dialog>

            <!-- Dialog de modification -->
            <Dialog 
                v-model:visible="editResultDialog" 
                header="Modifier l'intitulé" 
                modal 
                :style="{ width: '500px' }" 
                class="p-fluid"
            >
                <div class="field mt-3">
                    <label class="font-medium text-600 mb-2 block">
                        <i class="pi pi-tag mr-2 text-emerald-500"></i>
                        Intitulé du résultat
                    </label>
                    <InputText 
                        v-model="form.intitule" 
                        placeholder="Nouvel intitulé..."
                        :class="{ 'p-invalid': form.errors.intitule }"
                    />
                    <small v-if="form.errors.intitule" class="p-error block mt-1">{{ form.errors.intitule }}</small>
                </div>
                <template #footer>
                    <div class="flex justify-content-end gap-2">
                        <Button label="Annuler" icon="pi pi-times" class="p-button-outlined p-button-secondary" @click="editResultDialog = false" />
                        <Button label="Enregistrer" icon="pi pi-check" :loading="form.processing" @click="updateResultat" />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animation d'entrée */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.p-card {
    animation: fadeInUp 0.3s ease-out;
}

/* Amélioration du focus */
:deep(.p-inputtext:focus),
:deep(.p-button:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

/* Pagination */
:deep(.p-paginator) {
    padding: 1rem 0;
}

:deep(.p-paginator .p-paginator-page.p-highlight) {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .flex.gap-2 {
        flex-wrap: wrap;
    }
}
</style>