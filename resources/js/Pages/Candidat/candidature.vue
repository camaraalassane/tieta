<script setup>
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import Card from "primevue/card";
import { ref, computed } from "vue";
import { FilterMatchMode } from "@primevue/core/api";

const props = defineProps({
    candidatures: Array,
});

// Configuration des filtres
const dtFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

// Statistiques
const stats = computed(() => {
    const total = props.candidatures?.length || 0;
    const admis =
        props.candidatures?.filter((c) => c.resultat === "Admis").length || 0;
    const rejetes =
        props.candidatures?.filter((c) => c.resultat === "Rejeté").length || 0;
    const traitement =
        props.candidatures?.filter((c) => c.resultat === "Traitement").length ||
        0;
    return { total, admis, rejetes, traitement };
});

const getResultatSeverity = (res) => {
    switch (res) {
        case "Admis":
            return "success";
        case "Rejeté":
            return "danger";
        case "Traitement":
            return "info";
        default:
            return "secondary";
    }
};

const getResultatIcon = (res) => {
    switch (res) {
        case "Admis":
            return "pi-check-circle";
        case "Rejeté":
            return "pi-times-circle";
        case "Traitement":
            return "pi-spin pi-spinner";
        default:
            return "pi-question-circle";
    }
};

const viewDetails = (id) => {
    router.visit(route("candidature.show", id));
};

const printReceipt = (id) => {
    window.open(route("candidature.receipt", id), "_blank");
};
</script>

<template>
    <AppLayout>
        <Head title="Mes Candidatures" />

        <div class="candidature-container">
            <!-- Statistiques - Cartes responsive -->
            <div class="stats-grid">
                <div class="stat-card stat-total">
                    <div class="stat-icon">
                        <i class="pi pi-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">{{ stats.total }}</span>
                        <span class="stat-label">Total candidatures</span>
                    </div>
                </div>
                <div class="stat-card stat-admis">
                    <div class="stat-icon">
                        <i class="pi pi-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">{{ stats.admis }}</span>
                        <span class="stat-label">Admis</span>
                    </div>
                </div>
                <div class="stat-card stat-rejetes">
                    <div class="stat-icon">
                        <i class="pi pi-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">{{ stats.rejetes }}</span>
                        <span class="stat-label">Rejetés</span>
                    </div>
                </div>
                <div class="stat-card stat-traitement">
                    <div class="stat-icon">
                        <i class="pi pi-spinner pi-spin"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">{{ stats.traitement }}</span>
                        <span class="stat-label">En traitement</span>
                    </div>
                </div>
            </div>

            <!-- Carte principale -->
            <div class="main-card">
                <!-- En-tête -->
                <div class="card-header">
                    <div class="header-title">
                        <i class="pi pi-briefcase header-icon"></i>
                        <div>
                            <h1>Suivi de mes candidatures</h1>
                            <p>
                                État d'avancement de vos dossiers en temps réel
                            </p>
                        </div>
                    </div>
                    <div class="header-search">
                        <IconField iconPosition="left">
                            <InputIcon class="pi pi-search" />
                            <InputText
                                v-model="dtFilters['global'].value"
                                placeholder="Rechercher..."
                                class="search-input"
                            />
                        </IconField>
                    </div>
                </div>

                <!-- Version Desktop - DataTable -->
                <div class="desktop-view">
                    <DataTable
                        :value="candidatures"
                        :filters="dtFilters"
                        dataKey="id"
                        :rows="10"
                        :paginator="true"
                        :globalFilterFields="[
                            'num_dossier',
                            'concours',
                            'motif',
                        ]"
                        responsiveLayout="stack"
                        breakpoint="960px"
                        stripedRows
                        showGridlines
                        class="custom-datatable"
                    >
                        <template #empty>
                            <div class="empty-state">
                                <i class="pi pi-folder-open empty-icon"></i>
                                <span>Aucun dossier trouvé</span>
                                <p class="empty-hint">
                                    Vous n'avez pas encore déposé de candidature
                                </p>
                            </div>
                        </template>

                        <Column
                            field="num_dossier"
                            header="N° DOSSIER"
                            sortable
                        >
                            <template #body="slotProps">
                                <span class="dossier-number">{{
                                    slotProps.data.num_dossier
                                }}</span>
                            </template>
                        </Column>

                        <Column field="concours" header="CONCOURS" sortable>
                            <template #body="slotProps">
                                <span class="concours-name">{{
                                    slotProps.data.concours
                                }}</span>
                            </template>
                        </Column>

                        <Column field="date" header="DATE SOUMISSION" sortable>
                            <template #body="slotProps">
                                <div class="date-cell">
                                    <i class="pi pi-calendar"></i>
                                    <span>{{ slotProps.data.date }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="resultat" header="RÉSULTAT">
                            <template #body="slotProps">
                                <Tag
                                    :value="slotProps.data.resultat"
                                    :severity="
                                        getResultatSeverity(
                                            slotProps.data.resultat,
                                        )
                                    "
                                    class="status-tag"
                                >
                                    <i
                                        :class="
                                            getResultatIcon(
                                                slotProps.data.resultat,
                                            )
                                        "
                                        class="mr-1"
                                    ></i>
                                    {{ slotProps.data.resultat }}
                                </Tag>
                            </template>
                        </Column>

                        <Column field="motif" header="MOTIF">
                            <template #body="slotProps">
                                <span class="motif-text">
                                    {{
                                        slotProps.data.motif ||
                                        "Aucun motif spécifié"
                                    }}
                                </span>
                            </template>
                        </Column>

                        <Column header="ACTIONS" headerStyle="width: 8rem">
                            <template #body="slotProps">
                                <div class="action-buttons">
                                    <Button
                                        icon="pi pi-eye"
                                        class="p-button-text p-button-rounded action-view"
                                        v-tooltip.top="'Voir le dossier'"
                                        @click="viewDetails(slotProps.data.id)"
                                    />
                                    <Button
                                        v-if="
                                            slotProps.data.resultat ===
                                            'Traitement'
                                        "
                                        icon="pi pi-print"
                                        class="p-button-text p-button-rounded action-print"
                                        v-tooltip.top="'Imprimer le récépissé'"
                                        @click="printReceipt(slotProps.data.id)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Version Mobile - Cartes -->
                <div class="mobile-view">
                    <div v-if="candidatures?.length === 0" class="empty-state">
                        <i class="pi pi-folder-open empty-icon"></i>
                        <span>Aucun dossier trouvé</span>
                    </div>
                    <div
                        v-for="candidature in candidatures"
                        :key="candidature.id"
                        class="candidature-card"
                    >
                        <div
                            class="card-status"
                            :class="candidature.resultat.toLowerCase()"
                        >
                            <i
                                :class="getResultatIcon(candidature.resultat)"
                            ></i>
                            <span>{{ candidature.resultat }}</span>
                        </div>
                        <div class="card-content">
                            <div class="card-row">
                                <span class="row-label">N° Dossier</span>
                                <span class="row-value dossier-number">{{
                                    candidature.num_dossier
                                }}</span>
                            </div>
                            <div class="card-row">
                                <span class="row-label">Concours</span>
                                <span class="row-value">{{
                                    candidature.concours
                                }}</span>
                            </div>
                            <div class="card-row">
                                <span class="row-label">Date soumission</span>
                                <span class="row-value">
                                    <i class="pi pi-calendar mr-1"></i>
                                    {{ candidature.date }}
                                </span>
                            </div>
                            <div v-if="candidature.motif" class="card-row">
                                <span class="row-label">Motif</span>
                                <span class="row-value motif">{{
                                    candidature.motif
                                }}</span>
                            </div>
                            <div class="card-actions">
                                <Button
                                    icon="pi pi-eye"
                                    label="Détails"
                                    class="p-button-sm p-button-outlined action-view"
                                    @click="viewDetails(candidature.id)"
                                />
                                <Button
                                    v-if="candidature.resultat === 'Traitement'"
                                    icon="pi pi-print"
                                    label="Récépissé"
                                    class="p-button-sm p-button-outlined action-print"
                                    @click="printReceipt(candidature.id)"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style lang="scss" scoped>
.candidature-container {
    padding: 1rem;
    max-width: 1400px;
    margin: 0 auto;

    @media (min-width: 768px) {
        padding: 1.5rem;
    }

    @media (min-width: 1024px) {
        padding: 2rem;
    }
}

/* Statistiques - Grille responsive */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;

    @media (min-width: 640px) {
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    @media (min-width: 1024px) {
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
}

.stat-card {
    background: var(--surface-card);
    border-radius: 16px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition:
        transform 0.2s,
        box-shadow 0.2s;

    @media (min-width: 768px) {
        padding: 1.25rem;
        gap: 1rem;
    }

    &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;

    @media (min-width: 768px) {
        width: 48px;
        height: 48px;
        font-size: 1.5rem;
    }
}

.stat-total .stat-icon {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

.stat-admis .stat-icon {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

.stat-rejetes .stat-icon {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.stat-traitement .stat-icon {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.stat-content {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--text-color);

    @media (min-width: 768px) {
        font-size: 1.75rem;
    }
}

.stat-label {
    display: block;
    font-size: 0.7rem;
    color: var(--text-color-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;

    @media (min-width: 768px) {
        font-size: 0.75rem;
    }
}

/* Carte principale */
.main-card {
    background: var(--surface-card);
    border-radius: 20px;
    border: 1px solid var(--surface-border);
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.card-header {
    padding: 1.25rem;
    border-bottom: 1px solid var(--surface-border);
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.05) 0%,
        transparent 100%
    );

    @media (min-width: 768px) {
        padding: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }
}

.header-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;

    @media (min-width: 768px) {
        margin-bottom: 0;
    }

    .header-icon {
        font-size: 1.5rem;
        color: #10b981;
        background: rgba(16, 185, 129, 0.1);
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;

        @media (min-width: 768px) {
            font-size: 1.75rem;
            width: 52px;
            height: 52px;
        }
    }

    h1 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: var(--text-color);

        @media (min-width: 768px) {
            font-size: 1.5rem;
        }
    }

    p {
        font-size: 0.75rem;
        color: var(--text-color-secondary);
        margin: 0.25rem 0 0 0;

        @media (min-width: 768px) {
            font-size: 0.875rem;
        }
    }
}

.header-search {
    .search-input {
        width: 100%;
        border-radius: 12px;

        @media (min-width: 768px) {
            width: 280px;
        }
    }
}

/* DataTable personnalisé */
.custom-datatable {
    :deep(.p-datatable-thead > tr > th) {
        background: var(--surface-50);
        color: var(--surface-700);
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.875rem 1rem;
        white-space: nowrap;
    }

    :deep(.p-datatable-tbody > tr > td) {
        padding: 0.875rem 1rem;
        vertical-align: middle;
    }
}

.dossier-number {
    font-weight: 700;
    color: #10b981;
    font-family: monospace;
    font-size: 0.875rem;
}

.concours-name {
    font-weight: 500;
    font-size: 0.875rem;
}

.date-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;

    i {
        color: var(--text-color-secondary);
        font-size: 0.75rem;
    }
}

.status-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.7rem;
    font-weight: 600;
    border-radius: 20px;
}

.motif-text {
    font-size: 0.8rem;
    color: var(--text-color-secondary);
    font-style: italic;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
    justify-content: center;
}

.action-view {
    color: #10b981 !important;
}

.action-print {
    color: #3b82f6 !important;
}

/* Vue Mobile - Cartes */
.mobile-view {
    display: none;
    padding: 1rem;
    flex-direction: column;
    gap: 1rem;

    @media (max-width: 960px) {
        display: flex;
    }
}

.desktop-view {
    @media (max-width: 960px) {
        display: none;
    }
}

.candidature-card {
    background: var(--surface-card);
    border-radius: 16px;
    border: 1px solid var(--surface-border);
    overflow: hidden;
    transition: all 0.2s;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
}

.card-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;

    &.admis {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
    }

    &.rejeté {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    &.traitement {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }
}

.card-content {
    padding: 1rem;
}

.card-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--surface-border);

    &:last-of-type {
        border-bottom: none;
    }
}

.row-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-color-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.row-value {
    font-size: 0.85rem;
    font-weight: 500;
    text-align: right;
    word-break: break-word;
    max-width: 60%;

    &.dossier-number {
        color: #10b981;
        font-family: monospace;
        font-weight: 700;
    }

    &.motif {
        color: var(--text-color-secondary);
        font-style: italic;
    }
}

.card-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--surface-border);
}

/* État vide */
.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;

    .empty-icon {
        font-size: 3rem;
        color: var(--text-color-secondary);
        opacity: 0.5;
        margin-bottom: 1rem;
        display: block;
    }

    span {
        display: block;
        font-size: 1rem;
        color: var(--text-color);
        margin-bottom: 0.5rem;
    }

    .empty-hint {
        font-size: 0.8rem;
        color: var(--text-color-secondary);
        margin: 0;
    }
}
</style>
