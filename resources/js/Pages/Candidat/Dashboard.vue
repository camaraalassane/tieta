<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Link } from "@inertiajs/vue3";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Card from "primevue/card";
import Badge from "primevue/badge";
import Dialog from "primevue/dialog";
import { ref, computed } from "vue";

const props = defineProps({
    concours_disponibles: { type: Array, default: () => [] },
    mes_candidatures: { type: Array, default: () => [] },
    resultats_publies: { type: Array, default: () => [] },
    communiques_actifs: { type: Array, default: () => [] },
    avis_concours: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({ total: 0, admis: 0, traitement: 0, rejete: 0 }),
    },
});

// États
const showAllResults = ref(false);
const showAllCommuniques = ref(false);
const showAllAvis = ref(false);
const showAllConcours = ref(false);
const expandedRows = ref([]);

// Dialog
const showCommuniqueDialog = ref(false);
const selectedCommunique = ref(null);

// Éléments affichés
const displayedResults = computed(() =>
    showAllResults.value
        ? props.resultats_publies
        : props.resultats_publies.slice(0, 3),
);
const displayedCommuniques = computed(() =>
    showAllCommuniques.value
        ? props.communiques_actifs
        : props.communiques_actifs.slice(0, 3),
);
const displayedAvis = computed(() =>
    showAllAvis.value ? props.avis_concours : props.avis_concours.slice(0, 3),
);
const displayedConcours = computed(() =>
    showAllConcours.value
        ? props.concours_disponibles
        : props.concours_disponibles.slice(0, 4),
);

// Compteurs
const hiddenResultsCount = computed(() =>
    Math.max(0, props.resultats_publies.length - 3),
);
const hiddenCommuniquesCount = computed(() =>
    Math.max(0, props.communiques_actifs.length - 3),
);
const hiddenAvisCount = computed(() =>
    Math.max(0, props.avis_concours.length - 3),
);
const hiddenConcoursCount = computed(() =>
    Math.max(0, props.concours_disponibles.length - 4),
);

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

const truncateText = (text, maxLength = 60) => {
    if (!text) return "";
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + "...";
};

const getSeverity = (resultat) => {
    if (!resultat) return "info";
    const r = resultat.toLowerCase();
    if (r.includes("admis")) return "success";
    if (r.includes("rejété") || r.includes("rejeté")) return "danger";
    if (r.includes("traitement") || r.includes("attente")) return "warn";
    return "info";
};

const getSeverityProps = (resultat) => {
    const severity = getSeverity(resultat);
    return {
        severity,
        class:
            severity === "success"
                ? "bg-green-100 text-green-700 border-green-300"
                : severity === "danger"
                  ? "bg-red-100 text-red-700 border-red-300"
                  : severity === "warn"
                    ? "bg-orange-100 text-orange-700 border-orange-300"
                    : "bg-blue-100 text-blue-700 border-blue-300",
    };
};

const downloadFile = (id) => {
    if (!id) return;
    window.open(`/candidat-resultat/${id}/voir`, "_blank");
};

const downloadAvis = (url) => {
    if (!url) return;
    window.open(url, "_blank");
};

const openCommunique = (communique) => {
    selectedCommunique.value = communique;
    showCommuniqueDialog.value = true;
};
</script>

<template>
    <app-layout>
        <div class="dashboard-container">
            <!-- Hero Card - Bienvenue -->
            <div class="hero-section">
                <Card class="hero-card">
                    <template #content>
                        <div class="hero-content">
                            <div class="hero-icon">
                                <i class="pi pi-star"></i>
                            </div>
                            <div class="hero-text">
                                <h1>Bienvenue</h1>
                                <p>
                                    Suivez vos dossiers et découvrez les
                                    nouveaux concours
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Statistiques - 2x2 sur mobile, 4x1 sur desktop -->
            <div class="stats-grid">
                <div
                    class="stat-card stat-blue"
                    @click="$router.get(route('candidat-dossier.index'))"
                >
                    <div class="stat-content">
                        <div class="stat-info">
                            <span class="stat-label">Candidatures</span>
                            <span class="stat-value">{{ stats.total }}</span>
                        </div>
                        <div class="stat-icon">
                            <i class="pi pi-file"></i>
                        </div>
                    </div>
                </div>

                <div
                    class="stat-card stat-green"
                    @click="$router.get(route('candidat-dossier.index'))"
                >
                    <div class="stat-content">
                        <div class="stat-info">
                            <span class="stat-label">Admissions</span>
                            <span class="stat-value">{{ stats.admis }}</span>
                        </div>
                        <div class="stat-icon">
                            <i class="pi pi-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div
                    class="stat-card stat-orange"
                    @click="$router.get(route('candidat-dossier.index'))"
                >
                    <div class="stat-content">
                        <div class="stat-info">
                            <span class="stat-label">En traitement</span>
                            <span class="stat-value">{{
                                stats.traitement
                            }}</span>
                        </div>
                        <div class="stat-icon">
                            <i class="pi pi-spinner"></i>
                        </div>
                    </div>
                </div>

                <div
                    class="stat-card stat-red"
                    @click="$router.get(route('candidat-dossier.index'))"
                >
                    <div class="stat-content">
                        <div class="stat-info">
                            <span class="stat-label">Rejetées</span>
                            <span class="stat-value">{{ stats.rejete }}</span>
                        </div>
                        <div class="stat-icon">
                            <i class="pi pi-times-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Suivi des dossiers -->
            <div class="section-card">
                <Card>
                    <template #title>
                        <div class="card-header">
                            <div class="card-title">
                                <i class="pi pi-list text-emerald-500"></i>
                                <span>Suivi des dossiers</span>
                            </div>
                            <Badge
                                :value="mes_candidatures.length"
                                severity="info"
                            />
                        </div>
                    </template>
                    <template #content>
                        <!-- Desktop : Tableau -->
                        <div class="desktop-only">
                            <DataTable
                                :value="mes_candidatures"
                                :rows="5"
                                dataKey="id"
                                class="p-datatable-sm"
                                stripedRows
                                showGridlines
                            >
                                <Column
                                    field="concour.intitule"
                                    header="Concours"
                                >
                                    <template #body="sp">
                                        <div class="cell-with-icon">
                                            <i
                                                class="pi pi-briefcase text-emerald-500"
                                            ></i>
                                            <span class="truncate">{{
                                                sp.data.concour?.intitule
                                            }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column
                                    field="concour.service_nom"
                                    header="Service"
                                >
                                    <template #body="sp">
                                        <div class="cell-with-icon">
                                            <i
                                                class="pi pi-building text-gray-400"
                                            ></i>
                                            <span class="truncate">{{
                                                sp.data.concour?.service_nom ||
                                                "-"
                                            }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Date">
                                    <template #body="sp">
                                        <div class="cell-with-icon">
                                            <i
                                                class="pi pi-calendar text-gray-400"
                                            ></i>
                                            <span>{{
                                                formatDate(sp.data.created_at)
                                            }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="État">
                                    <template #body="sp">
                                        <Tag
                                            :severity="
                                                getSeverity(sp.data.resultat)
                                            "
                                            :value="
                                                sp.data.resultat?.toUpperCase() ||
                                                'ATTENTE'
                                            "
                                            rounded
                                            class="status-tag"
                                            :class="
                                                getSeverityProps(
                                                    sp.data.resultat,
                                                ).class
                                            "
                                        />
                                    </template>
                                </Column>
                            </DataTable>
                        </div>

                        <!-- Mobile : Liste de cartes -->
                        <div class="mobile-only">
                            <div
                                v-if="mes_candidatures.length === 0"
                                class="empty-state"
                            >
                                <i class="pi pi-inbox"></i>
                                <p>Aucune candidature</p>
                            </div>
                            <div v-else class="mobile-list">
                                <div
                                    v-for="c in mes_candidatures.slice(0, 5)"
                                    :key="c.id"
                                    class="mobile-card"
                                >
                                    <div class="mobile-card-header">
                                        <div
                                            class="mobile-card-icon bg-emerald-100"
                                        >
                                            <i
                                                class="pi pi-briefcase text-emerald-600"
                                            ></i>
                                        </div>
                                        <div class="mobile-card-title">
                                            <span class="truncate">{{
                                                c.concour?.intitule
                                            }}</span>
                                            <small class="text-gray-500">{{
                                                c.concour?.service_nom || ""
                                            }}</small>
                                        </div>
                                        <Tag
                                            :severity="getSeverity(c.resultat)"
                                            :value="
                                                c.resultat?.toUpperCase() ||
                                                'ATTENTE'
                                            "
                                            rounded
                                            class="status-tag-mobile"
                                        />
                                    </div>
                                    <div class="mobile-card-footer">
                                        <span
                                            ><i class="pi pi-calendar"></i>
                                            {{ formatDate(c.created_at) }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Section Communiqués et Avis - 2 colonnes -->
            <div class="two-columns">
                <!-- Communiqués officiels -->
                <div class="section-card">
                    <Card class="communique-card">
                        <template #title>
                            <div class="card-header">
                                <div class="card-title">
                                    <i
                                        class="pi pi-megaphone text-blue-500"
                                    ></i>
                                    <span>Communiqués</span>
                                </div>
                                <Badge
                                    :value="communiques_actifs.length"
                                    severity="info"
                                />
                            </div>
                        </template>
                        <template #content>
                            <div
                                v-if="communiques_actifs.length === 0"
                                class="empty-state"
                            >
                                <i class="pi pi-inbox"></i>
                                <p>Aucun communiqué</p>
                            </div>
                            <div v-else class="mobile-list">
                                <div
                                    v-for="c in displayedCommuniques"
                                    :key="c.id"
                                    class="list-item"
                                    @click="openCommunique(c)"
                                >
                                    <div class="list-item-icon bg-blue-100">
                                        <i
                                            class="pi pi-megaphone text-blue-600"
                                        ></i>
                                    </div>
                                    <div class="list-item-content">
                                        <div class="list-item-title">
                                            <span class="truncate">{{
                                                c.titre
                                            }}</span>
                                            <Tag
                                                value="Nouveau"
                                                severity="info"
                                                size="small"
                                            />
                                        </div>
                                        <div class="list-item-meta">
                                            <span
                                                ><i class="pi pi-calendar"></i>
                                                {{ c.published_at }}</span
                                            >
                                            <span v-if="c.service_nom"
                                                ><i class="pi pi-building"></i>
                                                {{ c.service_nom }}</span
                                            >
                                        </div>
                                        <p class="list-item-text">
                                            {{ truncateText(c.contenu, 50) }}
                                        </p>
                                        <div
                                            v-if="c.fichier_url"
                                            class="list-item-attachment"
                                        >
                                            <i class="pi pi-paperclip"></i>
                                            Pièce jointe
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    v-if="communiques_actifs.length > 3"
                                    :label="
                                        showAllCommuniques
                                            ? 'Voir moins'
                                            : `Voir plus (${hiddenCommuniquesCount})`
                                    "
                                    icon="pi pi-chevron-down"
                                    :iconPos="
                                        showAllCommuniques ? 'right' : 'right'
                                    "
                                    link
                                    size="small"
                                    class="see-more-btn"
                                    @click="
                                        showAllCommuniques = !showAllCommuniques
                                    "
                                />
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Avis de concours -->
                <div class="section-card">
                    <Card class="avis-card">
                        <template #title>
                            <div class="card-header">
                                <div class="card-title">
                                    <i
                                        class="pi pi-file-pdf text-purple-500"
                                    ></i>
                                    <span>Avis de concours</span>
                                </div>
                                <Badge
                                    :value="avis_concours.length"
                                    severity="warn"
                                />
                            </div>
                        </template>
                        <template #content>
                            <div
                                v-if="avis_concours.length === 0"
                                class="empty-state"
                            >
                                <i class="pi pi-inbox"></i>
                                <p>Aucun avis</p>
                            </div>
                            <div v-else class="mobile-list">
                                <div
                                    v-for="a in displayedAvis"
                                    :key="a.id"
                                    class="list-item"
                                >
                                    <div class="list-item-icon bg-purple-100">
                                        <i
                                            class="pi pi-file-pdf text-purple-600"
                                        ></i>
                                    </div>
                                    <div class="list-item-content">
                                        <span
                                            class="list-item-title-text truncate"
                                            >{{ a.intitule }}</span
                                        >
                                        <div class="list-item-meta">
                                            <span
                                                ><i class="pi pi-building"></i>
                                                {{
                                                    a.service_nom || "Service"
                                                }}</span
                                            >
                                            <span
                                                ><i class="pi pi-calendar"></i>
                                                {{ a.date_publication }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="a.date_limite"
                                            class="list-item-deadline"
                                        >
                                            <i class="pi pi-clock"></i> Limite:
                                            {{ a.date_limite }}
                                        </div>
                                        <div class="list-item-actions">
                                            <Button
                                                v-if="a.avis_url"
                                                label="Télécharger"
                                                icon="pi pi-download"
                                                size="small"
                                                text
                                                class="action-btn purple"
                                                @click.stop="
                                                    downloadAvis(a.avis_url)
                                                "
                                            />
                                            <Link
                                                :href="
                                                    route(
                                                        'candidat-postuler.index',
                                                    )
                                                "
                                                class="action-link"
                                            >
                                                Postuler →
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                                <Button
                                    v-if="avis_concours.length > 3"
                                    :label="
                                        showAllAvis
                                            ? 'Voir moins'
                                            : `Voir plus (${hiddenAvisCount})`
                                    "
                                    icon="pi pi-chevron-down"
                                    link
                                    size="small"
                                    class="see-more-btn"
                                    @click="showAllAvis = !showAllAvis"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Section Résultats -->
            <div class="section-card">
                <Card class="resultats-card">
                    <template #title>
                        <div class="card-header">
                            <div class="card-title">
                                <i class="pi pi-trophy text-orange-500"></i>
                                <span>Mes Résultats</span>
                            </div>
                            <Badge
                                :value="resultats_publies.length"
                                severity="warn"
                            />
                        </div>
                    </template>
                    <template #content>
                        <div
                            v-if="resultats_publies.length === 0"
                            class="empty-state"
                        >
                            <i class="pi pi-file-pdf"></i>
                            <p>Aucun résultat disponible</p>
                        </div>
                        <div v-else class="mobile-list">
                            <div
                                v-for="r in displayedResults"
                                :key="r.id"
                                class="list-item result-item"
                                @click="downloadFile(r.id)"
                            >
                                <div class="list-item-icon bg-orange-100">
                                    <i class="pi pi-trophy text-orange-600"></i>
                                </div>
                                <div class="list-item-content">
                                    <span
                                        class="list-item-title-text truncate"
                                        >{{ r.intitule }}</span
                                    >
                                    <div class="list-item-meta">
                                        <span
                                            ><i class="pi pi-tag"></i>
                                            {{ r.concours_nom || "" }}</span
                                        >
                                    </div>
                                    <div class="list-item-meta">
                                        <span v-if="r.service_nom"
                                            ><i class="pi pi-building"></i>
                                            {{ r.service_nom }}</span
                                        >
                                        <span
                                            ><i class="pi pi-calendar"></i>
                                            {{ formatDate(r.updated_at) }}</span
                                        >
                                    </div>
                                </div>
                                <i class="pi pi-download download-icon"></i>
                            </div>
                            <Button
                                v-if="resultats_publies.length > 3"
                                :label="
                                    showAllResults
                                        ? 'Voir moins'
                                        : `Voir plus (${hiddenResultsCount})`
                                "
                                icon="pi pi-chevron-down"
                                link
                                size="small"
                                class="see-more-btn"
                                @click="showAllResults = !showAllResults"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Section Concours disponibles -->
            <div class="section-card">
                <Card>
                    <template #title>
                        <div class="card-header">
                            <div class="card-title">
                                <i class="pi pi-bullhorn text-emerald-500"></i>
                                <span>Concours disponibles</span>
                            </div>
                            <Badge
                                :value="concours_disponibles.length"
                                severity="success"
                            />
                        </div>
                    </template>
                    <template #content>
                        <div
                            v-if="concours_disponibles.length === 0"
                            class="empty-state"
                        >
                            <i class="pi pi-info-circle"></i>
                            <p>Aucun concours disponible</p>
                        </div>
                        <div v-else class="concours-grid">
                            <div
                                v-for="c in displayedConcours"
                                :key="c.id"
                                class="concours-item"
                            >
                                <div class="concours-icon bg-emerald-100">
                                    <i
                                        class="pi pi-briefcase text-emerald-600"
                                    ></i>
                                </div>
                                <div class="concours-content">
                                    <h4 class="truncate">{{ c.intitule }}</h4>
                                    <p
                                        v-if="c.service"
                                        class="concours-service"
                                    >
                                        <i class="pi pi-building"></i>
                                        {{ c.service.nom }}
                                    </p>
                                    <p class="concours-desc line-clamp-2">
                                        {{
                                            c.description ||
                                            "Aucune description"
                                        }}
                                    </p>
                                    <div class="concours-footer">
                                        <span class="concours-date">
                                            <i class="pi pi-clock"></i>
                                            {{ formatDate(c.date_limite) }}
                                        </span>
                                        <Link
                                            :href="
                                                route('candidat-postuler.index')
                                            "
                                            class="concours-link"
                                        >
                                            Postuler →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Button
                            v-if="concours_disponibles.length > 4"
                            :label="
                                showAllConcours
                                    ? 'Voir moins'
                                    : `Voir plus (${hiddenConcoursCount})`
                            "
                            icon="pi pi-chevron-down"
                            link
                            class="see-more-btn full-width"
                            @click="showAllConcours = !showAllConcours"
                        />
                    </template>
                </Card>
            </div>
        </div>

        <!-- Dialog Communiqué -->
        <Dialog
            v-model:visible="showCommuniqueDialog"
            :header="selectedCommunique?.titre || 'Communiqué'"
            modal
            :style="{ width: '90vw', maxWidth: '600px' }"
            class="communique-dialog"
        >
            <div v-if="selectedCommunique" class="dialog-content">
                <div class="dialog-meta">
                    <span
                        ><i class="pi pi-calendar"></i>
                        {{ selectedCommunique.published_at }}</span
                    >
                    <span v-if="selectedCommunique.service_nom"
                        ><i class="pi pi-building"></i>
                        {{ selectedCommunique.service_nom }}</span
                    >
                    <span
                        ><i class="pi pi-tag"></i>
                        {{ selectedCommunique.concour_intitule }}</span
                    >
                </div>
                <div class="dialog-body">
                    <p class="whitespace-pre-wrap">
                        {{ selectedCommunique.contenu }}
                    </p>
                </div>
                <div
                    v-if="selectedCommunique.fichier_url"
                    class="dialog-attachment"
                >
                    <a
                        :href="selectedCommunique.fichier_url"
                        target="_blank"
                        class="attachment-link"
                    >
                        <i class="pi pi-file-pdf"></i>
                        <span>{{
                            selectedCommunique.fichier_nom || "Télécharger"
                        }}</span>
                        <i class="pi pi-external-link"></i>
                    </a>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Fermer"
                    icon="pi pi-times"
                    @click="showCommuniqueDialog = false"
                    outlined
                />
            </template>
        </Dialog>
    </app-layout>
</template>

<style scoped>
/* ============================================ */
/* CONTAINER */
/* ============================================ */
.dashboard-container {
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 640px) {
    .dashboard-container {
        padding: 1rem;
        gap: 1.25rem;
    }
}

@media (min-width: 1024px) {
    .dashboard-container {
        padding: 1rem 1.5rem;
        gap: 1.5rem;
    }
}

/* ============================================ */
/* HERO CARD */
/* ============================================ */
.hero-card {
    border: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
}

.hero-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.25rem 0;
}

.hero-icon {
    width: 3.5rem;
    height: 3.5rem;
    background: #10b981;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.hero-icon i {
    font-size: 1.75rem;
    color: white;
}

.hero-text h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
    color: #1f2937;
}

.hero-text p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

@media (min-width: 640px) {
    .hero-icon {
        width: 4rem;
        height: 4rem;
    }
    .hero-icon i {
        font-size: 2rem;
    }
    .hero-text h1 {
        font-size: 1.75rem;
    }
    .hero-text p {
        font-size: 1rem;
    }
}

/* ============================================ */
/* STATS GRID - 2x2 sur mobile, 4x1 sur desktop */
/* ============================================ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

@media (min-width: 640px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 0.875rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #f3f4f6;
    cursor: pointer;
    transition: all 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
}

.stat-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.stat-blue .stat-value {
    color: #2563eb;
}
.stat-green .stat-value {
    color: #059669;
}
.stat-orange .stat-value {
    color: #ea580c;
}
.stat-red .stat-value {
    color: #dc2626;
}

.stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-blue .stat-icon {
    background: #dbeafe;
    color: #2563eb;
}
.stat-green .stat-icon {
    background: #d1fae5;
    color: #059669;
}
.stat-orange .stat-icon {
    background: #ffedd5;
    color: #ea580c;
}
.stat-red .stat-icon {
    background: #fee2e2;
    color: #dc2626;
}

.stat-icon i {
    font-size: 1.25rem;
}

@media (min-width: 640px) {
    .stat-card {
        padding: 1rem;
    }
    .stat-value {
        font-size: 1.75rem;
    }
    .stat-icon {
        width: 3rem;
        height: 3rem;
    }
    .stat-icon i {
        font-size: 1.5rem;
    }
}

/* ============================================ */
/* SECTION CARDS */
/* ============================================ */
.section-card {
    width: 100%;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

@media (min-width: 640px) {
    .card-title {
        font-size: 1.125rem;
    }
}

/* ============================================ */
/* TWO COLUMNS */
/* ============================================ */
.two-columns {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 1024px) {
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
}

/* ============================================ */
/* DESKTOP / MOBILE VISIBILITY */
/* ============================================ */
.desktop-only {
    display: none;
}

.mobile-only {
    display: block;
}

@media (min-width: 768px) {
    .desktop-only {
        display: block;
    }
    .mobile-only {
        display: none;
    }
}

/* ============================================ */
/* TABLEAU DESKTOP */
/* ============================================ */
.cell-with-icon {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-tag {
    font-size: 0.7rem !important;
    padding: 0.2rem 0.75rem !important;
    border-width: 1px !important;
    border-style: solid !important;
}

/* ============================================ */
/* MOBILE LIST */
/* ============================================ */
.mobile-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
}

/* Mobile Card (pour suivi des dossiers) */
.mobile-card {
    background: #f9fafb;
    border-radius: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
}

.mobile-card-header {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.mobile-card-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.mobile-card-title {
    flex: 1;
    min-width: 0;
}

.mobile-card-title span {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    color: #1f2937;
}

.mobile-card-title small {
    display: block;
    font-size: 0.7rem;
    margin-top: 0.125rem;
}

.status-tag-mobile {
    font-size: 0.65rem !important;
    padding: 0.15rem 0.5rem !important;
    flex-shrink: 0;
}

.mobile-card-footer {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
    font-size: 0.7rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* List Item (communiqués, avis, résultats) */
.list-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.15s;
}

.list-item:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.list-item-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.list-item-content {
    flex: 1;
    min-width: 0;
}

.list-item-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.list-item-title span {
    font-weight: 600;
    font-size: 0.875rem;
    color: #1f2937;
}

.list-item-title-text {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.list-item-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 1rem;
    font-size: 0.7rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.list-item-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.list-item-text {
    font-size: 0.75rem;
    color: #4b5563;
    margin: 0.375rem 0 0 0;
    line-height: 1.4;
}

.list-item-attachment {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    color: #2563eb;
    margin-top: 0.375rem;
}

.list-item-deadline {
    font-size: 0.7rem;
    color: #ea580c;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0.25rem 0;
}

.list-item-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.action-btn {
    padding: 0.25rem 0.5rem !important;
    font-size: 0.75rem !important;
}

.action-btn.purple {
    color: #7c3aed !important;
}

.action-link {
    font-size: 0.75rem;
    color: #10b981;
    text-decoration: none;
    font-weight: 500;
}

.action-link:hover {
    text-decoration: underline;
}

.result-item .download-icon {
    color: #9ca3af;
    font-size: 1rem;
    transition: color 0.15s;
    flex-shrink: 0;
    margin-left: 0.5rem;
}

.result-item:hover .download-icon {
    color: #f97316;
}

/* ============================================ */
/* SEE MORE BUTTON */
/* ============================================ */
.see-more-btn {
    margin-top: 0.75rem !important;
    width: 100%;
    justify-content: center;
    color: #6b7280 !important;
    font-size: 0.8rem !important;
}

.see-more-btn:hover {
    color: #10b981 !important;
    background: #f0fdf4 !important;
}

.see-more-btn.full-width {
    width: 100%;
}

/* ============================================ */
/* CONCOURS GRID */
/* ============================================ */
.concours-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .concours-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}

.concours-item {
    display: flex;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
}

.concours-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.concours-content {
    flex: 1;
    min-width: 0;
}

.concours-content h4 {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: #1f2937;
}

.concours-service {
    font-size: 0.7rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    margin: 0 0 0.25rem 0;
}

.concours-desc {
    font-size: 0.75rem;
    color: #4b5563;
    margin: 0 0 0.5rem 0;
}

.concours-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.concours-date {
    font-size: 0.7rem;
    color: #ea580c;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.concours-link {
    font-size: 0.75rem;
    color: #10b981;
    text-decoration: none;
    font-weight: 500;
}

/* ============================================ */
/* DIALOG */
/* ============================================ */
.dialog-content {
    padding: 0.5rem;
}

.dialog-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.8rem;
    color: #6b7280;
}

.dialog-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.dialog-body {
    max-height: 40vh;
    overflow-y: auto;
    font-size: 0.9rem;
    line-height: 1.6;
    color: #374151;
}

.dialog-attachment {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.attachment-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #ecfdf5;
    border-radius: 0.5rem;
    color: #059669;
    text-decoration: none;
    font-size: 0.875rem;
}

.attachment-link:hover {
    background: #d1fae5;
}

/* ============================================ */
/* UTILITIES */
/* ============================================ */
.truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.whitespace-pre-wrap {
    white-space: pre-wrap;
}

/* ============================================ */
/* PRIMEVUE OVERRIDES */
/* ============================================ */
:deep(.p-card) {
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid #f3f4f6;
}

:deep(.p-card .p-card-body) {
    padding: 1rem;
}

@media (min-width: 640px) {
    :deep(.p-card .p-card-body) {
        padding: 1.25rem;
    }
}

:deep(.p-card .p-card-title) {
    font-size: 1rem;
    padding-bottom: 0.75rem;
    margin-bottom: 0;
}

:deep(.p-card .p-card-content) {
    padding: 0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc;
    font-size: 0.8rem;
    padding: 0.75rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem;
    font-size: 0.8rem;
}

:deep(.p-tag) {
    font-size: 0.65rem !important;
    padding: 0.15rem 0.5rem !important;
}

:deep(.p-badge) {
    font-size: 0.7rem;
}

/* ============================================ */
/* DARK MODE */
/* ============================================ */
.dark .hero-card {
    background: linear-gradient(135deg, #064e3b 0%, #1f2937 100%);
}

.dark .hero-text h1 {
    color: #f3f4f6;
}

.dark .stat-card,
.dark .mobile-card,
.dark .list-item,
.dark .concours-item {
    background: #1f2937;
    border-color: #374151;
}

.dark .list-item:hover {
    background: #374151;
    border-color: #4b5563;
}

.dark .card-title {
    color: #f3f4f6;
}

.dark .mobile-card-title span,
.dark .list-item-title span,
.dark .list-item-title-text,
.dark .concours-content h4 {
    color: #f3f4f6;
}

.dark .dialog-meta {
    border-color: #374151;
}

.dark .dialog-body {
    color: #d1d5db;
}

.dark .empty-state {
    color: #6b7280;
}

.dark :deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #374151;
    color: #e5e7eb;
}
</style>
