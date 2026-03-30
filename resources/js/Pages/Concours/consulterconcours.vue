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
});

const confirm = useConfirm();
const toast = useToast();
const op = ref();
const selectedFiles = ref([]);
const selectedConcour = ref(
    props.selectedConcourId ? Number(props.selectedConcourId) : null,
);
const expandedRows = ref([]);

// Recherche globale uniquement
const searchQuery = ref(props.filters?.search || "");

// Debounce pour la recherche
let searchTimeout;
watch(searchQuery, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applySearch(), 300);
});

// Appliquer la recherche
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

// Réinitialiser la recherche
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

// Mise à jour du motif avec debounce
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

        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card
                        class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-900"
                    >
                        <template #content>
                            <div
                                class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4"
                            >
                                <div class="flex align-items-center gap-4">
                                    <div
                                        class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-center"
                                    >
                                        <i
                                            class="pi pi-users text-white text-3xl"
                                        ></i>
                                    </div>
                                    <div>
                                        <h1
                                            class="text-3xl font-bold text-900 m-0 mb-2"
                                        >
                                            Gestion des candidatures
                                        </h1>
                                        <p
                                            class="text-600 m-0 flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-check-circle text-emerald-500"
                                            ></i>
                                            Traitez et gérez les candidatures
                                            par concours
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Sélection du concours -->
            <div class="grid mb-4">
                <div class="col-12">
                    <Card class="shadow-sm">
                        <template #content>
                            <div
                                class="flex flex-column md:flex-row align-items-center gap-4"
                            >
                                <div class="flex-1 w-full">
                                    <label
                                        class="block font-medium text-sm text-600 mb-2"
                                    >
                                        <i
                                            class="pi pi-filter mr-2 text-emerald-500"
                                        ></i>
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
            </div>

            <!-- Recherche globale (affichée seulement quand un concours est sélectionné) -->
            <div v-if="selectedConcour && candidatures" class="grid mb-4">
                <div class="col-12">
                    <Card class="shadow-sm">
                        <template #content>
                            <div
                                class="flex flex-column md:flex-row align-items-center gap-4"
                            >
                                <div class="flex-1 w-full">
                                    <label
                                        class="block font-medium text-sm text-600 mb-2"
                                    >
                                        <i
                                            class="pi pi-search mr-2 text-emerald-500"
                                        ></i>
                                        Rechercher un candidat
                                    </label>
                                    <div class="flex gap-2">
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
                                            class="p-button-outlined p-button-secondary"
                                            @click="resetSearch"
                                            v-tooltip.top="
                                                'Effacer la recherche'
                                            "
                                        />
                                    </div>
                                    <small class="text-400 text-xs"
                                        >La recherche s'effectue sur le nom,
                                        prénom, n° de dossier, email et
                                        motif</small
                                    >
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Tableau des candidatures -->
            <div v-if="selectedConcour && candidatures" class="grid">
                <div class="col-12">
                    <Card class="shadow-md">
                        <template #title>
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-list text-emerald-500"></i>
                                <span class="text-lg font-semibold"
                                    >Liste des candidatures</span
                                >
                                <span class="text-sm text-600 ml-auto"
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
                                class="p-datatable-sm"
                                stripedRows
                                showGridlines
                                responsiveLayout="stack"
                                :loading="!candidatures.data"
                            >
                                <Column expander style="width: 3rem" />

                                <Column
                                    field="num_dossier"
                                    header="N° Dossier"
                                    sortable
                                >
                                    <template #body="slotProps">
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-qrcode text-emerald-500"
                                            ></i>
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
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <Avatar
                                                :label="
                                                    slotProps.data.nom_complet?.charAt(
                                                        0,
                                                    ) || '?'
                                                "
                                                size="small"
                                                shape="circle"
                                                class="bg-emerald-500 text-white"
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
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-calendar text-400"
                                            ></i>
                                            <span>{{
                                                slotProps.data.created_at
                                            }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Statut">
                                    <template #body="slotProps">
                                        <Tag
                                            :severity="
                                                getSeverity(
                                                    slotProps.data.resultat,
                                                )
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
                                            class="px-3 py-1"
                                        />
                                    </template>
                                </Column>

                                <Column header="Dossier" style="width: 6rem">
                                    <template #body="slotProps">
                                        <Button
                                            icon="pi pi-paperclip"
                                            class="p-button-rounded p-button-text p-button-sm"
                                            :badge="
                                                slotProps.data.fichiers
                                                    ?.length || null
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

                                <Column header="Actions" style="width: 12rem">
                                    <template #body="slotProps">
                                        <div class="flex gap-1">
                                            <Button
                                                icon="pi pi-check"
                                                class="p-button-rounded p-button-text p-button-sm text-green-600 hover:text-green-700"
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
                                                class="p-button-rounded p-button-text p-button-sm text-red-600 hover:text-red-700"
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
                                                        concour_id:
                                                            selectedConcour,
                                                    })
                                                "
                                            >
                                                <Button
                                                    icon="pi pi-envelope"
                                                    class="p-button-rounded p-button-text p-button-sm text-blue-600 hover:text-blue-700"
                                                    v-tooltip.top="
                                                        'Envoyer un message'
                                                    "
                                                />
                                            </Link>
                                            <Link
                                                v-if="slotProps.data.profil_id"
                                                :href="
                                                    route(
                                                        'candidat-profil.show',
                                                        {
                                                            id: slotProps.data
                                                                .profil_id,
                                                        },
                                                    )
                                                "
                                            >
                                                <Button
                                                    icon="pi pi-user"
                                                    class="p-button-rounded p-button-text p-button-sm text-emerald-600 hover:text-emerald-700"
                                                    v-tooltip.top="
                                                        'Voir le profil'
                                                    "
                                                />
                                            </Link>
                                        </div>
                                    </template>
                                </Column>

                                <template #expansion="slotProps">
                                    <div
                                        class="p-4 bg-gray-50 dark:bg-gray-800"
                                    >
                                        <h5 class="font-semibold mb-3">
                                            Informations complémentaires
                                        </h5>
                                        <div class="grid">
                                            <div class="col-12 md:col-6">
                                                <div class="field">
                                                    <label
                                                        class="text-xs text-600"
                                                        >Motif /
                                                        Remarques</label
                                                    >
                                                    <InputText
                                                        v-model="
                                                            slotProps.data.motif
                                                        "
                                                        @blur="
                                                            updateMotif(
                                                                slotProps.data
                                                                    .id,
                                                                slotProps.data
                                                                    .motif,
                                                            )
                                                        "
                                                        placeholder="Ajouter un motif..."
                                                        class="w-full"
                                                    />
                                                </div>
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <div class="field">
                                                    <label
                                                        class="text-xs text-600"
                                                        >Dernière
                                                        modification</label
                                                    >
                                                    <InputText
                                                        :value="
                                                            slotProps.data
                                                                .updated_at ||
                                                            'Jamais'
                                                        "
                                                        disabled
                                                        class="w-full"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </DataTable>

                            <!-- Pagination -->
                            <div
                                class="flex align-items-center justify-content-center mt-4 gap-1"
                            >
                                <Link
                                    v-for="link in candidatures.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    class="p-button p-button-sm no-underline"
                                    :class="{
                                        'p-button-primary': link.active,
                                        'p-button-outlined p-button-secondary':
                                            !link.active,
                                        'opacity-50 pointer-events-none':
                                            !link.url,
                                    }"
                                    v-html="link.label"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- État quand aucun concours sélectionné -->
            <div v-else-if="!selectedConcour" class="grid">
                <div class="col-12">
                    <Card class="shadow-sm">
                        <template #content>
                            <div class="text-center py-8">
                                <div
                                    class="w-20 h-20 bg-50 border-circle flex align-items-center justify-content-center mb-4 mx-auto"
                                >
                                    <i
                                        class="pi pi-search-minus text-4xl text-400"
                                    ></i>
                                </div>
                                <h3 class="text-xl font-semibold text-900 mb-2">
                                    Aucun concours sélectionné
                                </h3>
                                <p class="text-500">
                                    Veuillez choisir un concours pour voir les
                                    candidatures
                                </p>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Overlay Panel pour les fichiers -->
            <OverlayPanel ref="op">
                <div class="p-3" style="min-width: 250px">
                    <h5 class="font-semibold mb-3">Pièces jointes</h5>
                    <div
                        v-if="selectedFiles.length > 0"
                        class="flex flex-column gap-2"
                    >
                        <div
                            v-for="file in selectedFiles"
                            :key="file.url"
                            class="flex align-items-center gap-2 p-2 hover:surface-hover border-round transition-colors"
                        >
                            <i class="pi pi-file-pdf text-red-500"></i>
                            <a
                                :href="'/storage/' + file.url"
                                target="_blank"
                                class="flex-1 text-sm text-700 no-underline hover:text-emerald-600"
                            >
                                {{ file.nom }}
                            </a>
                            <i class="pi pi-external-link text-400"></i>
                        </div>
                    </div>
                    <div v-else class="text-center py-3 text-400">
                        <i class="pi pi-file-excel text-2xl mb-2"></i>
                        <p class="text-sm">Aucune pièce jointe</p>
                    </div>
                </div>
            </OverlayPanel>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.row-admitted) {
    background-color: rgba(16, 185, 129, 0.05) !important;
}

:deep(.row-rejected) {
    background-color: rgba(239, 68, 68, 0.05) !important;
}

:deep(.row-admitted:hover),
:deep(.row-rejected:hover) {
    filter: brightness(0.98);
}

:deep(.p-datatable-stack .p-column-title) {
    font-weight: 600;
    color: var(--surface-700);
}

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
:deep(.p-select:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

/* Pagination personnalisée */
:deep(.p-paginator) {
    padding: 1rem 0;
}

:deep(.p-paginator .p-paginator-page.p-highlight) {
    background: #10b981;
    border-color: #10b981;
    color: white;
}
</style>
