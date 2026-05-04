<script setup>
import { ref, watch, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Button from "primevue/button";
import Select from "primevue/select";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Message from "primevue/message";
import Avatar from "primevue/avatar";
import Dialog from "primevue/dialog";
import Tooltip from "primevue/tooltip";

const props = defineProps({
    evenements: Object,
    typesActions: Array,
    filters: Object,
    user_role: String,
    is_superadmin: Boolean,
    is_gerant: Boolean,
    userService: Object,
});

const selectedTypeAction = ref(props.filters?.type_action || null);
const searchQuery = ref(props.filters?.search || "");
const showDetails = ref(false);
const selectedEvenement = ref(null);

let searchTimeout;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
});

watch(selectedTypeAction, () => applyFilters());

const applyFilters = () => {
    router.get(
        route("tracabilite.index"),
        {
            type_action: selectedTypeAction.value || null,
            search: searchQuery.value || null,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    selectedTypeAction.value = null;
    searchQuery.value = "";
    router.get(route("tracabilite.index"));
};

const getActionSeverity = (typeAction) => {
    const map = {
        Création: "success",
        Modification: "info",
        Suppression: "danger",
        Admission: "success",
        Rejet: "danger",
        Publication: "success",
        Assignation: "info",
        Révocation: "warn",
    };
    return map[typeAction] || "secondary";
};

const openDetails = (evenement) => {
    selectedEvenement.value = evenement;
    showDetails.value = true;
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getInitials = (name) => {
    if (!name) return "?";
    return name.charAt(0).toUpperCase();
};

const hasDonnees = computed(() => {
    if (!selectedEvenement.value) return false;
    return (
        selectedEvenement.value.donnees_avant ||
        selectedEvenement.value.donnees_apres
    );
});
</script>

<template>
    <AppLayout>
        <Head title="Traçabilité des actions" />

        <div class="tracabilite-container">
            <!-- En-tête -->
            <div class="header-card-wrapper">
                <Card class="header-card">
                    <template #content>
                        <div class="header-content">
                            <div class="header-left">
                                <div class="icon-wrapper">
                                    <i class="pi pi-history text-white"></i>
                                </div>
                                <div class="header-text">
                                    <h1>Traçabilité des actions</h1>
                                    <p>
                                        <i class="pi pi-chart-line"></i>
                                        <span class="desktop-text"
                                            >Historique complet des actions des
                                            administrateurs et gérants</span
                                        >
                                        <span class="mobile-text"
                                            >Historique des actions</span
                                        >
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Messages selon le rôle -->
            <div v-if="is_superadmin" class="message-wrapper">
                <Message severity="success" :closable="false">
                    <div class="message-content">
                        <i class="pi pi-crown"></i>
                        <span
                            ><strong>Super Administrateur</strong> - Vous
                            visualisez toutes les actions de tous les
                            services.</span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_gerant && userService" class="message-wrapper">
                <Message severity="success" :closable="false">
                    <div class="message-content">
                        <i class="pi pi-building"></i>
                        <span
                            ><strong>Gérant - {{ userService.nom }}</strong> -
                            Vous visualisez les actions de votre service.</span
                        >
                    </div>
                </Message>
            </div>

            <!-- Filtres -->
            <div class="filters-card-wrapper">
                <Card class="filters-card">
                    <template #content>
                        <div class="filters-content">
                            <div class="filter-field">
                                <label
                                    ><i class="pi pi-filter"></i> Type
                                    d'action</label
                                >
                                <Select
                                    v-model="selectedTypeAction"
                                    :options="typesActions"
                                    placeholder="Tous les types"
                                    class="w-full"
                                    showClear
                                />
                            </div>
                            <div class="filter-field">
                                <label
                                    ><i class="pi pi-search"></i>
                                    Rechercher</label
                                >
                                <div class="search-input-wrapper">
                                    <IconField
                                        iconPosition="left"
                                        class="flex-1"
                                    >
                                        <InputIcon class="pi pi-search" />
                                        <InputText
                                            v-model="searchQuery"
                                            placeholder="Auteur, description..."
                                            class="w-full"
                                        />
                                    </IconField>
                                    <Button
                                        v-if="selectedTypeAction || searchQuery"
                                        icon="pi pi-times"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                        @click="resetFilters"
                                        v-tooltip.top="'Réinitialiser'"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Tableau -->
            <div class="table-card-wrapper">
                <Card class="table-card">
                    <template #title>
                        <div class="table-title">
                            <div class="title-left">
                                <i class="pi pi-list"></i>
                                <span>Journal des événements</span>
                            </div>
                            <span class="total-badge"
                                >Total : {{ evenements.total }}</span
                            >
                        </div>
                    </template>
                    <template #content>
                        <DataTable
                            :value="evenements.data"
                            class="evenements-table"
                            stripedRows
                            showGridlines
                            responsiveLayout="stack"
                            breakpoint="960px"
                        >
                            <Column header="Date" style="min-width: 120px">
                                <template #body="slotProps">
                                    <div class="cell-content">
                                        <i class="pi pi-clock"></i>
                                        <span>{{
                                            formatDate(
                                                slotProps.data.created_at,
                                            )
                                        }}</span>
                                    </div>
                                </template>
                            </Column>

                            <Column header="Auteur" style="min-width: 150px">
                                <template #body="slotProps">
                                    <div class="cell-content">
                                        <Avatar
                                            :label="
                                                getInitials(
                                                    slotProps.data.user_name,
                                                )
                                            "
                                            size="small"
                                            shape="circle"
                                            class="author-avatar"
                                        />
                                        <div class="author-info">
                                            <span class="author-name">{{
                                                slotProps.data.user_name
                                            }}</span>
                                            <span class="author-email">{{
                                                slotProps.data.user_email
                                            }}</span>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <Column
                                v-if="is_superadmin"
                                header="Service"
                                style="min-width: 120px"
                            >
                                <template #body="slotProps">
                                    <div
                                        class="cell-content"
                                        v-if="slotProps.data.service_nom"
                                    >
                                        <i class="pi pi-building"></i>
                                        <span>{{
                                            slotProps.data.service_nom
                                        }}</span>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </template>
                            </Column>

                            <Column header="Type" style="min-width: 100px">
                                <template #body="slotProps">
                                    <Tag
                                        :value="slotProps.data.type_action"
                                        :severity="
                                            getActionSeverity(
                                                slotProps.data.type_action,
                                            )
                                        "
                                        rounded
                                        size="small"
                                    />
                                </template>
                            </Column>

                            <Column
                                header="Description"
                                style="min-width: 250px"
                            >
                                <template #body="slotProps">
                                    <span class="description-text">{{
                                        slotProps.data.description
                                    }}</span>
                                </template>
                            </Column>

                            <Column
                                header="Détails"
                                style="min-width: 80px; text-align: center"
                            >
                                <template #body="slotProps">
                                    <Button
                                        icon="pi pi-eye"
                                        text
                                        rounded
                                        size="small"
                                        @click="openDetails(slotProps.data)"
                                        v-tooltip.top="'Voir les détails'"
                                    />
                                </template>
                            </Column>
                        </DataTable>

                        <!-- Pagination -->
                        <div class="pagination-wrapper" v-if="evenements.links">
                            <template
                                v-for="link in evenements.links"
                                :key="link.label"
                            >
                                <a
                                    v-if="link.url"
                                    :href="link.url"
                                    class="pagination-link"
                                    :class="{ active: link.active }"
                                    v-html="link.label"
                                ></a>
                                <span
                                    v-else
                                    class="pagination-link disabled"
                                    v-html="link.label"
                                ></span>
                            </template>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- État vide -->
            <div
                v-if="evenements.data?.length === 0 && !evenements.total"
                class="empty-state-wrapper"
            >
                <Card class="empty-state-card">
                    <template #content>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="pi pi-history"></i>
                            </div>
                            <h3>Aucun événement</h3>
                            <p>Aucune action n'a encore été enregistrée.</p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Dialog Détails -->
        <Dialog
            v-model:visible="showDetails"
            header="Détails de l'événement"
            modal
            :style="{ width: '90vw', maxWidth: '600px' }"
        >
            <div v-if="selectedEvenement" class="details-content">
                <div class="detail-row">
                    <span class="detail-label">Date</span>
                    <span class="detail-value">{{
                        formatDate(selectedEvenement.created_at)
                    }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Auteur</span>
                    <span class="detail-value"
                        >{{ selectedEvenement.user_name }} ({{
                            selectedEvenement.user_email
                        }})</span
                    >
                </div>
                <div class="detail-row" v-if="selectedEvenement.service_nom">
                    <span class="detail-label">Service</span>
                    <span class="detail-value">{{
                        selectedEvenement.service_nom
                    }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Type d'action</span>
                    <Tag
                        :value="selectedEvenement.type_action"
                        :severity="
                            getActionSeverity(selectedEvenement.type_action)
                        "
                        rounded
                        size="small"
                    />
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span class="detail-value">{{
                        selectedEvenement.description
                    }}</span>
                </div>
                <div class="detail-row" v-if="selectedEvenement.entite">
                    <span class="detail-label">Entité</span>
                    <span class="detail-value"
                        >{{ selectedEvenement.entite }} #{{
                            selectedEvenement.entite_id
                        }}</span
                    >
                </div>

                <!-- Données avant/après -->
                <div v-if="hasDonnees" class="donnees-section">
                    <h5>Données modifiées</h5>
                    <div
                        v-if="selectedEvenement.donnees_avant"
                        class="donnees-block"
                    >
                        <h6>Avant</h6>
                        <pre>{{
                            JSON.stringify(
                                selectedEvenement.donnees_avant,
                                null,
                                2,
                            )
                        }}</pre>
                    </div>
                    <div
                        v-if="selectedEvenement.donnees_apres"
                        class="donnees-block"
                    >
                        <h6>Après</h6>
                        <pre>{{
                            JSON.stringify(
                                selectedEvenement.donnees_apres,
                                null,
                                2,
                            )
                        }}</pre>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Fermer"
                    icon="pi pi-times"
                    @click="showDetails = false"
                    outlined
                    severity="secondary"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.tracabilite-container {
    padding: 0.5rem;
}

@media (min-width: 640px) {
    .tracabilite-container {
        padding: 1rem;
    }
}

@media (min-width: 1024px) {
    .tracabilite-container {
        padding: 1rem 2rem;
    }
}

/* Header */
.header-card-wrapper {
    margin-bottom: 1rem;
}
.header-card {
    border: none !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    background: linear-gradient(to right, #fef3c7, white);
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

.icon-wrapper {
    width: 3rem;
    height: 3rem;
    background: #f59e0b;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.icon-wrapper i {
    font-size: 1.5rem;
    color: white;
}

.header-text h1 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #111827;
}

@media (min-width: 1024px) {
    .header-text h1 {
        font-size: 1.875rem;
    }
}

.header-text p {
    margin: 0;
    color: #6b7280;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
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

/* Messages */
.message-wrapper {
    margin-bottom: 0.75rem;
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

/* Filtres */
.filters-card-wrapper {
    margin-bottom: 0.75rem;
}
.filters-card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.filters-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .filters-content {
        flex-direction: row;
        align-items: flex-end;
    }
    .filter-field {
        flex: 1;
    }
}

.filter-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.filter-field label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.search-input-wrapper {
    display: flex;
    gap: 0.5rem;
}

/* Tableau */
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
}

.title-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.title-left i {
    color: #f59e0b;
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

.evenements-table {
    font-size: 0.75rem;
}
@media (min-width: 640px) {
    .evenements-table {
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

.author-avatar {
    background: #f59e0b !important;
    color: white !important;
    flex-shrink: 0;
}
.author-info {
    display: flex;
    flex-direction: column;
    min-width: 0;
}
.author-name {
    font-weight: 600;
    font-size: 0.75rem;
    color: #1f2937;
}
.author-email {
    font-size: 0.65rem;
    color: #6b7280;
}
.description-text {
    font-size: 0.8rem;
    color: #374151;
    line-height: 1.4;
}

/* Pagination */
.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 1rem;
    gap: 0.25rem;
    flex-wrap: wrap;
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

.pagination-link:hover {
    background: #f3f4f6;
}
.pagination-link.active {
    background: #f59e0b;
    border-color: #f59e0b;
    color: white;
}
.pagination-link.disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* Empty state */
.empty-state-wrapper {
    margin-top: 1rem;
}
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}
.empty-icon {
    width: 4rem;
    height: 4rem;
    background: #f3f4f6;
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
.empty-state h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.5rem 0;
}
.empty-state p {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Dialog détails */
.details-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.detail-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
.detail-label {
    width: 100px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    flex-shrink: 0;
}
.detail-value {
    flex: 1;
    font-size: 0.875rem;
    color: #1f2937;
}

.donnees-section {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
}
.donnees-section h5 {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}
.donnees-block {
    margin-bottom: 0.5rem;
}
.donnees-block h6 {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin: 0 0 0.25rem 0;
}
.donnees-block pre {
    background: #f9fafb;
    padding: 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.7rem;
    max-height: 150px;
    overflow-y: auto;
    margin: 0;
}

/* Responsive DataTable */
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
}

:deep(.p-inputtext:focus),
:deep(.p-select:focus) {
    border-color: #f59e0b !important;
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important;
}

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
</style>
