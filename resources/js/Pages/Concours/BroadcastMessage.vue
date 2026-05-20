<script setup>
import { ref, computed } from "vue";
import { useForm, Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Select from "primevue/select";
import Card from "primevue/card";
import Divider from "primevue/divider";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Toast from "primevue/toast";
import Dialog from "primevue/dialog";
import ProgressSpinner from "primevue/progressspinner";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import axios from "axios";

const props = defineProps({
    concours: Array,
    broadcasts: Object,
    flash: Object,
    user_role: String,
    is_superadmin: Boolean,
    is_gerant: Boolean,
    is_admin: Boolean,
});

const toast = useToast();

const form = useForm({
    concour_id: null,
    subject: "",
    message: "",
});

const previewData = ref(null);
const showPreview = ref(false);
const showConfirmDialog = ref(false);
const sending = ref(false);

const selectedConcours = computed(() => {
    return props.concours.find((c) => c.id === form.concour_id);
});

const formatDate = (date) => {
    return new Date(date).toLocaleString("fr-FR");
};

const previewMessage = async () => {
    if (!form.concour_id) {
        toast.add({
            severity: "warn",
            summary: "Sélection requise",
            detail: "Veuillez sélectionner un concours",
            life: 3000,
        });
        return;
    }

    if (!form.message || form.message.trim().length < 3) {
        toast.add({
            severity: "warn",
            summary: "Message vide",
            detail: "Veuillez écrire un message",
            life: 3000,
        });
        return;
    }

    try {
        const response = await axios.post(route("broadcast.preview"), {
            concour_id: form.concour_id,
            subject: form.subject,
            message: form.message,
        });
        previewData.value = response.data;
        showPreview.value = true;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Erreur lors de la prévisualisation",
            life: 4000,
        });
    }
};

const confirmSend = () => {
    if (!form.concour_id || !form.message) return;
    showConfirmDialog.value = true;
};

const sendBroadcast = () => {
    sending.value = true;
    showConfirmDialog.value = false;

    form.post(route("broadcast.send"), {
        preserveScroll: true,
        onSuccess: () => {
            sending.value = false;
            form.reset();
            showPreview.value = false;

            toast.add({
                severity: "success",
                summary: "Diffusion envoyée !",
                detail: "Message envoyé avec succès",
                life: 5000,
                closable: true,
            });

            setTimeout(() => {
                router.reload({ only: ["broadcasts"] });
            }, 1500);
        },
        onError: (errors) => {
            sending.value = false;
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: errors.message || "Erreur lors de l'envoi",
                life: 5000,
            });
        },
    });
};

const resetForm = () => {
    form.reset();
    showPreview.value = false;
    previewData.value = null;
};
</script>

<template>
    <AppLayout>
        <Head title="Diffusion de messages" />
        <Toast position="top-right" />

        <div class="broadcast-page">
            <!-- En-tête -->
            <div class="page-header-card">
                <div class="page-header-content">
                    <i class="pi pi-megaphone page-header-icon"></i>
                    <div class="page-header-text">
                        <h1 class="page-header-title">Diffusion de messages</h1>
                        <p class="page-header-subtitle">
                            Envoyez un message à tous les candidats d'un
                            concours spécifique
                        </p>
                    </div>
                </div>
            </div>

            <!-- Messages d'information selon le rôle -->
            <div v-if="is_admin" class="info-message mb-4">
                <Message severity="info" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-info-circle"></i>
                        <span
                            >Vous pouvez envoyer des messages uniquement aux
                            concours où vous êtes assigné.</span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_gerant" class="info-message mb-4">
                <Message severity="success" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-check-circle"></i>
                        <span
                            >Vous pouvez envoyer des messages à tous les
                            concours de votre service.</span
                        >
                    </div>
                </Message>
            </div>

            <div class="broadcast-grid">
                <!-- Formulaire d'envoi -->
                <div class="broadcast-form-col">
                    <Card class="broadcast-card">
                        <template #title>
                            <div class="card-title-wrapper">
                                <i class="pi pi-send card-title-icon"></i>
                                <span class="card-title-text"
                                    >Nouvelle diffusion</span
                                >
                            </div>
                        </template>
                        <template #content>
                            <div class="form-container">
                                <div class="form-field">
                                    <label class="form-label">
                                        Concours
                                        <span class="required-star">*</span>
                                    </label>
                                    <Select
                                        v-model="form.concour_id"
                                        :options="concours"
                                        optionLabel="intitule"
                                        optionValue="id"
                                        placeholder="Choisir un concours..."
                                        :class="{
                                            'p-invalid': form.errors.concour_id,
                                        }"
                                        class="w-full"
                                    />
                                    <small
                                        class="p-error"
                                        v-if="form.errors.concour_id"
                                    >
                                        {{ form.errors.concour_id }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label class="form-label"
                                        >Objet (optionnel)</label
                                    >
                                    <InputText
                                        v-model="form.subject"
                                        placeholder="Ex: Information importante concernant le concours"
                                        class="w-full"
                                    />
                                </div>

                                <div class="form-field">
                                    <label class="form-label">
                                        Message
                                        <span class="required-star">*</span>
                                    </label>
                                    <Textarea
                                        v-model="form.message"
                                        rows="6"
                                        placeholder="Écrivez votre message ici..."
                                        :class="{
                                            'p-invalid': form.errors.message,
                                        }"
                                        class="w-full"
                                    />
                                    <small class="form-hint">
                                        <i class="pi pi-info-circle"></i>
                                        Ce message sera envoyé à tous les
                                        candidats de ce concours
                                    </small>
                                    <small
                                        class="p-error"
                                        v-if="form.errors.message"
                                    >
                                        {{ form.errors.message }}
                                    </small>
                                </div>

                                <div class="form-actions">
                                    <Button
                                        label="Prévisualiser"
                                        icon="pi pi-eye"
                                        class="p-button-outlined preview-button"
                                        @click="previewMessage"
                                        :disabled="
                                            !form.concour_id || !form.message
                                        "
                                    />
                                    <!-- ⭐ Bouton Envoyer - Dynamique -->
                                    <Button
                                        label="Envoyer"
                                        icon="pi pi-send"
                                        class="btn-send send-button"
                                        @click="confirmSend"
                                        :disabled="
                                            !form.concour_id ||
                                            !form.message ||
                                            sending
                                        "
                                        :loading="sending"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- ⭐ Prévisualisation - sans nombre de candidats -->
                <div class="broadcast-preview-col" v-if="showPreview">
                    <Card class="broadcast-card preview-card-animate">
                        <template #title>
                            <div class="card-title-wrapper">
                                <i class="pi pi-eye card-title-icon"></i>
                                <span class="card-title-text"
                                    >Prévisualisation</span
                                >
                            </div>
                        </template>
                        <template #content>
                            <div v-if="previewData">
                                <div class="preview-info-box">
                                    <div class="preview-info-row">
                                        <span class="preview-info-label"
                                            >Concours :</span
                                        >
                                        <span class="preview-info-value">{{
                                            previewData.concour
                                        }}</span>
                                    </div>
                                </div>

                                <Divider class="my-3" />

                                <div class="preview-message-box">
                                    <div class="preview-message-subject">
                                        {{
                                            previewData.subject || "Sans objet"
                                        }}
                                    </div>
                                    <div class="preview-message-content">
                                        {{ previewData.message }}
                                    </div>
                                </div>

                                <Divider class="my-3" />

                                <div class="preview-actions">
                                    <Button
                                        label="Modifier"
                                        icon="pi pi-pencil"
                                        class="p-button-text"
                                        @click="showPreview = false"
                                    />
                                    <Button
                                        label="Confirmer l'envoi"
                                        icon="pi pi-check"
                                        class="p-button-success"
                                        @click="confirmSend"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Historique des diffusions -->
                <div class="broadcast-history-col">
                    <Card class="broadcast-card">
                        <template #title>
                            <div class="card-title-wrapper">
                                <i class="pi pi-history card-title-icon"></i>
                                <span class="card-title-text"
                                    >Historique des diffusions</span
                                >
                            </div>
                        </template>
                        <template #content>
                            <!-- Version Desktop -->
                            <div class="history-desktop">
                                <DataTable
                                    :value="broadcasts.data"
                                    :paginator="true"
                                    :rows="10"
                                    class="broadcast-table"
                                    responsiveLayout="scroll"
                                >
                                    <Column
                                        field="created_at"
                                        header="Date"
                                        :sortable="true"
                                    >
                                        <template #body="{ data }">
                                            <span class="date-cell">{{
                                                formatDate(data.created_at)
                                            }}</span>
                                        </template>
                                    </Column>
                                    <Column
                                        field="concour.intitule"
                                        header="Concours"
                                        :sortable="true"
                                    >
                                        <template #body="{ data }">
                                            <span class="concour-cell">{{
                                                data.concour?.intitule || "-"
                                            }}</span>
                                        </template>
                                    </Column>
                                    <Column
                                        field="broadcast_subject"
                                        header="Objet"
                                    >
                                        <template #body="{ data }">
                                            <span class="subject-cell">{{
                                                data.broadcast_subject ||
                                                data.objet ||
                                                "-"
                                            }}</span>
                                        </template>
                                    </Column>
                                    <Column field="texte" header="Message">
                                        <template #body="{ data }">
                                            <span class="message-cell"
                                                >{{
                                                    data.texte.substring(0, 50)
                                                }}...</span
                                            >
                                        </template>
                                    </Column>
                                    <Column
                                        field="emetteur.name"
                                        header="Envoyé par"
                                    />
                                    <Column header="Statut">
                                        <template #body>
                                            <Tag
                                                value="Envoyé"
                                                severity="success"
                                                icon="pi pi-check"
                                            />
                                        </template>
                                    </Column>
                                </DataTable>
                            </div>

                            <!-- Version Mobile -->
                            <div class="history-mobile">
                                <div
                                    v-if="broadcasts.data?.length === 0"
                                    class="empty-history"
                                >
                                    <i class="pi pi-inbox"></i>
                                    <p>Aucune diffusion effectuée</p>
                                </div>
                                <div
                                    v-for="item in broadcasts.data"
                                    :key="item.id"
                                    class="history-card-item"
                                >
                                    <div class="history-card-header">
                                        <div class="history-card-date">
                                            <i class="pi pi-calendar"></i>
                                            <span>{{
                                                formatDate(item.created_at)
                                            }}</span>
                                        </div>
                                        <Tag
                                            value="Envoyé"
                                            severity="success"
                                            size="small"
                                        />
                                    </div>
                                    <div class="history-card-body">
                                        <div class="history-card-row">
                                            <span class="history-card-label"
                                                >Concours :</span
                                            >
                                            <span class="history-card-value">{{
                                                item.concour?.intitule || "-"
                                            }}</span>
                                        </div>
                                        <div class="history-card-row">
                                            <span class="history-card-label"
                                                >Objet :</span
                                            >
                                            <span class="history-card-value">{{
                                                item.broadcast_subject ||
                                                item.objet ||
                                                "-"
                                            }}</span>
                                        </div>
                                        <div class="history-card-row">
                                            <span class="history-card-label"
                                                >Message :</span
                                            >
                                            <span
                                                class="history-card-value message-preview-mobile"
                                                >{{ item.texte }}</span
                                            >
                                        </div>
                                        <div class="history-card-row">
                                            <span class="history-card-label"
                                                >Envoyé par :</span
                                            >
                                            <span class="history-card-value">{{
                                                item.emetteur?.name || "-"
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="history-pagination-mobile"
                                    v-if="broadcasts.last_page > 1"
                                >
                                    <!-- ⭐ Bouton Page précédente - Dynamique -->
                                    <Button
                                        icon="pi pi-chevron-left"
                                        class="pagination-nav-btn"
                                        :disabled="!broadcasts.prev_page_url"
                                        @click="
                                            goToPage(
                                                broadcasts.current_page - 1,
                                            )
                                        "
                                    />
                                    <span class="pagination-info">
                                        Page {{ broadcasts.current_page }} /
                                        {{ broadcasts.last_page }}
                                    </span>
                                    <!-- ⭐ Bouton Page suivante - Dynamique -->
                                    <Button
                                        icon="pi pi-chevron-right"
                                        class="pagination-nav-btn"
                                        :disabled="!broadcasts.next_page_url"
                                        @click="
                                            goToPage(
                                                broadcasts.current_page + 1,
                                            )
                                        "
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>
        </div>

        <!-- ⭐ Modal de confirmation - sans nombre de candidats -->
        <Dialog
            v-model:visible="showConfirmDialog"
            header="Confirmation d'envoi"
            :modal="true"
            :closable="true"
            class="confirm-dialog"
            :style="{ width: '450px' }"
            :breakpoints="{ '960px': '90vw' }"
        >
            <div class="confirm-content">
                <div class="confirm-icon">
                    <i class="pi pi-question-circle"></i>
                </div>
                <div class="confirm-text">
                    <p>
                        Vous êtes sur le point d'envoyer ce message à tous les
                        candidats du concours :
                    </p>
                    <div class="confirm-details">
                        <strong>{{
                            previewData?.concour || "ce concours"
                        }}</strong>
                    </div>
                    <p class="confirm-warning">
                        Cette action est irréversible.
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="dialog-footer">
                    <Button
                        label="Annuler"
                        icon="pi pi-times"
                        class="p-button-text"
                        @click="showConfirmDialog = false"
                    />
                    <Button
                        label="Confirmer l'envoi"
                        icon="pi pi-check"
                        class="p-button-success"
                        :loading="sending"
                        @click="sendBroadcast"
                    />
                </div>
            </template>
        </Dialog>

        <!-- Loader -->
        <div v-if="sending" class="loader-overlay">
            <ProgressSpinner />
            <p>Envoi en cours...</p>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ============================================ */
/* ⭐ CONTAINER */
/* ============================================ */
.broadcast-page {
    padding: 1rem;
}

/* ============================================ */
/* ⭐ EN-TÊTE - DYNAMIQUE */
/* ============================================ */
.page-header-card {
    background: linear-gradient(
        135deg,
        var(--color-primary) 0%,
        var(--color-primary-dark) 100%
    );
    border-radius: 1rem;
    margin-bottom: 1.5rem;
    padding: 1.25rem 1.5rem;
    color: white;
}

.page-header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-header-icon {
    font-size: 2rem;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.75rem;
    border-radius: 1rem;
}

.page-header-text {
    flex: 1;
}

.page-header-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.page-header-subtitle {
    font-size: 0.8rem;
    margin: 0.25rem 0 0;
    opacity: 0.9;
}

/* ============================================ */
/* ⭐ MESSAGES */
/* ============================================ */
.info-message {
    margin-bottom: 1rem;
}

/* ============================================ */
/* ⭐ GRILLE */
/* ============================================ */
.broadcast-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.broadcast-form-col,
.broadcast-preview-col,
.broadcast-history-col {
    width: 100%;
}

/* ============================================ */
/* ⭐ CARTES */
/* ============================================ */
.broadcast-card {
    border-radius: 1rem;
    overflow: hidden;
}

.card-title-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* ⭐ Icône titre carte - Dynamique */
.card-title-icon {
    color: var(--color-primary);
    font-size: 1.2rem;
}

.card-title-text {
    font-weight: 600;
    font-size: 1rem;
}

/* ============================================ */
/* ⭐ FORMULAIRE */
/* ============================================ */
.form-container {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text-color);
}

.required-star {
    color: #ef4444;
}

.form-hint {
    font-size: 0.7rem;
    color: var(--text-color-secondary);
}

.form-hint i {
    margin-right: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 0.5rem;
}

/* ============================================ */
/* ⭐ PRÉVISUALISATION - DYNAMIQUE */
/* ============================================ */
.preview-info-box {
    background: var(--surface-50);
    padding: 1rem;
    border-radius: 0.75rem;
}

.preview-info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.preview-info-row:last-child {
    margin-bottom: 0;
}

.preview-info-label {
    font-weight: 600;
    color: var(--text-color-secondary);
    font-size: 0.8rem;
}

.preview-info-value {
    font-weight: 500;
    font-size: 0.85rem;
}

.preview-info-highlight {
    color: var(--color-primary);
    font-weight: 700;
}

.preview-message-box {
    background: var(--surface-50);
    padding: 1rem;
    border-radius: 0.75rem;
}

.preview-message-subject {
    font-weight: 700;
    color: var(--color-primary);
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--surface-border);
    font-size: 0.85rem;
}

.preview-message-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    line-height: 1.5;
    font-size: 0.85rem;
}

.preview-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.preview-card-animate {
    animation: fadeIn 0.3s ease;
}

/* ============================================ */
/* ⭐ HISTORIQUE */
/* ============================================ */
.history-desktop {
    display: block;
}

.history-mobile {
    display: none;
}

.broadcast-table {
    width: 100%;
}

.date-cell {
    font-size: 0.8rem;
    white-space: nowrap;
}

.concour-cell,
.subject-cell,
.message-cell {
    display: inline-block;
    max-width: 180px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ============================================ */
/* ⭐ HISTORIQUE MOBILE */
/* ============================================ */
.history-card-item {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 0.75rem;
    margin-bottom: 0.75rem;
    overflow: hidden;
}

.history-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--color-primary-light);
    border-bottom: 1px solid var(--surface-border);
}

.history-card-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.7rem;
    color: var(--color-primary-dark);
}

.history-card-body {
    padding: 0.75rem;
}

.history-card-row {
    display: flex;
    margin-bottom: 0.5rem;
    font-size: 0.8rem;
}

.history-card-row:last-child {
    margin-bottom: 0;
}

.history-card-label {
    width: 80px;
    font-weight: 600;
    color: var(--text-color-secondary);
    flex-shrink: 0;
}

.history-card-value {
    flex: 1;
    word-break: break-word;
}

.message-preview-mobile {
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.4;
}

.history-pagination-mobile {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding: 0.5rem;
    font-size: 0.8rem;
}

.empty-history {
    text-align: center;
    padding: 2rem;
    color: var(--text-color-secondary);
}

.empty-history i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* ============================================ */
/* ⭐ MODAL CONFIRMATION - DYNAMIQUE */
/* ============================================ */
.confirm-dialog :deep(.p-dialog-header) {
    background: linear-gradient(
        135deg,
        var(--color-primary-light) 0%,
        #fff 100%
    );
}

.confirm-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
}

.confirm-icon {
    font-size: 2.5rem;
    color: var(--color-primary);
}

.confirm-text {
    flex: 1;
}

.confirm-text p {
    margin: 0 0 0.5rem;
    font-size: 0.9rem;
}

.confirm-details {
    background: var(--color-primary-light);
    padding: 0.75rem;
    border-radius: 0.5rem;
    margin: 0.5rem 0;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.confirm-badge {
    background: var(--color-primary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    font-size: 0.7rem;
}

.confirm-warning {
    color: #dc2626;
    font-size: 0.75rem;
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* ============================================ */
/* ⭐ LOADER */
/* ============================================ */
.loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(4px);
}

.loader-overlay p {
    margin-top: 1rem;
    color: white;
    font-weight: 500;
}

/* ============================================ */
/* ⭐ FOCUS & BOUTONS GLOBAL */
/* ============================================ */
:deep(.p-inputtext:focus),
:deep(.p-select:focus),
:deep(.p-dropdown:focus),
:deep(.p-textarea:focus),
:deep(.p-editor:focus) {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

:deep(.p-button.p-button-success) {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

:deep(.p-button.p-button-success:hover) {
    background: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
}

:deep(.p-button.p-button-outlined) {
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

:deep(.p-button.p-button-outlined:hover) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

:deep(.p-button.p-button-text) {
    color: var(--color-primary) !important;
}

:deep(.p-button.p-button-text:hover) {
    background: var(--color-primary-light) !important;
}

/* ⭐ Checkbox */
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    border-color: var(--color-primary) !important;
    background: var(--color-primary) !important;
}

/* ⭐ Dropdown highlight */
:deep(.p-dropdown-panel .p-dropdown-items .p-dropdown-item.p-highlight) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Tags */
:deep(.p-tag.p-tag-success) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Badges */
:deep(.p-badge.p-badge-success) {
    background: var(--color-primary) !important;
}

/* ⭐ Datatable */
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--color-primary-light) !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    border-bottom: 2px solid var(--color-primary-light);
}

/* ⭐ Pagination */
:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

/* ============================================ */
/* ⭐ ANIMATIONS */
/* ============================================ */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.page-header-card,
.broadcast-card {
    animation: fadeIn 0.4s ease-out;
}

/* ============================================ */
/* ⭐ DARK MODE */
/* ============================================ */
.dark .confirm-dialog :deep(.p-dialog-header) {
    background: linear-gradient(
        135deg,
        rgba(16, 185, 129, 0.15) 0%,
        #1f2937 100%
    );
}

.dark .history-card-header {
    background: rgba(16, 185, 129, 0.1);
}

.dark .confirm-details {
    background: rgba(16, 185, 129, 0.1);
}

/* ============================================ */
/* ⭐ RESPONSIVE */
/* ============================================ */
@media (min-width: 768px) {
    .broadcast-page {
        padding: 1.5rem;
    }

    .page-header-card {
        padding: 1.5rem 2rem;
    }

    .page-header-title {
        font-size: 1.5rem;
    }

    .page-header-subtitle {
        font-size: 0.9rem;
    }

    .broadcast-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    .broadcast-history-col {
        grid-column: span 2;
    }
}

@media (min-width: 1024px) {
    .broadcast-grid {
        grid-template-columns: 1fr 1fr;
    }

    .broadcast-history-col {
        grid-column: span 2;
    }
}

@media (max-width: 767px) {
    .history-desktop {
        display: none;
    }

    .history-mobile {
        display: block;
    }

    .form-actions {
        flex-direction: column;
    }

    .preview-button,
    .send-button {
        width: 100%;
    }

    .preview-info-row {
        flex-direction: column;
        gap: 0.25rem;
    }

    .confirm-content {
        flex-direction: column;
        text-align: center;
    }

    .confirm-details {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .page-header-card {
        padding: 1rem;
    }

    .page-header-icon {
        font-size: 1.5rem;
        padding: 0.5rem;
    }

    .page-header-title {
        font-size: 1rem;
    }

    .page-header-subtitle {
        font-size: 0.7rem;
    }

    .history-card-label {
        width: 70px;
        font-size: 0.75rem;
    }

    .history-card-value {
        font-size: 0.75rem;
    }
}
/* ============================================ */
/* ⭐ PAGINATION MOBILE - DYNAMIQUE */
/* ============================================ */
.history-pagination-mobile {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    padding: 0.5rem;
    font-size: 0.8rem;
}

.pagination-info {
    font-weight: 500;
    color: var(--color-primary-dark);
    min-width: 80px;
    text-align: center;
}

/* ⭐ Boutons de navigation pagination */
.pagination-nav-btn {
    color: var(--color-primary) !important;
    background: transparent !important;
    border: 1px solid var(--color-primary-light) !important;
    border-radius: 0.5rem !important;
    width: 2.5rem !important;
    height: 2.5rem !important;
    transition: all 0.2s ease;
}

.pagination-nav-btn:hover:not(:disabled) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    border-color: var(--color-primary) !important;
}

.pagination-nav-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Dark mode */
.dark .pagination-info {
    color: var(--color-primary-light);
}

.dark .pagination-nav-btn {
    border-color: rgba(16, 185, 129, 0.2) !important;
    color: var(--color-primary-light) !important;
}

.dark .pagination-nav-btn:hover:not(:disabled) {
    background: rgba(16, 185, 129, 0.15) !important;
    color: var(--color-primary) !important;
}
/* ⭐ Bouton Envoyer - Dynamique */
.btn-send {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    font-weight: 600 !important;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-send:hover:not(:disabled) {
    background: var(--color-primary-dark) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-send:focus {
    box-shadow: 0 0 0 3px var(--color-primary-light) !important;
}

.btn-send:disabled {
    background: #9ca3af !important;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
    opacity: 0.7;
}

/* Version mobile */
@media (max-width: 767px) {
    .btn-send {
        width: 100%;
        justify-content: center;
    }
}
</style>
