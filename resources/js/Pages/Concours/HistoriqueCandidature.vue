<script setup>
import { ref, watch, computed } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Button from "primevue/button";
import OverlayPanel from "primevue/overlaypanel";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Card from "primevue/card";
import Avatar from "primevue/avatar";
import Message from "primevue/message";
import Tooltip from "primevue/tooltip";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    concoursList: Array,
    candidatures: Object,
    selectedConcourId: [Number, String],
    hasSpecialites: {
        type: Boolean,
        default: false,
    },
    filters: Object,
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
const op = ref();
const selectedFiles = ref([]);
const selectedConcour = ref(
    props.selectedConcourId ? Number(props.selectedConcourId) : null,
);
const expandedRows = ref([]);

const searchQuery = ref(props.filters?.search || "");

let searchTimeout;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applySearch(), 300);
});

const applySearch = () => {
    if (!selectedConcour.value) return;

    router.get(
        route("concours-historique.index"),
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

watch(selectedConcour, (newVal) => {
    if (newVal) {
        resetSearch();
        router.get(
            route("concours-historique.index"),
            { concour_id: Number(newVal) },
            {
                preserveState: true,
                replace: true,
            },
        );
    }
});

const downloadFullList = () => {
    if (!selectedConcour.value) return;

    window.open(
        route("concours-historique.export", {
            concour_id: selectedConcour.value,
        }),
        "_blank",
    );

    toast.add({
        severity: "info",
        summary: "Téléchargement en cours",
        detail: "La liste des candidatures est en cours de génération",
        life: 3000,
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

const nombreConcoursDisponibles = computed(() => {
    return props.concoursList?.length || 0;
});

// Ajouter cette fonction après downloadFullList
const downloadNinaList = () => {
    if (!selectedConcour.value) return;

    window.open(
        route("concours-historique.export-nina", {
            concour_id: selectedConcour.value,
        }),
        "_blank",
    );

    toast.add({
        severity: "info",
        summary: "Téléchargement en cours",
        detail: "La liste des NINA est en cours de génération",
        life: 3000,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Historique des candidatures" />

        <div class="historique-container">
            <!-- En-tête - FULL RESPONSIVE -->
            <div class="header-card-wrapper">
                <Card class="header-card">
                    <template #content>
                        <div class="header-content">
                            <!-- ⭐ Titre à gauche -->
                            <div class="header-left">
                                <div class="icon-wrapper">
                                    <i class="pi pi-history text-white"></i>
                                </div>
                                <div class="header-text">
                                    <h1>Historique des candidatures</h1>
                                    <p>
                                        <i class="pi pi-chart-line"></i>
                                        <span class="desktop-text"
                                            >Consultez l'historique complet des
                                            candidatures par concours</span
                                        >
                                        <span class="mobile-text"
                                            >Historique par concours</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <!-- ⭐ Boutons à droite -->
                            <div class="header-buttons">
                                <Button
                                    v-if="
                                        selectedConcour &&
                                        candidatures?.data?.length > 0
                                    "
                                    label="NINA Excel"
                                    icon="pi pi-table"
                                    severity="info"
                                    size="small"
                                    @click="downloadNinaList"
                                />
                                <Button
                                    v-if="
                                        selectedConcour &&
                                        candidatures?.data?.length > 0
                                    "
                                    label="Télécharger PDF"
                                    icon="pi pi-download"
                                    severity="success"
                                    size="small"
                                    @click="downloadFullList"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Messages d'information selon le rôle -->
            <div v-if="is_superadmin" class="message-wrapper">
                <Message severity="success" :closable="false">
                    <div class="message-content">
                        <i class="pi pi-crown"></i>
                        <span
                            ><strong>Super Administrateur</strong>
                            <span class="desktop-text"
                                >- Vous avez accès à l'historique de tous les
                                concours.</span
                            ></span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_gerant && userService" class="message-wrapper">
                <Message severity="success" :closable="false">
                    <div class="message-content">
                        <i class="pi pi-building"></i>
                        <span
                            ><strong>Gérant - {{ userService.nom }}</strong>
                            <span class="desktop-text"
                                >- Vous avez accès à l'historique des concours
                                de votre service.</span
                            ></span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_admin && userService" class="message-wrapper">
                <Message severity="info" :closable="false">
                    <div class="message-content">
                        <i class="pi pi-user"></i>
                        <span
                            ><strong>Admin - {{ userService.nom }}</strong>
                            <span class="desktop-text"
                                >- Vous avez accès à l'historique des concours
                                qui vous sont assignés.</span
                            ></span
                        >
                    </div>
                </Message>
            </div>

            <!-- Message si aucun concours disponible -->
            <div v-if="nombreConcoursDisponibles === 0" class="message-wrapper">
                <Message severity="warn" :closable="false">
                    <div class="message-content">
                        <i class="pi pi-exclamation-triangle"></i>
                        <span
                            >Aucun concours disponible pour votre profil.</span
                        >
                    </div>
                </Message>
            </div>

            <!-- Sélection du concours -->
            <div
                v-if="nombreConcoursDisponibles > 0"
                class="select-card-wrapper"
            >
                <Card class="select-card">
                    <template #content>
                        <div class="field">
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
                            >
                                <template #option="slotProps">
                                    <div class="select-option">
                                        <i
                                            class="pi pi-calendar"
                                            :class="
                                                slotProps.option.statut ===
                                                'Actif'
                                                    ? 'text-green-500'
                                                    : 'text-gray-400'
                                            "
                                        ></i>
                                        <span>{{
                                            slotProps.option.intitule
                                        }}</span>
                                        <Tag
                                            :value="slotProps.option.statut"
                                            :severity="
                                                slotProps.option.statut ===
                                                'Actif'
                                                    ? 'success'
                                                    : 'secondary'
                                            "
                                            size="small"
                                        />
                                    </div>
                                </template>
                            </Select>
                            <small
                                >Vous pouvez sélectionner un concours actif ou
                                inactif</small
                            >
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Recherche globale -->
            <div
                v-if="selectedConcour && candidatures"
                class="search-card-wrapper"
            >
                <Card class="search-card">
                    <template #content>
                        <div class="field">
                            <label>
                                <i class="pi pi-search"></i>
                                Rechercher un candidat
                            </label>
                            <div class="search-input-wrapper">
                                <IconField iconPosition="left" class="flex-1">
                                    <InputIcon class="pi pi-search" />
                                    <InputText
                                        v-model="searchQuery"
                                        placeholder="Nom, prénom, n° dossier, NINA..."
                                        class="w-full"
                                    />
                                </IconField>
                                <Button
                                    v-if="searchQuery"
                                    icon="pi pi-times"
                                    severity="secondary"
                                    variant="outlined"
                                    size="small"
                                    @click="resetSearch"
                                    v-tooltip.top="'Effacer la recherche'"
                                />
                            </div>
                            <small>Recherche insensible à la casse</small>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Tableau des candidatures -->
            <div
                v-if="selectedConcour && candidatures"
                class="table-card-wrapper"
            >
                <Card class="table-card">
                    <template #title>
                        <div class="table-title">
                            <div class="title-left">
                                <i class="pi pi-list"></i>
                                <span>Historique des candidatures</span>
                            </div>
                            <span class="total-badge"
                                >Total: {{ candidatures.total }}</span
                            >
                        </div>
                    </template>
                    <template #content>
                        <DataTable
                            :value="candidatures.data"
                            :rowClass="rowClass"
                            v-model:expandedRows="expandedRows"
                            dataKey="id"
                            class="candidatures-table"
                            stripedRows
                            showGridlines
                            responsiveLayout="stack"
                            breakpoint="960px"
                            :loading="!candidatures.data"
                        >
                            <Column expander style="width: 3rem" />

                            <Column
                                field="num_dossier"
                                header="N° Dossier"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="cell-content">
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
                                    <div class="cell-content">
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
                                        <span class="truncate">{{
                                            slotProps.data.nom_complet
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                v-if="hasSpecialites"
                                field="specialite"
                                header="Spécialité"
                                sortable
                            >
                                <template #body="slotProps">
                                    <Tag
                                        :value="
                                            slotProps.data.specialite ||
                                            'Non spécifiée'
                                        "
                                        severity="info"
                                        rounded
                                        class="specialite-tag"
                                    />
                                </template>
                            </Column>

                            <Column
                                field="created_at"
                                header="Date dépôt"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="cell-content">
                                        <i class="pi pi-calendar"></i>
                                        <span>{{
                                            slotProps.data.created_at
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                field="updated_at"
                                header="Modifié le"
                                sortable
                            >
                                <template #body="slotProps">
                                    <div class="cell-content">
                                        <i class="pi pi-clock"></i>
                                        <span>{{
                                            slotProps.data.updated_at || "-"
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Résultat">
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
                                        class="resultat-tag"
                                    />
                                </template>
                            </Column>

                            <Column header="Dossier" style="min-width: 5rem">
                                <template #body="slotProps">
                                    <Button
                                        icon="pi pi-paperclip"
                                        rounded
                                        text
                                        size="small"
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

                            <Column header="Actions" style="min-width: 5rem">
                                <template #body="slotProps">
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
                                            rounded
                                            text
                                            size="small"
                                            class="action-btn"
                                            v-tooltip.top="'Voir le profil'"
                                        />
                                    </Link>
                                </template>
                            </Column>

                            <template #expansion="slotProps">
                                <div class="expansion-content">
                                    <h5>Informations complémentaires</h5>
                                    <div class="field">
                                        <label>Motif / Remarques</label>
                                        <div class="motif-box">
                                            <p>
                                                {{
                                                    slotProps.data.motif ||
                                                    "Aucun motif renseigné"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </DataTable>

                        <!-- Pagination -->
                        <div class="pagination-wrapper">
                            <template
                                v-for="link in candidatures.links"
                                :key="link.label"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="pagination-link"
                                    :class="{ active: link.active }"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="pagination-link disabled"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- État quand aucun concours sélectionné -->
            <div v-else-if="!selectedConcour" class="empty-state-wrapper">
                <Card class="empty-state-card">
                    <template #content>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="pi pi-search-minus"></i>
                            </div>
                            <h3>Aucun concours sélectionné</h3>
                            <p>
                                Veuillez choisir un concours pour voir
                                l'historique des candidatures
                            </p>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- État quand aucune candidature -->
            <div
                v-else-if="selectedConcour && candidatures?.data?.length === 0"
                class="empty-state-wrapper"
            >
                <Card class="empty-state-card">
                    <template #content>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="pi pi-folder-open"></i>
                            </div>
                            <h3>Aucune candidature</h3>
                            <p>
                                Ce concours ne contient aucune candidature pour
                                le moment
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
                    <div v-else class="no-files">
                        <i class="pi pi-file-excel"></i>
                        <p>Aucune pièce jointe</p>
                    </div>
                </div>
            </OverlayPanel>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ============================================ */
/* CONTAINER PRINCIPAL */
/* ============================================ */
.historique-container {
    padding: 0.5rem;
}

@media (min-width: 640px) {
    .historique-container {
        padding: 1rem;
    }
}

@media (min-width: 768px) {
    .historique-container {
        padding: 1rem 1.5rem;
    }
}

@media (min-width: 1024px) {
    .historique-container {
        padding: 1rem 2rem;
    }
}

/* ============================================ */
/* HEADER CARD - FULL RESPONSIVE */
/* ============================================ */
.header-card-wrapper {
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    .header-card-wrapper {
        margin-bottom: 1.5rem;
    }
}

.header-card {
    border: none !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    background: linear-gradient(to right, #ecfdf5, white);
}

.dark .header-card {
    background: linear-gradient(to right, rgba(16, 185, 129, 0.2), #1f2937);
}

.header-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
}

@media (min-width: 768px) {
    .header-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .header-left {
        gap: 1rem;
    }
}

.icon-wrapper {
    width: 3rem;
    height: 3rem;
    background: #10b981;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .icon-wrapper {
        width: 4rem;
        height: 4rem;
    }
}

.icon-wrapper i {
    font-size: 1.5rem;
}

@media (min-width: 640px) {
    .icon-wrapper i {
        font-size: 2rem;
    }
}

.header-text h1 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #111827;
}

@media (min-width: 640px) {
    .header-text h1 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
}

@media (min-width: 1024px) {
    .header-text h1 {
        font-size: 1.875rem;
    }
}

.header-text p {
    margin: 0;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .header-text p {
        font-size: 0.875rem;
    }
}

.header-text p i {
    color: #10b981;
}

.desktop-text {
    display: none;
}

@media (min-width: 768px) {
    .desktop-text {
        display: inline;
    }
}

.mobile-text {
    display: inline;
}

@media (min-width: 768px) {
    .mobile-text {
        display: none;
    }
}

.download-btn {
    width: 100%;
}

@media (min-width: 768px) {
    .download-btn {
        width: auto;
    }
}

/* ============================================ */
/* MESSAGES */
/* ============================================ */
.message-wrapper {
    margin-bottom: 0.75rem;
}

@media (min-width: 768px) {
    .message-wrapper {
        margin-bottom: 1rem;
    }
}

.message-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .message-content {
        font-size: 0.875rem;
    }
}

/* ============================================ */
/* SELECT CARD */
/* ============================================ */
.select-card-wrapper {
    margin-bottom: 0.75rem;
}

@media (min-width: 768px) {
    .select-card-wrapper {
        margin-bottom: 1rem;
    }
}

.select-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.select-card .field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.select-card label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (min-width: 640px) {
    .select-card label {
        font-size: 0.875rem;
    }
}

.select-card label i {
    color: #10b981;
}

.select-card small {
    color: #9ca3af;
    font-size: 0.625rem;
}

@media (min-width: 640px) {
    .select-card small {
        font-size: 0.75rem;
    }
}

.select-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .select-option {
        font-size: 0.875rem;
    }
}

/* ============================================ */
/* SEARCH CARD */
/* ============================================ */
.search-card-wrapper {
    margin-bottom: 0.75rem;
}

@media (min-width: 768px) {
    .search-card-wrapper {
        margin-bottom: 1rem;
    }
}

.search-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-card .field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.search-card label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (min-width: 640px) {
    .search-card label {
        font-size: 0.875rem;
    }
}

.search-card label i {
    color: #10b981;
}

.search-card small {
    color: #9ca3af;
    font-size: 0.625rem;
}

@media (min-width: 640px) {
    .search-card small {
        font-size: 0.75rem;
    }
}

.search-input-wrapper {
    display: flex;
    gap: 0.5rem;
}

/* ============================================ */
/* TABLE CARD */
/* ============================================ */
.table-card-wrapper {
    margin-bottom: 1rem;
}

.table-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.table-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0 0.25rem;
}

@media (min-width: 640px) {
    .table-title {
        padding: 0;
    }
}

.title-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.title-left i {
    color: #10b981;
}

.title-left span {
    font-size: 0.875rem;
    font-weight: 600;
}

@media (min-width: 640px) {
    .title-left span {
        font-size: 1.125rem;
    }
}

.total-badge {
    font-size: 0.7rem;
    color: #6b7280;
}

@media (min-width: 640px) {
    .total-badge {
        font-size: 0.875rem;
    }
}

/* ============================================ */
/* TABLEAU */
/* ============================================ */
.candidatures-table {
    font-size: 0.75rem;
}

@media (min-width: 640px) {
    .candidatures-table {
        font-size: 0.875rem;
    }
}

.cell-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cell-content i {
    color: #9ca3af;
    flex-shrink: 0;
}

.cell-content .pi-qrcode {
    color: #10b981;
}

.candidat-avatar {
    background: #10b981 !important;
    color: white !important;
    flex-shrink: 0;
}

.specialite-tag {
    font-size: 0.65rem !important;
    padding: 0.15rem 0.5rem !important;
}

@media (min-width: 640px) {
    .specialite-tag {
        font-size: 0.75rem !important;
        padding: 0.2rem 0.75rem !important;
    }
}

.resultat-tag {
    font-size: 0.65rem !important;
    padding: 0.15rem 0.5rem !important;
}

@media (min-width: 640px) {
    .resultat-tag {
        font-size: 0.75rem !important;
        padding: 0.2rem 0.75rem !important;
    }
}

.action-btn {
    color: #10b981 !important;
}

.action-btn:hover {
    color: #059669 !important;
}

.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 100px;
}

@media (min-width: 480px) {
    .truncate {
        max-width: 150px;
    }
}

@media (min-width: 768px) {
    .truncate {
        max-width: 200px;
    }
}

/* ============================================ */
/* EXPANSION */
/* ============================================ */
.expansion-content {
    padding: 0.75rem;
    background: #f9fafb;
}

.dark .expansion-content {
    background: #1f2937;
}

@media (min-width: 768px) {
    .expansion-content {
        padding: 1rem;
    }
}

.expansion-content h5 {
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0 0 0.75rem 0;
}

@media (min-width: 768px) {
    .expansion-content h5 {
        font-size: 0.875rem;
        margin-bottom: 1rem;
    }
}

.expansion-content .field label {
    font-size: 0.65rem;
    font-weight: 600;
    color: #6b7280;
}

@media (min-width: 768px) {
    .expansion-content .field label {
        font-size: 0.75rem;
    }
}

.motif-box {
    padding: 0.5rem;
    background: white;
    border-radius: 0.375rem;
    margin-top: 0.25rem;
}

.dark .motif-box {
    background: #111827;
}

@media (min-width: 768px) {
    .motif-box {
        padding: 0.75rem;
    }
}

.motif-box p {
    font-size: 0.7rem;
    margin: 0;
}

@media (min-width: 768px) {
    .motif-box p {
        font-size: 0.875rem;
    }
}

/* ============================================ */
/* PAGINATION */
/* ============================================ */
.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 1rem;
    gap: 0.25rem;
    flex-wrap: wrap;
}

@media (min-width: 640px) {
    .pagination-wrapper {
        margin-top: 1.5rem;
        gap: 0.5rem;
    }
}

.pagination-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.75rem;
    height: 1.75rem;
    padding: 0 0.5rem;
    font-size: 0.7rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

@media (min-width: 640px) {
    .pagination-link {
        min-width: 2rem;
        height: 2rem;
        font-size: 0.875rem;
    }
}

.pagination-link:hover {
    background: #f3f4f6;
}

.pagination-link.active {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.pagination-link.disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* ============================================ */
/* EMPTY STATE */
/* ============================================ */
.empty-state-wrapper {
    margin-top: 1rem;
}

.empty-state-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state {
    text-align: center;
    padding: 1.5rem 1rem;
}

@media (min-width: 640px) {
    .empty-state {
        padding: 2rem;
    }
}

@media (min-width: 768px) {
    .empty-state {
        padding: 3rem;
    }
}

.empty-icon {
    width: 3rem;
    height: 3rem;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
}

.dark .empty-icon {
    background: #374151;
}

@media (min-width: 640px) {
    .empty-icon {
        width: 4rem;
        height: 4rem;
        margin-bottom: 1rem;
    }
}

@media (min-width: 768px) {
    .empty-icon {
        width: 5rem;
        height: 5rem;
    }
}

.empty-icon i {
    font-size: 1.5rem;
    color: #9ca3af;
}

@media (min-width: 640px) {
    .empty-icon i {
        font-size: 2rem;
    }
}

@media (min-width: 768px) {
    .empty-icon i {
        font-size: 2.5rem;
    }
}

.empty-state h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #111827;
}

@media (min-width: 640px) {
    .empty-state h3 {
        font-size: 1.125rem;
    }
}

@media (min-width: 768px) {
    .empty-state h3 {
        font-size: 1.25rem;
    }
}

.empty-state p {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

@media (min-width: 640px) {
    .empty-state p {
        font-size: 0.875rem;
    }
}

/* ============================================ */
/* FILES PANEL */
/* ============================================ */
.files-panel {
    padding: 0.5rem;
    min-width: 180px;
    max-width: 280px;
}

@media (min-width: 640px) {
    .files-panel {
        padding: 0.75rem;
        min-width: 250px;
        max-width: 350px;
    }
}

.files-panel h5 {
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}

@media (min-width: 640px) {
    .files-panel h5 {
        font-size: 0.875rem;
        margin-bottom: 0.75rem;
    }
}

.files-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: background 0.2s;
}

.file-item:hover {
    background: #f3f4f6;
}

.dark .file-item:hover {
    background: #374151;
}

.file-item i.pi-file-pdf {
    color: #ef4444;
    flex-shrink: 0;
    font-size: 0.875rem;
}

.file-item a {
    flex: 1;
    font-size: 0.7rem;
    color: #374151;
    text-decoration: none;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (min-width: 640px) {
    .file-item a {
        font-size: 0.875rem;
    }
}

.file-item a:hover {
    color: #10b981;
}

.file-item i.pi-external-link {
    color: #9ca3af;
    flex-shrink: 0;
    font-size: 0.7rem;
}

.no-files {
    text-align: center;
    padding: 1rem 0;
    color: #9ca3af;
}

.no-files i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.no-files p {
    font-size: 0.7rem;
    margin: 0;
}

/* ============================================ */
/* ROW STYLES */
/* ============================================ */
:deep(.row-admitted) {
    background-color: rgba(16, 185, 129, 0.05) !important;
}

:deep(.row-rejected) {
    background-color: rgba(239, 68, 68, 0.05) !important;
}

/* ============================================ */
/* DATATABLE STACK MODE OPTIMIZATION */
/* ============================================ */
@media (max-width: 960px) {
    :deep(.p-datatable.p-datatable-stack .p-datatable-tbody > tr > td) {
        padding: 0.5rem !important;
    }

    :deep(
            .p-datatable.p-datatable-stack
                .p-datatable-tbody
                > tr
                > td
                .p-column-title
        ) {
        font-weight: 600 !important;
        min-width: 100px !important;
        font-size: 0.7rem !important;
    }

    :deep(
            .p-datatable.p-datatable-stack
                .p-datatable-tbody
                > tr
                > td
                .p-column-title
                + *
        ) {
        font-size: 0.75rem !important;
    }
}

@media (max-width: 480px) {
    :deep(
            .p-datatable.p-datatable-stack
                .p-datatable-tbody
                > tr
                > td
                .p-column-title
        ) {
        min-width: 80px !important;
        font-size: 0.65rem !important;
    }

    :deep(
            .p-datatable.p-datatable-stack
                .p-datatable-tbody
                > tr
                > td
                .p-column-title
                + *
        ) {
        font-size: 0.7rem !important;
    }
}

/* ============================================ */
/* FOCUS STYLES */
/* ============================================ */
:deep(.p-inputtext:focus),
:deep(.p-select:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

/* ============================================ */
/* ANIMATION */
/* ============================================ */
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
.header-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    width: 100%;
}

@media (min-width: 768px) {
    .header-buttons {
        width: auto;
    }
}
</style>
