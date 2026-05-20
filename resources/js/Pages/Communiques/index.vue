<script setup>
import { ref, nextTick } from "vue";
import { router, Head } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Select from "primevue/select";
import Button from "primevue/button";
import Card from "primevue/card";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Calendar from "primevue/calendar";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import InputSwitch from "primevue/inputswitch";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";
import ConfirmDialog from "primevue/confirmdialog";

const props = defineProps({
    concours: Array,
    communiques: Array,
});

const toast = useToast();
const confirm = useConfirm();

const dialogVisible = ref(false);
const isEditing = ref(false);
const fileInput = ref(null);
const selectedFileName = ref("");

const form = ref({
    id: null,
    concour_id: null,
    titre: "",
    contenu: "",
    fichier: null,
    is_active: false,
    date_limite: null,
});

const resetForm = () => {
    form.value = {
        id: null,
        concour_id: null,
        titre: "",
        contenu: "",
        fichier: null,
        is_active: false,
        date_limite: null,
    };
    selectedFileName.value = "";
    isEditing.value = false;
    if (fileInput.value) fileInput.value.value = "";
};

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            toast.add({
                severity: "error",
                summary: "Fichier trop volumineux",
                detail: "Le fichier ne doit pas dépasser 5 Mo",
                life: 3000,
            });
            return;
        }
        form.value.fichier = file;
        selectedFileName.value = file.name;
    }
};

const removeFile = () => {
    form.value.fichier = null;
    selectedFileName.value = "";
    if (fileInput.value) fileInput.value.value = "";
};

const downloadFile = (url, nom) => {
    if (url) {
        const link = document.createElement("a");
        link.href = url;
        link.download = nom || "fichier";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
};

const openCreateDialog = () => {
    resetForm();
    dialogVisible.value = true;
};

const openEditDialog = (communique) => {
    let dateLimite = null;
    if (communique.date_limite) {
        const parts = communique.date_limite.split("/");
        if (parts.length === 3)
            dateLimite = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`);
    }
    form.value = {
        id: communique.id,
        concour_id: communique.concour_id,
        titre: communique.titre,
        contenu: communique.contenu,
        fichier: null,
        is_active: communique.is_active === true || communique.is_active === 1,
        date_limite: dateLimite,
    };
    selectedFileName.value = "";
    isEditing.value = true;
    dialogVisible.value = true;
};

const saveCommunique = () => {
    if (!form.value.concour_id) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez sélectionner un concours",
            life: 3000,
        });
        return;
    }
    if (!form.value.titre) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez saisir un titre",
            life: 3000,
        });
        return;
    }
    if (!form.value.contenu) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez saisir le contenu",
            life: 3000,
        });
        return;
    }

    const data = new FormData();
    data.append("concour_id", form.value.concour_id);
    data.append("titre", form.value.titre);
    data.append("contenu", form.value.contenu);
    data.append("is_active", form.value.is_active ? "1" : "0");
    data.append(
        "date_limite",
        form.value.date_limite
            ? new Date(form.value.date_limite).toISOString().split("T")[0]
            : "",
    );
    if (form.value.fichier) data.append("fichier", form.value.fichier);

    if (form.value.id) {
        // ⭐ Mise à jour
        router.put(route("communiques.update", form.value.id), data, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                dialogVisible.value = false;
                resetForm();
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Communiqué mis à jour",
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: Object.values(errors).join(", "),
                    life: 3000,
                });
            },
        });
    } else {
        // ⭐ Création
        router.post(route("communiques.store"), data, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                dialogVisible.value = false;
                resetForm();
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Communiqué créé",
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: Object.values(errors).join(", "),
                    life: 3000,
                });
            },
        });
    }
};

const publishCommunique = (communique) => {
    confirm.require({
        message: "Voulez-vous publier ce communiqué ?",
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Publier",
        rejectLabel: "Annuler",
        acceptClass: "p-button-success",
        accept: () =>
            router.patch(
                route("communiques.publish", communique.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () =>
                        toast.add({
                            severity: "success",
                            summary: "Publié",
                            life: 3000,
                        }),
                },
            ),
    });
};

const unpublishCommunique = (communique) => {
    confirm.require({
        message: "Voulez-vous dépublier ce communiqué ?",
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Dépublier",
        rejectLabel: "Annuler",
        acceptClass: "p-button-warning",
        accept: () =>
            router.patch(
                route("communiques.unpublish", communique.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () =>
                        toast.add({
                            severity: "info",
                            summary: "Dépublié",
                            life: 3000,
                        }),
                },
            ),
    });
};

const deleteCommunique = (communique) => {
    confirm.require({
        message: "Voulez-vous supprimer définitivement ce communiqué ?",
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Supprimer",
        rejectLabel: "Annuler",
        acceptClass: "p-button-danger",
        accept: () =>
            router.delete(route("communiques.destroy", communique.id), {
                preserveScroll: true,
                onSuccess: () =>
                    toast.add({
                        severity: "success",
                        summary: "Supprimé",
                        life: 3000,
                    }),
            }),
    });
};

const getStatusBadge = (isActive) =>
    isActive
        ? { severity: "success", value: "Publié" }
        : { severity: "secondary", value: "Brouillon" };

const isDateExpired = (dateLimite) => {
    if (!dateLimite) return false;
    const parts = dateLimite.split("/");
    if (parts.length !== 3) return false;
    return new Date(`${parts[2]}-${parts[1]}-${parts[0]}`) < new Date();
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des communiqués" />
        <ConfirmDialog />
        <Toast />

        <div class="page-container">
            <div class="header-section">
                <Card class="header-card">
                    <template #content>
                        <div class="header-content">
                            <div class="header-icon">
                                <i class="pi pi-megaphone"></i>
                            </div>
                            <div class="header-text">
                                <h1>Gestion des communiqués</h1>
                                <p>
                                    <i class="pi pi-info-circle"></i> Créez et
                                    gérez les communiqués pour informer les
                                    candidats
                                </p>
                            </div>
                            <Button
                                label="Nouveau communiqué"
                                icon="pi pi-plus"
                                @click="openCreateDialog"
                                class="new-btn"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <div class="desktop-view">
                <Card class="table-card">
                    <template #title>
                        <div class="table-title">
                            <i class="pi pi-list"></i
                            ><span>Liste des communiqués</span
                            ><span class="total-badge"
                                >{{ communiques.length }} communiqué(s)</span
                            >
                        </div>
                    </template>
                    <template #content>
                        <DataTable
                            :value="communiques"
                            stripedRows
                            showGridlines
                            class="p-datatable-sm"
                        >
                            <Column
                                field="concour_intitule"
                                header="Concours"
                                sortable
                                ><template #body="sp"
                                    ><div class="concour-cell">
                                        <i class="pi pi-calendar"></i
                                        ><span>{{
                                            sp.data.concour_intitule
                                        }}</span>
                                    </div></template
                                ></Column
                            >
                            <Column field="titre" header="Titre" sortable
                                ><template #body="sp"
                                    ><span class="titre-cell">{{
                                        sp.data.titre
                                    }}</span></template
                                ></Column
                            >
                            <Column field="contenu" header="Contenu"
                                ><template #body="sp"
                                    ><div class="contenu-cell">
                                        {{ sp.data.contenu }}
                                    </div></template
                                ></Column
                            >
                            <Column
                                field="date_limite"
                                header="Date limite"
                                sortable
                                ><template #body="sp"
                                    ><div class="date-cell">
                                        <i class="pi pi-calendar"></i
                                        ><span
                                            :class="{
                                                expired: isDateExpired(
                                                    sp.data.date_limite,
                                                ),
                                            }"
                                            >{{
                                                sp.data.date_limite ||
                                                "Illimitée"
                                            }}</span
                                        >
                                    </div></template
                                ></Column
                            >
                            <Column
                                field="published_at"
                                header="Publication"
                                sortable
                                ><template #body="sp"
                                    ><div class="date-cell">
                                        <i class="pi pi-clock"></i
                                        ><span>{{
                                            sp.data.published_at || "-"
                                        }}</span>
                                    </div></template
                                ></Column
                            >
                            <Column header="Statut"
                                ><template #body="sp"
                                    ><Tag
                                        :severity="
                                            getStatusBadge(sp.data.is_active)
                                                .severity
                                        "
                                        :value="
                                            getStatusBadge(sp.data.is_active)
                                                .value
                                        "
                                        rounded /></template
                            ></Column>
                            <Column header="Fichier" style="width: 6rem"
                                ><template #body="sp"
                                    ><Button
                                        v-if="sp.data.fichier_url"
                                        icon="pi pi-download"
                                        rounded
                                        text
                                        size="small"
                                        class="download-btn"
                                        v-tooltip.top="'Télécharger'"
                                        @click="
                                            downloadFile(
                                                sp.data.fichier_url,
                                                sp.data.fichier_nom,
                                            )
                                        "
                                    /><span v-else class="no-file"
                                        >-</span
                                    ></template
                                ></Column
                            >
                            <Column header="Actions" style="width: 10rem">
                                <template #body="sp">
                                    <div class="actions-cell">
                                        <Button
                                            icon="pi pi-pencil"
                                            rounded
                                            text
                                            size="small"
                                            class="edit-btn"
                                            v-tooltip.top="'Modifier'"
                                            @click="openEditDialog(sp.data)"
                                        />
                                        <Button
                                            v-if="!sp.data.is_active"
                                            icon="pi pi-check-circle"
                                            rounded
                                            text
                                            size="small"
                                            class="publish-btn"
                                            v-tooltip.top="'Publier'"
                                            @click="publishCommunique(sp.data)"
                                        />
                                        <Button
                                            v-else
                                            icon="pi pi-eye-slash"
                                            rounded
                                            text
                                            size="small"
                                            class="unpublish-btn"
                                            v-tooltip.top="'Dépublier'"
                                            @click="
                                                unpublishCommunique(sp.data)
                                            "
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            rounded
                                            text
                                            size="small"
                                            class="delete-btn"
                                            v-tooltip.top="'Supprimer'"
                                            @click="deleteCommunique(sp.data)"
                                        />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </template>
                </Card>
            </div>

            <div class="mobile-view">
                <div v-if="communiques.length === 0" class="empty-state">
                    <i class="pi pi-inbox"></i>
                    <p>Aucun communiqué</p>
                </div>
                <div
                    v-for="item in communiques"
                    :key="item.id"
                    class="communique-card"
                >
                    <div class="card-header">
                        <div class="card-title">
                            <i class="pi pi-megaphone"></i
                            ><span>{{ item.titre }}</span>
                        </div>
                        <Tag
                            :severity="getStatusBadge(item.is_active).severity"
                            :value="getStatusBadge(item.is_active).value"
                            size="small"
                        />
                    </div>
                    <div class="card-body">
                        <div class="card-row">
                            <span class="card-label">Concours :</span
                            ><span class="card-value">{{
                                item.concour_intitule
                            }}</span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Contenu :</span
                            ><span class="card-value card-content">{{
                                item.contenu
                            }}</span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Date limite :</span
                            ><span
                                class="card-value"
                                :class="{
                                    expired: isDateExpired(item.date_limite),
                                }"
                                >{{ item.date_limite || "Illimitée" }}</span
                            >
                        </div>
                        <div class="card-row">
                            <span class="card-label">Publication :</span
                            ><span class="card-value">{{
                                item.published_at || "-"
                            }}</span>
                        </div>
                        <div class="card-row" v-if="item.fichier_url">
                            <span class="card-label">Fichier :</span
                            ><Button
                                icon="pi pi-download"
                                label="Télécharger"
                                size="small"
                                text
                                class="download-mobile-btn"
                                @click="
                                    downloadFile(
                                        item.fichier_url,
                                        item.fichier_nom,
                                    )
                                "
                            />
                        </div>
                    </div>
                    <div class="card-actions">
                        <Button
                            icon="pi pi-pencil"
                            rounded
                            text
                            size="small"
                            @click="openEditDialog(item)"
                        />
                        <Button
                            v-if="!item.is_active"
                            icon="pi pi-check-circle"
                            rounded
                            text
                            size="small"
                            class="text-green-600"
                            @click="publishCommunique(item)"
                        />
                        <Button
                            v-else
                            icon="pi pi-eye-slash"
                            rounded
                            text
                            size="small"
                            class="text-orange-600"
                            @click="unpublishCommunique(item)"
                        />
                        <Button
                            icon="pi pi-trash"
                            rounded
                            text
                            size="small"
                            class="text-red-600"
                            @click="deleteCommunique(item)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ⭐ DIALOG AVEC SELECT CONTENU -->
        <Dialog
            v-model:visible="dialogVisible"
            :header="
                isEditing ? 'Modifier le communiqué' : 'Nouveau communiqué'
            "
            :modal="true"
            :style="{ width: '90vw', maxWidth: '600px' }"
            :closable="true"
            :dismissableMask="false"
            class="communique-dialog"
        >
            <div class="dialog-content">
                <div class="form-field">
                    <label class="field-label"
                        >Concours concerné
                        <span class="required">*</span></label
                    >
                    <Select
                        v-model="form.concour_id"
                        :options="concours"
                        optionLabel="intitule"
                        optionValue="id"
                        placeholder="Choisissez un concours"
                        class="w-full select-in-dialog"
                        :showClear="true"
                        :filter="true"
                    />
                </div>
                <div class="form-field">
                    <label class="field-label"
                        >Titre <span class="required">*</span></label
                    ><InputText
                        v-model="form.titre"
                        placeholder="Titre du communiqué"
                        class="w-full"
                    />
                </div>
                <div class="form-field">
                    <label class="field-label"
                        >Contenu <span class="required">*</span></label
                    ><Textarea
                        v-model="form.contenu"
                        rows="6"
                        placeholder="Contenu..."
                        class="w-full"
                    />
                </div>
                <div class="form-field">
                    <label class="field-label">Fichier joint (optionnel)</label>
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFileUpload"
                        accept=".pdf,.doc,.docx,.jpg,.png"
                        class="hidden-file-input"
                    />
                    <Button
                        type="button"
                        icon="pi pi-upload"
                        label="Choisir un fichier"
                        class="p-button-outlined w-full"
                        @click="$refs.fileInput.click()"
                    />
                    <div v-if="selectedFileName" class="file-info">
                        <i class="pi pi-file-pdf"></i
                        ><span>{{ selectedFileName }}</span
                        ><Button
                            icon="pi pi-times"
                            class="p-button-rounded p-button-text p-button-sm"
                            @click="removeFile"
                        />
                    </div>
                    <small class="field-hint"
                        >PDF, DOC, DOCX, JPG, PNG (max 5MB)</small
                    >
                </div>
                <div class="form-field">
                    <label class="field-label">Date limite (optionnel)</label
                    ><Calendar
                        v-model="form.date_limite"
                        dateFormat="dd/mm/yy"
                        placeholder="Sélectionner une date"
                        class="w-full"
                        :showIcon="true"
                    />
                </div>
                <div class="form-field publication-field">
                    <div class="publication-switch">
                        <InputSwitch v-model="form.is_active" />
                        <div class="publication-info">
                            <span class="publication-label">{{
                                form.is_active
                                    ? "Publication immédiate"
                                    : "Enregistrer comme brouillon"
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <div class="dialog-footer">
                    <Button
                        label="Annuler"
                        icon="pi pi-times"
                        severity="secondary"
                        text
                        @click="dialogVisible = false"
                    />
                    <Button
                        :label="isEditing ? 'Mettre à jour' : 'Enregistrer'"
                        icon="pi pi-save"
                        severity="success"
                        @click="saveCommunique"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* ============================================ */
/* ⭐ CONTAINER */
/* ============================================ */
.page-container {
    padding: 1rem;
}

/* ============================================ */
/* ⭐ EN-TÊTE */
/* ============================================ */
.header-card {
    background: linear-gradient(
        135deg,
        var(--color-primary) 0%,
        var(--color-primary-dark) 100%
    );
    border-radius: 1rem;
    margin-bottom: 1.5rem;
    overflow: hidden;
    border: none !important;
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
    flex-wrap: wrap;
    color: white;
}
.header-icon {
    width: 3.5rem;
    height: 3.5rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.header-icon i {
    font-size: 1.75rem;
    color: white;
}
.header-text {
    flex: 1;
    min-width: 200px;
}
.header-text h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
    color: white;
}
.header-text p {
    font-size: 0.875rem;
    margin: 0;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.new-btn {
    background: white !important;
    color: var(--color-primary-dark) !important;
    border: 2px solid rgba(255, 255, 255, 0.5) !important;
    font-weight: 600 !important;
    padding: 0.65rem 1.25rem !important;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}
.new-btn:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    border-color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
.new-btn:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5) !important;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
    }
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
    .header-text p {
        justify-content: center;
    }
    .new-btn {
        width: 100%;
        justify-content: center;
    }
}

/* ============================================ */
/* ⭐ TABLEAU */
/* ============================================ */
.desktop-view {
    display: block;
}
.mobile-view {
    display: none;
}
.table-card {
    border-radius: 1rem;
    overflow: hidden;
}
.table-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.table-title i {
    color: var(--color-primary);
}
.total-badge {
    margin-left: auto;
    font-size: 0.75rem;
    background: var(--color-primary-light);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    color: var(--color-primary-dark);
}
.concour-cell,
.date-cell {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.concour-cell i,
.date-cell i {
    color: var(--color-primary);
    font-size: 0.875rem;
}
.contenu-cell {
    max-width: 250px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.expired {
    color: #ef4444;
    font-weight: 600;
}
.actions-cell {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}
.edit-btn,
.publish-btn {
    color: var(--color-primary) !important;
}
.edit-btn:hover,
.publish-btn:hover {
    background: var(--color-primary-light) !important;
}
.unpublish-btn {
    color: #f97316 !important;
}
.unpublish-btn:hover {
    background: rgba(249, 115, 22, 0.1) !important;
}
.delete-btn {
    color: #ef4444 !important;
}
.delete-btn:hover {
    background: rgba(239, 68, 68, 0.1) !important;
}
.download-btn {
    color: #3b82f6 !important;
}
.download-btn:hover {
    background: rgba(59, 130, 246, 0.1) !important;
}
.no-file {
    color: #9ca3af;
}

/* ============================================ */
/* ⭐ MOBILE CARDS */
/* ============================================ */
@media (max-width: 768px) {
    .page-container {
        padding: 0.75rem;
    }
    .desktop-view {
        display: none;
    }
    .mobile-view {
        display: block;
    }
    .communique-card {
        background: white;
        border-radius: 0.75rem;
        margin-bottom: 0.75rem;
        padding: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    .dark .communique-card {
        background: #1f2937;
        border-color: #374151;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .dark .card-header {
        border-bottom-color: #374151;
    }
    .card-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .card-title i {
        color: var(--color-primary);
    }
    .card-body {
        margin-bottom: 0.75rem;
    }
    .card-row {
        display: flex;
        margin-bottom: 0.5rem;
        font-size: 0.75rem;
    }
    .card-label {
        width: 85px;
        font-weight: 600;
        color: #6b7280;
        flex-shrink: 0;
    }
    .dark .card-label {
        color: #9ca3af;
    }
    .card-value {
        flex: 1;
        word-break: break-word;
    }
    .card-content {
        white-space: pre-wrap;
        line-height: 1.4;
    }
    .card-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid #e5e7eb;
    }
    .dark .card-actions {
        border-top-color: #374151;
    }
    .download-mobile-btn {
        color: #3b82f6 !important;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #9ca3af;
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 0.5rem;
        opacity: 0.5;
    }
}

/* ============================================ */
/* ⭐ DIALOGUE */
/* ============================================ */
.communique-dialog :deep(.p-dialog-header) {
    background: linear-gradient(
        135deg,
        var(--color-primary) 0%,
        var(--color-primary-dark) 100%
    );
    color: white;
    padding: 1rem;
}
.communique-dialog :deep(.p-dialog-title) {
    font-size: 1.1rem;
    font-weight: 600;
}
.communique-dialog :deep(.p-dialog-content) {
    padding: 1rem;
    overflow: visible !important;
}
.dialog-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    overflow: visible !important;
    min-width: 0 !important;
}
.field-label {
    font-weight: 600;
    font-size: 0.8rem;
    color: #374151;
}
.dark .field-label {
    color: #e5e7eb;
}
.required {
    color: #ef4444;
}
.hidden-file-input {
    display: none;
}
.file-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    font-size: 0.75rem;
}
.dark .file-info {
    background: #374151;
}
.file-info i {
    color: #ef4444;
}
.field-hint {
    font-size: 0.65rem;
    color: #6b7280;
}
.publication-field {
    background: #f9fafb;
    border-radius: 0.75rem;
    padding: 0.75rem;
}
.dark .publication-field {
    background: #1f2937;
}
.publication-switch {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.publication-info {
    flex: 1;
}
.publication-label {
    font-weight: 600;
    font-size: 0.8rem;
}
.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}
.dark .dialog-footer {
    border-top-color: #374151;
}

/* ⭐⭐ SELECT DANS DIALOG - SOLUTION FINALE ⭐⭐ */
:deep(.select-in-dialog) {
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
}

:deep(.select-in-dialog .p-select-label) {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.5 !important;
    padding: 0.75rem 2.5rem 0.75rem 1rem !important;
    font-size: 0.875rem !important;
    height: auto !important;
    min-height: 44px !important;
    text-overflow: clip !important;
    display: flex !important;
    align-items: center !important;
}

/* ⭐ Panel dans le body - contraint à la largeur du Dialog */
:deep(.p-select-panel) {
    width: auto !important;
    min-width: 0 !important;
    max-width: 600px !important;
    box-sizing: border-box !important;
}

:deep(.p-select-list-container) {
    max-height: 250px !important;
    overflow-y: auto !important;
}

:deep(.p-select-item) {
    white-space: normal !important;
    word-break: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.5 !important;
    min-height: 42px !important;
    display: flex !important;
    align-items: center !important;
    font-size: 0.875rem !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}

@media (max-width: 640px) {
    :deep(.p-select-panel) {
        max-width: 90vw !important;
    }
    :deep(.p-select-item) {
        font-size: 0.85rem !important;
    }
}
/* ============================================ */
/* ⭐ MOBILE */
/* ============================================ */
@media (max-width: 640px) {
    :deep(.select-in-dialog .p-select-label) {
        font-size: 0.82rem !important;
        padding: 0.7rem 2.5rem 0.7rem 0.85rem !important;
        min-height: 46px !important;
        max-height: 100px !important;
    }
    :deep(.p-select-panel) {
        width: 100% !important;
        max-height: 45vh !important;
    }
    :deep(.p-select-item) {
        padding: 0.85rem 1rem !important;
        font-size: 0.85rem !important;
        min-height: 46px !important;
        max-width: 100% !important;
    }
    :deep(.p-select-filter) {
        font-size: 0.9rem !important;
        padding: 0.6rem 0.85rem !important;
    }
}

/* ============================================ */
/* ⭐ DARK MODE */
/* ============================================ */
.dark :deep(.p-select-item) {
    border-bottom-color: rgba(255, 255, 255, 0.05) !important;
}
.dark :deep(.p-select-item:hover) {
    background: rgba(16, 185, 129, 0.12) !important;
}
.dark :deep(.p-select-item.p-highlight) {
    background: rgba(16, 185, 129, 0.18) !important;
    color: #34d399 !important;
}
.dark :deep(.p-select-header) {
    border-bottom-color: rgba(255, 255, 255, 0.08) !important;
}
.dark :deep(.p-select-filter) {
    background: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #475569 !important;
}

/* ============================================ */
/* ⭐ BOUTONS PRIMEVUE */
/* ============================================ */
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
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    border-color: var(--color-primary) !important;
    background: var(--color-primary) !important;
}
:deep(.p-inputswitch.p-inputswitch-checked .p-inputswitch-slider) {
    background: var(--color-primary) !important;
}
:deep(
        .p-inputswitch.p-inputswitch-checked:not(.p-disabled):hover
            .p-inputswitch-slider
    ) {
    background: var(--color-primary-dark) !important;
}
:deep(.p-dropdown-panel .p-dropdown-items .p-dropdown-item.p-highlight) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}
:deep(.p-tag.p-tag-success) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}
:deep(.p-badge.p-badge-success) {
    background: var(--color-primary) !important;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--color-primary-light) !important;
}
:deep(.p-datatable .p-datatable-thead > tr > th) {
    border-bottom: 2px solid var(--color-primary-light);
}
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
.header-card,
.table-card {
    animation: fadeIn 0.4s ease-out;
}
</style>
