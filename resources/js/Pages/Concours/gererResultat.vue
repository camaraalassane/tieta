<script setup>
import { ref, computed, watch } from "vue";
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
import Card from "primevue/card";
import Badge from "primevue/badge";
import Message from "primevue/message";
import Tooltip from "primevue/tooltip";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    resultats: Object,
    // ⭐ AJOUT : Nouvelles props
    user_role: String,
    is_superadmin: {
        type: Boolean,
        default: false,
    },
    is_gerant: {
        type: Boolean,
        default: false,
    },
    is_admin: {
        type: Boolean,
        default: false,
    },
    userService: {
        type: Object,
        default: null,
    },
});

const toast = useToast();
const confirm = useConfirm();

// --- ETATS ---
const detailsDialog = ref(false);
const confirmPublierDialog = ref(false);
const openUploadPdfDialog = ref(false);
const deleteDialog = ref(false);
const editResultDialog = ref(false);

const loading = ref(false);
const selectedId = ref(null);
const selectedResultatSync = ref(null);
const searchQuery = ref("");
const hasSpecialites = ref(false);

// Formulaire pour la modification
const form = useForm({ id: null, intitule: "" });

// Formulaire pour l'upload PDF
const uploadForm = useForm({ pdf_file: null });
const uploadFileName = ref("");

// Data pour la pagination du modal
const candidatsPagination = ref({ data: [], total: 0 });
const lazyParams = ref({ page: 1, search: "" });

// ⭐ AJOUT : Nombre de résultats disponibles
const nombreResultatsDisponibles = computed(() => {
    return props.resultats?.data?.length || 0;
});

// Statistiques
const stats = computed(() => {
    if (!props.resultats?.data) return { total: 0, publies: 0, brouillons: 0 };
    const data = props.resultats.data;
    return {
        total: data.length,
        publies: data.filter((r) => r.statut === "Publié").length,
        brouillons: data.filter((r) => r.statut !== "Publié").length,
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
        const response = await axios.get(
            `/concours/${selectedId.value}/candidats`,
            {
                params: {
                    page: lazyParams.value.page,
                    search: lazyParams.value.search,
                },
            },
        );
        candidatsPagination.value = response.data;
        hasSpecialites.value = response.data.has_specialites || false;
    } catch (e) {
        console.error("Erreur chargement:", e);
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: e.response?.data?.error || "Erreur de chargement",
            life: 3000,
        });
    } finally {
        loading.value = false;
    }
};

const ouvrirDetails = (data) => {
    selectedId.value = data.id;
    selectedResultatSync.value = data;
    lazyParams.value = { page: 1, search: "" };
    searchQuery.value = "";
    detailsDialog.value = true;
    loadCandidats();
};

const onPage = (event) => {
    lazyParams.value.page = event.page + 1;
    loadCandidats();
};

// Export PDF
const exporterPdf = () => {
    window.open(`/concour-resultat/${selectedId.value}/export`, "_blank");
    toast.add({
        severity: "info",
        summary: "PDF",
        detail: "Génération du PDF en cours...",
        life: 2000,
    });
};

const exporterExcel = () => {
    window.open(
        `/concour-gererResultat/${selectedId.value}/export-excel`,
        "_blank",
    );
    toast.add({
        severity: "info",
        summary: "Excel",
        detail: "Génération du fichier Excel en cours...",
        life: 2000,
    });
};

// Gestion de l'upload PDF
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file && file.type === "application/pdf") {
        uploadForm.pdf_file = file;
        uploadFileName.value = file.name;
    } else {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez sélectionner un fichier PDF valide",
            life: 3000,
        });
    }
};

const uploadAndPublish = () => {
    if (!uploadForm.pdf_file) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez sélectionner un fichier PDF",
            life: 3000,
        });
        return;
    }

    uploadForm.post(
        `/concour-gererResultat/${selectedId.value}/publier-fichier`,
        {
            forceFormData: true,
            onSuccess: () => {
                openUploadPdfDialog.value = false;
                uploadForm.reset();
                uploadFileName.value = "";
                detailsDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Résultat publié avec succès",
                    life: 3000,
                });
                router.reload();
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: errors.message || "Erreur lors de la publication",
                    life: 3000,
                });
            },
        },
    );
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
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Intitulé mis à jour",
                life: 3000,
            });
        },
    });
};

const confirmDelete = (id, intitule) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer le résultat "${intitule}" ?`,
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(`/concour-gererResultat/${id}`, {
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Succès",
                        detail: "Supprimé avec succès",
                        life: 3000,
                    });
                },
            });
        },
    });
};

// --- LOGIQUE PUBLICATION ---
const publierResultat = () => {
    router.put(
        `/concour-gererResultat/${selectedId.value}`,
        { action: "publier" },
        {
            onSuccess: () => {
                confirmPublierDialog.value = false;
                detailsDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Résultats publiés !",
                    life: 3000,
                });
            },
        },
    );
};

// Fonction pour changer le statut
const changerStatut = async (candidat, nouveauStatut) => {
    try {
        const response = await axios.put(
            `/concour-gererResultat/${selectedId.value}`,
            {
                candidature_id: candidat.id,
                nouveau_statut: nouveauStatut,
                motif: candidat.motif || "",
            },
            {
                headers: {
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
            },
        );

        if (response.data.success) {
            candidat.resultat = nouveauStatut;
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: `Candidat ${nouveauStatut === "Admis" ? "accepté" : "rejeté"} avec succès`,
                life: 2000,
            });
            await loadCandidats();
        }
    } catch (e) {
        console.error("Erreur détaillée:", e.response?.data || e);
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail:
                e.response?.data?.message ||
                "Erreur lors du changement de statut",
            life: 3000,
        });
    }
};

const getSeverity = (statut) => {
    return statut === "Publié" ? "success" : "info";
};
</script>

<template>
    <AppLayout>
        <Head title="Gérer les Résultats" />
        <Toast />
        <ConfirmDialog />

        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <!-- ⭐ En-tête dynamique -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card
                        class="header-card shadow-lg border-none overflow-hidden"
                    >
                        <template #content>
                            <div
                                class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4"
                            >
                                <div class="flex align-items-center gap-4">
                                    <div class="header-icon-wrapper">
                                        <i
                                            class="pi pi-chart-line text-white text-3xl"
                                        ></i>
                                    </div>
                                    <div>
                                        <h1
                                            class="text-3xl font-bold text-900 m-0 mb-2"
                                        >
                                            Gestion des résultats
                                        </h1>
                                        <p
                                            class="text-600 m-0 flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-check-circle theme-text-primary"
                                            ></i>
                                            Gérez les résultats des concours
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="flex gap-4 flex-wrap justify-content-center"
                                >
                                    <div class="stat-badge-item">
                                        <Badge
                                            :value="stats.total"
                                            severity="info"
                                            class="mb-1"
                                        ></Badge>
                                        <div class="text-xs text-500">
                                            Total
                                        </div>
                                    </div>
                                    <div class="stat-badge-item">
                                        <Badge
                                            :value="stats.publies"
                                            severity="success"
                                            class="mb-1"
                                        ></Badge>
                                        <div class="text-xs text-500">
                                            Publiés
                                        </div>
                                    </div>
                                    <div class="stat-badge-item">
                                        <Badge
                                            :value="stats.brouillons"
                                            severity="warn"
                                            class="mb-1"
                                        ></Badge>
                                        <div class="text-xs text-500">
                                            Brouillons
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Messages d'information selon le rôle -->
            <div v-if="is_superadmin" class="mb-4">
                <Message severity="success" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-crown"></i>
                        <span
                            ><strong>Super Administrateur</strong> - Vous avez
                            accès à tous les résultats de tous les
                            services.</span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_gerant && userService" class="mb-4">
                <Message severity="success" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-building"></i>
                        <span
                            ><strong
                                >Gérant - Service {{ userService.nom }}</strong
                            >
                            - Vous gérez les résultats de tous les concours de
                            votre service.</span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_admin && userService" class="mb-4">
                <Message severity="info" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-user"></i>
                        <span
                            ><strong
                                >Administrateur - Service
                                {{ userService.nom }}</strong
                            >
                            - Vous gérez uniquement les résultats des concours
                            qui vous sont assignés.</span
                        >
                    </div>
                </Message>
            </div>

            <!-- Message si aucun résultat disponible -->
            <div v-if="nombreResultatsDisponibles === 0" class="mb-4">
                <Message severity="warn" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-exclamation-triangle"></i>
                        <span
                            >Aucun résultat disponible pour votre profil.</span
                        >
                    </div>
                </Message>
            </div>

            <!-- Tableau des résultats -->
            <Card class="shadow-md" v-if="nombreResultatsDisponibles > 0">
                <template #title>
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-list theme-text-primary"></i>
                        <span class="text-lg font-semibold"
                            >Liste des résultats</span
                        >
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
                                    <i
                                        class="pi pi-file-pdf theme-text-primary"
                                    ></i>
                                    <span class="font-medium">{{
                                        slotProps.data.intitule
                                    }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Fichier PDF">
                            <template #body="slotProps">
                                <a
                                    v-if="slotProps.data.fichier"
                                    :href="
                                        '/resultats/' +
                                        slotProps.data.id +
                                        '/view'
                                    "
                                    target="_blank"
                                    class="flex align-items-center gap-2 text-red-500 hover:text-red-600 no-underline"
                                    v-tooltip.top="'Consulter le PDF'"
                                >
                                    <i class="pi pi-file-pdf"></i>
                                    <span class="text-sm">Consulter</span>
                                </a>
                                <span
                                    v-else
                                    class="text-400 italic text-sm flex align-items-center gap-2"
                                >
                                    <i class="pi pi-file-excel"></i>
                                    Non généré
                                </span>
                            </template>
                        </Column>

                        <Column field="statut" header="Statut" sortable>
                            <template #body="slotProps">
                                <Tag
                                    :value="slotProps.data.statut"
                                    :severity="
                                        getSeverity(slotProps.data.statut)
                                    "
                                    rounded
                                    class="px-3 py-1"
                                />
                            </template>
                        </Column>

                        <Column field="nbAdmis" header="Admis" sortable>
                            <template #body="slotProps">
                                <Badge
                                    :value="slotProps.data.nbAdmis"
                                    severity="success"
                                ></Badge>
                            </template>
                        </Column>

                        <Column field="nbRejetes" header="Rejetés" sortable>
                            <template #body="slotProps">
                                <Badge
                                    :value="slotProps.data.nbRejetes"
                                    severity="danger"
                                ></Badge>
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 12rem">
                            <template #body="slotProps">
                                <div class="flex gap-1">
                                    <Button
                                        icon="pi pi-pencil"
                                        class="p-button-rounded p-button-text p-button-sm theme-text-primary hover:theme-text-primary-dark"
                                        @click="
                                            modifierResultat(slotProps.data)
                                        "
                                        v-tooltip.top="'Modifier l\'intitulé'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="p-button-rounded p-button-text p-button-sm text-red-600 hover:text-red-700"
                                        @click="
                                            confirmDelete(
                                                slotProps.data.id,
                                                slotProps.data.intitule,
                                            )
                                        "
                                        v-tooltip.top="'Supprimer'"
                                    />
                                    <Button
                                        icon="pi pi-list"
                                        label="Détails"
                                        class="p-button-sm btn-outlined-primary"
                                        @click="ouvrirDetails(slotProps.data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- ⭐ Pagination corrigée -->
                    <div
                        class="flex align-items-center justify-content-center mt-4 gap-1"
                    >
                        <Link
                            v-for="link in resultats.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="pagination-link no-underline"
                            :class="{
                                'pagination-active': link.active,
                                'pagination-inactive': !link.active,
                                'pagination-disabled': !link.url,
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
                    <div
                        class="flex flex-column lg:flex-row lg:justify-content-between lg:align-items-center mb-4 gap-3 p-3 info-highlight-box border-round-lg"
                    >
                        <div>
                            <h3 class="text-xl font-bold text-900 m-0">
                                {{ selectedResultatSync.intitule }}
                            </h3>
                            <div class="flex align-items-center gap-2 mt-2">
                                <Badge
                                    :value="selectedResultatSync.statut"
                                    :severity="
                                        getSeverity(selectedResultatSync.statut)
                                    "
                                ></Badge>
                                <span class="text-500 text-sm">
                                    <i class="pi pi-users mr-1"></i>
                                    {{ candidatsPagination.total }} candidats
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap align-items-center gap-2">
                            <IconField iconPosition="left">
                                <InputText
                                    v-model="searchQuery"
                                    placeholder="Rechercher un candidat..."
                                    class="p-inputtext-sm"
                                />
                            </IconField>

                            <Button
                                label="Exporter PDF"
                                icon="pi pi-file-pdf"
                                class="p-button-sm export-pdf-btn"
                                @click="exporterPdf"
                            />

                            <Button
                                label="Exporter Excel"
                                icon="pi pi-file-excel"
                                severity="success"
                                class="p-button-sm"
                                @click="exporterExcel"
                            />

                            <Button
                                label="Publier avec fichier"
                                icon="pi pi-upload"
                                severity="info"
                                class="p-button-sm"
                                @click="openUploadPdfDialog = true"
                                :disabled="
                                    selectedResultatSync.statut === 'Publié'
                                "
                            />

                            <Button
                                label="Publier (auto)"
                                icon="pi pi-send"
                                severity="warning"
                                class="p-button-sm"
                                @click="confirmPublierDialog = true"
                                :disabled="
                                    selectedResultatSync.statut === 'Publié'
                                "
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
                        <Column
                            field="num_dossier"
                            header="N° Dossier"
                            style="width: 10%"
                        >
                            <template #body="sp">
                                <span class="font-mono font-medium">{{
                                    sp.data.num_dossier
                                }}</span>
                            </template>
                        </Column>
                        <Column
                            field="profil.prenom"
                            header="Prénom"
                            style="width: 12%"
                        >
                            <template #body="sp">
                                {{ sp.data.profil?.prenom || "-" }}
                            </template>
                        </Column>
                        <Column
                            field="profil.nom"
                            header="Nom"
                            style="width: 12%"
                        >
                            <template #body="sp">
                                {{ sp.data.profil?.nom || "-" }}
                            </template>
                        </Column>

                        <Column
                            v-if="hasSpecialites"
                            field="specialite.nom"
                            header="Spécialité"
                            style="width: 15%"
                        >
                            <template #body="sp">
                                <Tag
                                    :value="
                                        sp.data.specialite?.nom ||
                                        'Non spécifiée'
                                    "
                                    severity="info"
                                    rounded
                                    class="px-2 py-1 text-xs"
                                />
                            </template>
                        </Column>

                        <Column header="Résultat" style="width: 12%">
                            <template #body="sp">
                                <Tag
                                    :value="sp.data.resultat"
                                    :severity="
                                        sp.data.resultat === 'Admis'
                                            ? 'success'
                                            : 'danger'
                                    "
                                    rounded
                                    class="px-3 py-1"
                                />
                            </template>
                        </Column>

                        <Column header="Observations" style="width: 25%">
                            <template #body="sp">
                                <InputText
                                    v-model="sp.data.motif"
                                    class="p-inputtext-sm w-full"
                                    @blur="
                                        changerStatut(sp.data, sp.data.resultat)
                                    "
                                    placeholder="Motif..."
                                />
                            </template>
                        </Column>

                        <Column
                            header="Actions"
                            style="width: 16%"
                            class="text-center"
                        >
                            <template #body="sp">
                                <div class="flex gap-1 justify-content-center">
                                    <Button
                                        v-if="sp.data.resultat === 'Admis'"
                                        label="Rejeter"
                                        icon="pi pi-times"
                                        severity="danger"
                                        text
                                        class="p-button-sm"
                                        @click="
                                            changerStatut(sp.data, 'Rejété')
                                        "
                                        v-tooltip.top="'Rejeter la candidature'"
                                    />
                                    <Button
                                        v-else-if="
                                            sp.data.resultat === 'Rejété'
                                        "
                                        label="Accepter"
                                        icon="pi pi-check"
                                        severity="success"
                                        text
                                        class="p-button-sm"
                                        @click="changerStatut(sp.data, 'Admis')"
                                        v-tooltip.top="
                                            'Accepter la candidature'
                                        "
                                    />
                                    <template v-else>
                                        <Button
                                            label="Admettre"
                                            icon="pi pi-check"
                                            severity="success"
                                            text
                                            class="p-button-sm"
                                            @click="
                                                changerStatut(sp.data, 'Admis')
                                            "
                                            v-tooltip.top="'Admettre'"
                                        />
                                        <Button
                                            label="Rejeter"
                                            icon="pi pi-times"
                                            severity="danger"
                                            text
                                            class="p-button-sm"
                                            @click="
                                                changerStatut(sp.data, 'Rejété')
                                            "
                                            v-tooltip.top="'Rejeter'"
                                        />
                                    </template>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </Dialog>

            <!-- Dialog de confirmation publication auto -->
            <Dialog
                v-model:visible="confirmPublierDialog"
                header="Confirmation"
                modal
                :style="{ width: '450px' }"
                class="p-fluid"
            >
                <div class="flex flex-column align-items-center gap-4 p-4">
                    <div
                        class="w-16 h-16 bg-orange-100 border-circle flex align-items-center justify-content-center"
                    >
                        <i
                            class="pi pi-exclamation-triangle text-3xl text-orange-600"
                        ></i>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">Publier?</h3>
                        <p class="text-sm text-600">
                            Les résultats seront visibles par tous les
                            candidats.
                        </p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-content-center gap-2">
                        <Button
                            label="Annuler"
                            icon="pi pi-times"
                            class="btn-outlined-primary"
                            @click="confirmPublierDialog = false"
                        />
                        <Button
                            label="Oui, publier"
                            icon="pi pi-send"
                            class="p-button-warning"
                            @click="publierResultat"
                        />
                    </div>
                </template>
            </Dialog>

            <!-- Dialog pour uploader un PDF -->
            <Dialog
                v-model:visible="openUploadPdfDialog"
                header="Publier avec un fichier PDF"
                modal
                :style="{ width: '500px' }"
                class="p-fluid"
            >
                <div class="flex flex-column gap-4 p-3">
                    <div class="field">
                        <label class="font-medium text-600 mb-2 block">
                            <i class="pi pi-file-pdf mr-2 text-red-500"></i>
                            Sélectionner le fichier PDF
                        </label>
                        <div class="flex align-items-center gap-2 flex-wrap">
                            <input
                                type="file"
                                @change="handleFileUpload"
                                accept=".pdf"
                                class="hidden"
                                ref="pdfInput"
                                id="pdf-upload"
                            />
                            <Button
                                type="button"
                                icon="pi pi-upload"
                                label="Choisir un fichier"
                                class="btn-outlined-primary"
                                @click="$refs.pdfInput.click()"
                            />
                            <span
                                v-if="uploadFileName"
                                class="text-sm text-600"
                            >
                                <i class="pi pi-file-pdf text-red-500 mr-1"></i>
                                {{ uploadFileName }}
                            </span>
                        </div>
                        <small class="text-400 text-xs"
                            >Format PDF uniquement (max 30 Mo)</small
                        >
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-content-end gap-2">
                        <Button
                            label="Annuler"
                            icon="pi pi-times"
                            class="btn-outlined-primary"
                            @click="openUploadPdfDialog = false"
                        />
                        <Button
                            label="Publier"
                            icon="pi pi-send"
                            :loading="uploadForm.processing"
                            class="btn-primary"
                            @click="uploadAndPublish"
                        />
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
                        <i class="pi pi-tag mr-2 theme-text-primary"></i>
                        Intitulé du résultat
                    </label>
                    <InputText
                        v-model="form.intitule"
                        placeholder="Nouvel intitulé..."
                        :class="{ 'p-invalid': form.errors.intitule }"
                    />
                    <small
                        v-if="form.errors.intitule"
                        class="p-error block mt-1"
                        >{{ form.errors.intitule }}</small
                    >
                </div>
                <template #footer>
                    <div class="flex justify-content-end gap-2">
                        <Button
                            label="Annuler"
                            icon="pi pi-times"
                            class="btn-outlined-primary"
                            @click="editResultDialog = false"
                        />
                        <Button
                            label="Enregistrer"
                            icon="pi pi-check"
                            :loading="form.processing"
                            class="btn-primary"
                            @click="updateResultat"
                        />
                    </div>
                </template>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ============================================ */
/* ⭐ HEADER - DYNAMIQUE */
/* ============================================ */
.header-card {
    background: linear-gradient(
        to right,
        var(--color-primary-light),
        white
    ) !important;
}

.dark .header-card {
    background: linear-gradient(
        to right,
        rgba(16, 185, 129, 0.15),
        #1f2937
    ) !important;
}

.header-icon-wrapper {
    width: 4rem;
    height: 4rem;
    background: var(--color-primary);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* ⭐ STAT BADGES */
.stat-badge-item {
    text-align: center;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.dark .stat-badge-item {
    background: #1f2937;
}

.stat-badge-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* ⭐ ICÔNE THÈME */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ⭐ INFO HIGHLIGHT BOX */
.info-highlight-box {
    background: var(--color-primary-light);
}

/* ⭐ BOUTONS */
.btn-primary {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-outlined-primary {
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    background: transparent !important;
    transition: all 0.2s ease;
}

.btn-outlined-primary:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ PAGINATION */
.btn-pagination-active {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: white !important;
}

.btn-pagination-inactive {
    color: var(--color-primary) !important;
    border-color: var(--color-primary-light) !important;
}

.btn-pagination-inactive:hover {
    background: var(--color-primary-light) !important;
}

/* ⭐ BOUTON EXPORT PDF */
.export-pdf-btn {
    background-color: #fecaca !important;
    border-color: #fecaca !important;
    color: #991b1b !important;
}

.export-pdf-btn:hover {
    background-color: #fca5a5 !important;
    border-color: #fca5a5 !important;
    color: #7f1d1d !important;
}

/* ============================================ */
/* ⭐ FOCUS GLOBAL */
/* ============================================ */
:deep(.p-inputtext:focus),
:deep(.p-dropdown:focus),
:deep(.p-select:focus),
:deep(.p-textarea:focus),
:deep(.p-button:focus) {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ BADGES DYNAMIQUES */
:deep(.p-badge.p-badge-info) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

:deep(.p-badge.p-badge-success) {
    background: var(--color-primary) !important;
}

/* ⭐ TAGS SUCCESS */
:deep(.p-tag.p-tag-success) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ DATATABLE */
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--color-primary-light) !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    border-bottom: 2px solid var(--color-primary-light);
}

/* ⭐ ANIMATION */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header-card {
    animation: slideDown 0.4s ease-out;
}

/* ⭐ DARK MODE */
.dark .export-pdf-btn {
    background-color: #7f1d1d !important;
    border-color: #7f1d1d !important;
    color: #fecaca !important;
}

.dark .export-pdf-btn:hover {
    background-color: #991b1b !important;
    border-color: #991b1b !important;
    color: #fca5a5 !important;
}
/* ⭐ PAGINATION - DYNAMIQUE */
.pagination-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    height: 2.5rem;
    padding: 0 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
}

/* ⭐ Page active */
.pagination-active {
    background: var(--color-primary) !important;
    color: white !important;
    border: 2px solid var(--color-primary) !important;
    font-weight: 700 !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pagination-active:hover {
    background: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
    color: white !important;
}

/* ⭐ Page inactive */
.pagination-inactive {
    background: white !important;
    color: var(--color-primary-dark) !important;
    border: 2px solid var(--color-primary-light) !important;
}

.pagination-inactive:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    border-color: var(--color-primary) !important;
}

/* ⭐ Page désactivée */
.pagination-disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f3f4f6 !important;
    color: #9ca3af !important;
    border: 2px solid #e5e7eb !important;
}

.pagination-disabled:hover {
    background: #f3f4f6 !important;
    color: #9ca3af !important;
    border-color: #e5e7eb !important;
}

/* ⭐ Dark mode */
.dark .pagination-inactive {
    background: #1f2937 !important;
    color: var(--color-primary-light) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
}

.dark .pagination-inactive:hover {
    background: rgba(16, 185, 129, 0.15) !important;
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

.dark .pagination-disabled {
    background: #374151 !important;
    color: #6b7280 !important;
    border-color: #4b5563 !important;
}
</style>
