<script setup>
import { ref, watch } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Button from "primevue/button";
import OverlayPanel from "primevue/overlaypanel";
import ConfirmDialog from "primevue/confirmdialog";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Card from "primevue/card";
import Avatar from "primevue/avatar";
import Tooltip from "primevue/tooltip";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    concoursList: Array,
    candidatures: Object,
    selectedConcourId: [Number, String],
    filters: Object,
    user_role: String,
    is_superadmin: Boolean,
    is_gerant: Boolean,
    is_admin: Boolean,
});

const confirm = useConfirm();
const toast = useToast();
const op = ref();
const selectedFiles = ref([]);
const selectedConcour = ref(
    props.selectedConcourId ? Number(props.selectedConcourId) : null,
);
const expandedRows = ref([]);
const searchQuery = ref(props.filters?.search || "");

// Formatage de date
const formatDate = (dateString) => {
    if (!dateString) return "Non définie";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

// Debounce pour la recherche
let searchTimeout;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applySearch(), 300);
});

const applySearch = () => {
    if (!selectedConcour.value) return;

    router.get(
        route("concours-consulter.index"),
        {
            concour_id: selectedConcour.value,
            search: searchQuery.value || null,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const resetSearch = () => {
    searchQuery.value = "";
};

// Changement de concours
watch(selectedConcour, (newVal) => {
    if (newVal) {
        resetSearch();
        router.get(
            route("concours-consulter.index"),
            { concour_id: Number(newVal) },
            {
                preserveState: true,
                replace: true,
            },
        );
    }
});

// Mise à jour du motif
let motifTimeout;
const updateMotif = (id, newMotif) => {
    clearTimeout(motifTimeout);
    motifTimeout = setTimeout(() => {
        router.patch(
            route("concours-consulter.update", Number(id)),
            { motif: newMotif },
            {
                preserveScroll: true,
                only: ["candidatures"],
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Motif mis à jour",
                        life: 2000,
                    });
                },
            },
        );
    }, 500);
};

// Confirmation Admission/Rejet
const confirmUpdate = (id, newStatus) => {
    confirm.require({
        message: `Voulez-vous vraiment marquer cette candidature comme "${newStatus}" ?`,
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Confirmer",
        rejectLabel: "Annuler",
        acceptClass:
            newStatus === "Admis" ? "p-button-success" : "p-button-danger",
        accept: () => {
            router.patch(
                route("concours-consulter.update", Number(id)),
                { resultat: newStatus },
                {
                    preserveScroll: true,
                    only: ["candidatures"],
                    onSuccess: () => {
                        toast.add({
                            severity: "success",
                            summary: "Statut mis à jour",
                            detail: `Candidature marquée comme "${newStatus}"`,
                            life: 3000,
                        });
                    },
                },
            );
        },
    });
};

const toggleFiles = (event, files) => {
    selectedFiles.value = files || [];
    op.value.toggle(event);
};

const getSeverity = (resultat) => {
    const severities = {
        Admis: "success",
        Rejété: "danger",
        Traitement: "info",
    };
    return severities[resultat] || "secondary";
};

const getStatusBadgeClass = (resultat) => {
    const classes = {
        Admis: "bg-green-100 text-green-700",
        Rejété: "bg-red-100 text-red-700",
        Traitement: "bg-blue-100 text-blue-700",
    };
    return classes[resultat] || "bg-gray-100 text-gray-700";
};

const rowClass = (data) => {
    if (data?.resultat === "Admis") return "row-admitted";
    if (data?.resultat === "Rejété") return "row-rejected";
    return "";
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des Candidatures" />
        <ConfirmDialog />

        <div class="page-container">
            <!-- En-tête -->
            <div class="header-section">
                <Card class="header-card">
                    <template #content>
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="pi pi-users"></i>
                            </div>
                            <div class="header-text">
                                <h1>Gestion des candidatures</h1>
                                <p>
                                    <i class="pi pi-check-circle"></i>
                                    Traitez et gérez les candidatures par
                                    concours
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Sélection du concours -->
            <div class="selection-section">
                <Card class="selection-card">
                    <template #content>
                        <div class="selection-content">
                            <div class="selection-field">
                                <label>
                                    <i class="pi pi-filter"></i>
                                    Sélectionner un concours
                                </label>
                                <Select
                                    v-model="selectedConcour"
                                    :options="concoursList"
                                    optionLabel="intitule"
                                    optionValue="id"
                                    placeholder="Choisissez un concours"
                                    class="w-full"
                                    filter
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Recherche globale -->
            <div v-if="selectedConcour && candidatures" class="search-section">
                <Card class="search-card">
                    <template #content>
                        <div class="search-content">
                            <div class="search-field">
                                <label>
                                    <i class="pi pi-search"></i>
                                    Rechercher un candidat
                                </label>
                                <div class="search-input-wrapper">
                                    <IconField
                                        iconPosition="left"
                                        class="flex-1"
                                    >
                                        <InputIcon class="pi pi-search" />
                                        <InputText
                                            v-model="searchQuery"
                                            placeholder="Nom, prénom, n° dossier, email..."
                                            class="w-full"
                                        />
                                    </IconField>
                                    <Button
                                        v-if="searchQuery"
                                        icon="pi pi-times"
                                        class="p-button-outlined p-button-secondary clear-btn"
                                        @click="resetSearch"
                                        v-tooltip.top="'Effacer la recherche'"
                                    />
                                </div>
                                <small
                                    >La recherche s'effectue sur le nom, prénom,
                                    n° de dossier, email et motif</small
                                >
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Tableau des candidatures -->
            <div v-if="selectedConcour && candidatures" class="table-section">
                <Card class="table-card">
                    <template #title>
                        <div class="table-title">
                            <i class="pi pi-list"></i>
                            <span>Liste des candidatures</span>
                            <span class="total-count"
                                >{{ candidatures.total }} candidat(s)</span
                            >
                        </div>
                    </template>
                    <template #content>
                        <DataTable
                            :value="candidatures.data"
                            :rowClass="rowClass"
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            class="custom-datatable"
                            stripedRows
                            showGridlines
                            responsiveLayout="stack"
                            breakpoint="768px"
                            :loading="!candidatures.data"
                        >
                            <Column expander style="width: 3rem" />

                            <Column
                                field="num_dossier"
                                header="N° Dossier"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="dossier-cell">
                                        <i class="pi pi-qrcode"></i>
                                        <span class="font-medium">{{
                                            slotProps.data.num_dossier
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                field="nom_complet"
                                header="Candidat"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="candidat-cell">
                                        <Avatar
                                            :label="
                                                slotProps.data.nom_complet?.charAt(
                                                    0,
                                                ) || '?'
                                            "
                                            size="small"
                                            shape="circle"
                                            class="candidat-avatar"
                                        />
                                        <span>{{
                                            slotProps.data.nom_complet
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                field="created_at"
                                header="Date dépôt"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="date-cell">
                                        <i class="pi pi-calendar"></i>
                                        <span>{{
                                            formatDate(
                                                slotProps.data.created_at,
                                            )
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <!-- Colonne Spécialité -->
                            <Column
                                v-if="candidatures.data[0]?.has_specialites"
                                field="specialite"
                                header="Spécialité"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="specialite-cell">
                                        <i class="pi pi-tags"></i>
                                        <span>{{
                                            slotProps.data.specialite ||
                                            "Non spécifiée"
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Statut">
                                <template #body="slotProps">
                                    <Tag
                                        :severity="
                                            getSeverity(slotProps.data.resultat)
                                        "
                                        :value="
                                            slotProps.data.resultat ||
                                            'En attente'
                                        "
                                        rounded
                                        :class="
                                            getStatusBadgeClass(
                                                slotProps.data.resultat,
                                            )
                                        "
                                        class="status-tag"
                                    />
                                </template>
                            </Column>

                            <Column header="Dossier" style="width: 6rem">
                                <template #body="slotProps">
                                    <Button
                                        icon="pi pi-paperclip"
                                        class="p-button-rounded p-button-text p-button-sm"
                                        :badge="
                                            slotProps.data.fichiers?.length ||
                                            null
                                        "
                                        badgeClass="p-badge-info"
                                        @click="
                                            toggleFiles(
                                                $event,
                                                slotProps.data.fichiers,
                                            )
                                        "
                                        v-tooltip.top="
                                            'Voir les pièces jointes'
                                        "
                                    />
                                </template>
                            </Column>

                            <Column header="Actions" style="width: 14rem">
                                <template #body="slotProps">
                                    <div class="actions-cell">
                                        <Button
                                            icon="pi pi-check"
                                            class="p-button-rounded p-button-text p-button-sm"
                                            @click="
                                                confirmUpdate(
                                                    slotProps.data.id,
                                                    'Admis',
                                                )
                                            "
                                            v-tooltip.top="'Admettre'"
                                        />
                                        <Button
                                            icon="pi pi-ban"
                                            class="p-button-rounded p-button-text p-button-sm text-red-600"
                                            @click="
                                                confirmUpdate(
                                                    slotProps.data.id,
                                                    'Rejété',
                                                )
                                            "
                                            v-tooltip.top="'Rejeter'"
                                        />
                                        <Link
                                            v-if="slotProps.data?.id"
                                            :href="
                                                route('messagerie.index', {
                                                    candidat_id:
                                                        slotProps.data
                                                            .profil_user_id,
                                                    concour_id: selectedConcour,
                                                })
                                            "
                                        >
                                            <Button
                                                icon="pi pi-envelope"
                                                class="p-button-rounded p-button-text p-button-sm text-blue-600"
                                                v-tooltip.top="
                                                    'Envoyer un message'
                                                "
                                            />
                                        </Link>
                                        <Link
                                            v-if="slotProps.data.profil_id"
                                            :href="
                                                route('candidat-profil.show', {
                                                    profil: slotProps.data
                                                        .profil_id,
                                                })
                                            "
                                        >
                                            <Button
                                                icon="pi pi-user"
                                                class="p-button-rounded p-button-text p-button-sm text-gray-700"
                                                v-tooltip.top="'Voir le profil'"
                                            />
                                        </Link>
                                    </div>
                                </template>
                            </Column>

                            <template #expansion="slotProps">
                                <div class="expansion-content">
                                    <h5>Informations complémentaires</h5>
                                    <div class="expansion-grid">
                                        <div class="expansion-field">
                                            <label>Motif / Remarques</label>
                                            <InputText
                                                v-model="slotProps.data.motif"
                                                @blur="
                                                    updateMotif(
                                                        slotProps.data.id,
                                                        slotProps.data.motif,
                                                    )
                                                "
                                                placeholder="Ajouter un motif..."
                                                class="w-full"
                                            />
                                        </div>
                                        <div class="expansion-field">
                                            <label>Dernière modification</label>
                                            <InputText
                                                :value="
                                                    formatDate(
                                                        slotProps.data
                                                            .updated_at,
                                                    ) || 'Jamais'
                                                "
                                                disabled
                                                class="w-full"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>

                        <!-- Pagination -->
                        <div class="pagination-wrapper">
                            <Link
                                v-for="link in candidatures.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="pagination-link"
                                :class="{
                                    active: link.active,
                                    disabled: !link.url,
                                }"
                                v-html="link.label"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- État quand aucun concours sélectionné -->
            <div v-else-if="!selectedConcour" class="empty-section">
                <Card class="empty-card">
                    <template #content>
                        <div class="empty-content">
                            <div class="empty-icon">
                                <i class="pi pi-search-minus"></i>
                            </div>
                            <h3>Aucun concours sélectionné</h3>
                            <p>
                                Veuillez choisir un concours pour voir les
                                candidatures
                            </p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Overlay Panel pour les fichiers -->
            <OverlayPanel ref="op">
                <div class="files-panel">
                    <h5>Pièces jointes</h5>
                    <div v-if="selectedFiles.length > 0" class="files-list">
                        <div
                            v-for="file in selectedFiles"
                            :key="file.url"
                            class="file-item"
                        >
                            <i class="pi pi-file-pdf"></i>
                            <a :href="'/storage/' + file.url" target="_blank">
                                {{ file.nom }}
                            </a>
                            <i class="pi pi-external-link"></i>
                        </div>
                    </div>
                    <div v-else class="empty-files">
                        <i class="pi pi-file-excel"></i>
                        <p>Aucune pièce jointe</p>
                    </div>
                </div>
            </OverlayPanel>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Container principal */
.page-container {
    padding: 1rem;
    max-width: 1600px;
    margin: 0 auto;
}

@media (min-width: 768px) {
    .page-container {
        padding: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .page-container {
        padding: 2rem;
    }
}

/* Cartes */
.header-card,
.selection-card,
.search-card,
.table-card,
.empty-card {
    border-radius: 1rem;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

@media (max-width: 768px) {
    .header-card,
    .selection-card,
    .search-card,
    .table-card,
    .empty-card {
        margin-bottom: 1rem;
        border-radius: 0.75rem;
    }
}

/* En-tête */
.header-card {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.header-card :deep(.p-card-content) {
    padding: 1.5rem;
}

@media (max-width: 768px) {
    .header-card :deep(.p-card-content) {
        padding: 1rem;
    }
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.header-icon {
    width: 4rem;
    height: 4rem;
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-icon i {
    font-size: 2rem;
}

.header-text h1 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 0 0.25rem 0;
}

.header-text p {
    font-size: 0.875rem;
    opacity: 0.9;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 640px) {
    .header-icon {
        width: 3rem;
        height: 3rem;
    }
    .header-icon i {
        font-size: 1.5rem;
    }
    .header-text h1 {
        font-size: 1.25rem;
    }
}

/* Sélection */
.selection-content,
.search-content {
    display: flex;
    flex-direction: column;
}

.selection-field label,
.search-field label {
    display: block;
    font-weight: 500;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: #4b5563;
}

.selection-field label i,
.search-field label i {
    margin-right: 0.5rem;
    color: #10b981;
}

.search-input-wrapper {
    display: flex;
    gap: 0.5rem;
}

.clear-btn {
    flex-shrink: 0;
}

.search-field small {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #9ca3af;
}

/* Tableau */
.table-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.table-title i {
    color: #10b981;
    font-size: 1.25rem;
}

.table-title span {
    font-size: 1.125rem;
    font-weight: 600;
}

.total-count {
    margin-left: auto;
    font-size: 0.875rem;
    font-weight: normal;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
}

/* Cellules du tableau */
.dossier-cell,
.candidat-cell,
.date-cell,
.specialite-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dossier-cell i,
.date-cell i,
.specialite-cell i {
    color: #10b981;
    font-size: 0.875rem;
}

.candidat-avatar {
    background-color: #10b981 !important;
    color: white !important;
}

/* Statut */
.status-tag {
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
}

/* Boutons d'action */
.actions-cell {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.actions-cell .p-button-text {
    width: 2rem !important;
    height: 2rem !important;
    border-radius: 0.5rem !important;
}

.actions-cell .p-button-text:hover {
    background-color: #f3f4f6 !important;
}

/* Expansion */
.expansion-content {
    padding: 1rem;
    background-color: #f9fafb;
}

.expansion-content h5 {
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.expansion-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 768px) {
    .expansion-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.expansion-field label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

/* Pagination */
.pagination-wrapper {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.pagination-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.5rem;
    height: 2.5rem;
    padding: 0 0.5rem;
    border-radius: 0.5rem;
    background-color: white;
    border: 1px solid #e5e7eb;
    color: #374151;
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.2s;
}

.pagination-link:hover {
    background-color: #f3f4f6;
    border-color: #10b981;
}

.pagination-link.active {
    background-color: #10b981;
    border-color: #10b981;
    color: white;
}

.pagination-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* État vide */
.empty-content {
    text-align: center;
    padding: 3rem;
}

.empty-icon {
    width: 5rem;
    height: 5rem;
    background-color: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.empty-icon i {
    font-size: 2rem;
    color: #9ca3af;
}

.empty-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-content p {
    color: #6b7280;
}

/* Panel fichiers */
.files-panel {
    padding: 0.5rem;
    min-width: 250px;
    max-width: 350px;
}

.files-panel h5 {
    font-weight: 600;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.files-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: background-color 0.2s;
}

.file-item:hover {
    background-color: #f3f4f6;
}

.file-item i:first-child {
    color: #ef4444;
    font-size: 1rem;
}

.file-item a {
    flex: 1;
    font-size: 0.875rem;
    color: #374151;
    text-decoration: none;
    word-break: break-all;
}

.file-item a:hover {
    color: #10b981;
}

.file-item i:last-child {
    color: #9ca3af;
    font-size: 0.75rem;
}

.empty-files {
    text-align: center;
    padding: 1.5rem;
}

.empty-files i {
    font-size: 2rem;
    color: #d1d5db;
    margin-bottom: 0.5rem;
}

.empty-files p {
    font-size: 0.875rem;
    color: #9ca3af;
}

/* Lignes spéciales */
:deep(.row-admitted) {
    background-color: rgba(16, 185, 129, 0.05) !important;
}

:deep(.row-rejected) {
    background-color: rgba(239, 68, 68, 0.05) !important;
}

/* Responsive Datatable */
@media (max-width: 768px) {
    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 0.75rem;
    }

    :deep(.p-datatable .p-datatable-thead > tr > th) {
        padding: 0.75rem 0.5rem;
        font-size: 0.75rem;
    }

    .actions-cell {
        justify-content: flex-start;
    }

    .total-count {
        margin-left: 0;
        margin-top: 0.5rem;
    }

    .table-title {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* Focus */
:deep(.p-inputtext:focus),
:deep(.p-select:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}
</style>
