<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import Card from "primevue/card";
import Tag from "primevue/tag";
import Divider from "primevue/divider";
import Dialog from "primevue/dialog";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";

const props = defineProps({
    candidature: Object,
    profil: Object,
    user: Object,
    fichiers: Array,
    diplomes: Array,
});

const toast = useToast();
const showFilesDialog = ref(false);
const selectedFiles = ref([]);

// Statut badge
const getStatusSeverity = (resultat) => {
    switch (resultat) {
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

// Ouvrir le dialogue des fichiers
const viewFiles = (fichiers) => {
    selectedFiles.value = fichiers;
    showFilesDialog.value = true;
};

// Imprimer le récépissé
const printReceipt = () => {
    window.open(route("candidature.receipt", props.candidature.id), "_blank");
    toast.add({
        severity: "info",
        summary: "Génération du PDF",
        detail: "Votre récépissé est en cours de génération...",
        life: 3000,
    });
};

// Retour
const goBack = () => {
    router.get(route("candidat-dossier.index"));
};

// Diplômes possédés
const diplomesPossedes = props.diplomes.filter((d) => d.valeur === "oui");

// Troncature du texte
const truncateText = (text, maxLength = 50) => {
    if (!text) return "";
    return text.length > maxLength
        ? text.substring(0, maxLength) + "..."
        : text;
};
</script>

<template>
    <AppLayout>
        <Toast />
        <Head :title="`Dossier N°${candidature.num_dossier}`" />

        <div class="detail-container">
            <!-- Carte en-tête -->
            <div class="header-card sakai-card mb-6">
                <div
                    class="flex flex-column md:flex-row md:align-items-center justify-content-between gap-3"
                >
                    <div class="flex align-items-center gap-3">
                        <div class="header-icon">
                            <i class="pi pi-id-card"></i>
                        </div>
                        <div>
                            <h1 class="header-title">Dossier de candidature</h1>
                            <p class="header-subtitle">
                                N° {{ candidature.num_dossier }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <Button
                            icon="pi pi-arrow-left"
                            label="Retour"
                            class="p-button-outlined p-button-sm back-btn"
                            @click="goBack"
                        />
                        <Button
                            label="Imprimer le récépissé"
                            icon="pi pi-print"
                            class="p-button-success print-btn"
                            @click="printReceipt"
                        />
                    </div>
                </div>
            </div>

            <!-- Grille principale -->
            <div class="details-grid">
                <!-- Colonne gauche -->
                <div class="details-left">
                    <!-- Carte Informations du dossier -->
                    <div class="info-card sakai-card">
                        <div class="card-header-info">
                            <div class="card-title">
                                <i class="pi pi-info-circle"></i>
                                <span>Informations du dossier</span>
                            </div>
                            <Tag
                                :value="candidature.resultat"
                                :severity="
                                    getStatusSeverity(candidature.resultat)
                                "
                                class="status-badge"
                            />
                        </div>
                        <div class="info-list">
                            <div class="info-item">
                                <span class="info-label">N° Dossier</span>
                                <span class="info-value dossier-number">{{
                                    candidature.num_dossier
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Concours</span>
                                <span class="info-value">{{
                                    truncateText(candidature.concours, 40)
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date soumission</span>
                                <span class="info-value">{{
                                    candidature.date
                                }}</span>
                            </div>
                            <!-- ⭐ Spécialité - affichée sous motif si le concours a des spécialités -->
                            <div
                                v-if="candidature.has_specialites"
                                class="info-item"
                            >
                                <span class="info-label">Spécialité</span>
                                <span class="info-value">{{
                                    candidature.specialite || "Non spécifiée"
                                }}</span>
                            </div>
                            <div v-if="candidature.motif" class="info-item">
                                <span class="info-label">Motif</span>
                                <span class="info-value motif">{{
                                    candidature.motif
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Carte Documents fournis -->
                    <div class="docs-card sakai-card">
                        <div class="card-title">
                            <i class="pi pi-file-pdf"></i>
                            <span>Documents fournis</span>
                            <span class="docs-count"
                                >{{ fichiers.length }} fichier(s)</span
                            >
                        </div>
                        <div v-if="fichiers.length > 0" class="docs-list">
                            <div
                                v-for="fichier in fichiers"
                                :key="fichier.id"
                                class="doc-item"
                            >
                                <div class="doc-info">
                                    <i class="pi pi-file-pdf doc-icon"></i>
                                    <div class="doc-details">
                                        <span class="doc-name">{{
                                            truncateText(
                                                fichier.nom_fichier,
                                                30,
                                            )
                                        }}</span>
                                        <span class="doc-type">{{
                                            fichier.type
                                        }}</span>
                                    </div>
                                </div>
                                <a
                                    :href="`/storage/${fichier.url_fichier}`"
                                    target="_blank"
                                    class="doc-download"
                                    v-tooltip.top="'Télécharger'"
                                >
                                    <i class="pi pi-download"></i>
                                </a>
                            </div>
                        </div>
                        <div v-else class="empty-docs">
                            <i class="pi pi-folder-open"></i>
                            <p>Aucun document disponible</p>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite - Profil -->
                <div class="details-right">
                    <div class="profile-card sakai-card">
                        <div class="card-title">
                            <i class="pi pi-user"></i>
                            <span>Mon profil</span>
                        </div>

                        <!-- Avatar et nom -->
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="pi pi-user"></i>
                            </div>
                            <div class="profile-name">
                                <div class="name">
                                    {{ user.name }} {{ user.prenom }}
                                </div>
                                <div class="email">{{ user.email }}</div>
                            </div>
                        </div>

                        <!-- Informations personnelles -->
                        <div class="profile-info">
                            <div class="info-item">
                                <span class="info-label">Téléphone</span>
                                <span class="info-value">{{
                                    profil.telephone || "Non renseigné"
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Date naissance</span>
                                <span class="info-value">{{
                                    profil.date_naissance || "Non renseignée"
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Lieu naissance</span>
                                <span class="info-value">{{
                                    profil.lieu_naissance || "Non renseigné"
                                }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Région</span>
                                <span class="info-value">{{
                                    profil.region || "Non renseignée"
                                }}</span>
                            </div>
                        </div>

                        <Divider class="divider" />

                        <!-- Permis -->
                        <div class="info-item permis-item">
                            <span class="info-label">Permis de conduire</span>
                            <Tag
                                :severity="
                                    profil.permis ? 'success' : 'secondary'
                                "
                                :value="profil.permis ? 'Oui' : 'Non'"
                                size="small"
                            />
                        </div>

                        <Divider class="divider" />

                        <!-- Diplômes -->
                        <div class="diplomes-section">
                            <span class="info-label">Diplômes renseignés</span>
                            <div
                                v-if="diplomesPossedes.length > 0"
                                class="diplomes-list"
                            >
                                <Tag
                                    v-for="diplome in diplomesPossedes"
                                    :key="diplome.nom"
                                    severity="info"
                                    :value="diplome.nom"
                                    size="small"
                                />
                            </div>
                            <div v-else class="empty-diplomes">
                                Aucun diplôme renseigné
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dialogue des fichiers -->
        <Dialog
            v-model:visible="showFilesDialog"
            header="Documents de la candidature"
            :modal="true"
            :style="{ width: '90vw', maxWidth: '500px' }"
        >
            <div class="dialog-files-list">
                <div
                    v-for="file in selectedFiles"
                    :key="file.id"
                    class="dialog-file-item"
                >
                    <div class="dialog-file-info">
                        <i class="pi pi-file-pdf"></i>
                        <span class="doc-name">{{ file.type }}</span>
                    </div>
                    <a :href="`/storage/${file.url_fichier}`" target="_blank">
                        <i class="pi pi-download"></i>
                    </a>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Fermer"
                    icon="pi pi-times"
                    @click="showFilesDialog = false"
                    class="p-button-text"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Container principal */
.detail-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1rem;
}

@media (min-width: 768px) {
    .detail-container {
        padding: 1.5rem;
    }
}

/* Carte Sakai */
.sakai-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    overflow: hidden;
}

/* Carte en-tête */
.header-card {
    padding: 1.5rem;
}

.header-icon {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-icon i {
    color: white;
    font-size: 1.75rem;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-color);
}

@media (min-width: 768px) {
    .header-title {
        font-size: 1.75rem;
    }
}

.header-subtitle {
    font-size: 0.875rem;
    color: var(--text-color-secondary);
    margin: 0.25rem 0 0 0;
}

/* Boutons */
.back-btn,
.print-btn {
    white-space: nowrap;
}

@media (max-width: 640px) {
    .back-btn span,
    .print-btn span {
        display: none;
    }

    .back-btn .pi,
    .print-btn .pi {
        margin-right: 0;
    }

    .back-btn,
    .print-btn {
        padding: 0.5rem;
    }
}

/* Grille responsive */
.details-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

@media (min-width: 992px) {
    .details-grid {
        display: grid;
        grid-template-columns: 1fr 0.8fr;
        gap: 1.5rem;
    }
}

.details-left {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Cartes */
.info-card,
.docs-card,
.profile-card {
    overflow: hidden;
}

.card-header-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--surface-50);
    border-bottom: 1px solid var(--surface-border);
    flex-wrap: wrap;
    gap: 0.5rem;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    font-weight: 600;
    color: var(--text-color);
    border-bottom: 1px solid var(--surface-border);
}

.card-title i {
    color: #10b981;
    font-size: 1.1rem;
}

.card-title span {
    flex: 1;
}

.docs-count {
    font-size: 0.7rem;
    font-weight: normal;
    color: var(--text-color-secondary);
    background: var(--surface-100);
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
}

/* Liste d'informations */
.info-list {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    gap: 0.5rem;
}

.info-label {
    width: 110px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-color-secondary);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}

.info-value {
    flex: 1;
    font-size: 0.875rem;
    color: var(--text-color);
    word-break: break-word;
}

.dossier-number {
    font-family: monospace;
    font-weight: 700;
    color: #10b981;
}

.motif {
    font-style: italic;
    color: var(--text-color-secondary);
}

/* Documents */
.docs-list {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.doc-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--surface-50);
    border-radius: 12px;
    transition: all 0.2s;
}

.doc-item:hover {
    background: var(--surface-100);
}

.doc-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.doc-icon {
    color: #ef4444;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.doc-details {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.doc-name {
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--text-color);
    word-break: break-word;
}

.doc-type {
    font-size: 0.65rem;
    color: var(--text-color-secondary);
}

.doc-download {
    color: #10b981;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.2s;
    flex-shrink: 0;
}

.doc-download:hover {
    background: rgba(16, 185, 129, 0.1);
}

.empty-docs {
    text-align: center;
    padding: 2rem;
    color: var(--text-color-secondary);
}

.empty-docs i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-docs p {
    margin: 0;
    font-size: 0.875rem;
}

/* Profil */
.profile-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--surface-border);
}

.profile-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.profile-avatar i {
    color: white;
    font-size: 1.75rem;
}

.profile-name {
    min-width: 0;
    flex: 1;
}

.name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-color);
    word-break: break-word;
}

.email {
    font-size: 0.75rem;
    color: var(--text-color-secondary);
    word-break: break-word;
}

.profile-info {
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.divider {
    margin: 0;
}

.permis-item {
    padding: 0.75rem 1rem;
}

.diplomes-section {
    padding: 1rem;
}

.diplomes-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.empty-diplomes {
    font-size: 0.8rem;
    color: var(--text-color-secondary);
    font-style: italic;
    margin-top: 0.5rem;
}

/* Badge statut */
.status-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.75rem;
    white-space: nowrap;
}

/* Dialogue fichiers */
.dialog-files-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.dialog-file-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--surface-50);
    border-radius: 8px;
}

.dialog-file-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.dialog-file-info i {
    color: #ef4444;
    flex-shrink: 0;
}

.dialog-file-info span {
    font-size: 0.8rem;
    word-break: break-word;
    flex: 1;
}

.dialog-file-item a {
    color: #10b981;
    flex-shrink: 0;
    padding: 0.5rem;
}

/* Responsive fine */
@media (max-width: 480px) {
    .header-card {
        padding: 1rem;
    }

    .header-icon {
        width: 44px;
        height: 44px;
    }

    .header-icon i {
        font-size: 1.25rem;
    }

    .header-title {
        font-size: 1.25rem;
    }

    .info-label {
        width: 100%;
        margin-bottom: 0.25rem;
    }

    .info-item {
        flex-direction: column;
    }

    .info-value {
        width: 100%;
    }

    .card-header-info {
        flex-direction: column;
        align-items: flex-start;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
    }

    .profile-name {
        text-align: center;
    }

    .doc-item {
        flex-wrap: wrap;
    }

    .doc-download {
        margin-top: 0.5rem;
    }
}
</style>
