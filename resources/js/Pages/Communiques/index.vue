<script setup>
import { ref, watch } from "vue";
import { router, Head, Link } from "@inertiajs/vue3";
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

// État du formulaire
const dialogVisible = ref(false);
const isEditing = ref(false);
const form = ref({
    id: null,
    concour_id: null,
    titre: "",
    contenu: "",
    is_active: false,
    publish: false,
    date_limite: null,
});

// Réinitialiser le formulaire
const resetForm = () => {
    form.value = {
        id: null,
        concour_id: null,
        titre: "",
        contenu: "",
        is_active: false,
        publish: false,
        date_limite: null,
    };
    isEditing.value = false;
};

// Ouvrir le dialogue pour créer
const openCreateDialog = () => {
    resetForm();
    dialogVisible.value = true;
};

// Ouvrir le dialogue pour éditer
const openEditDialog = (communique) => {
    form.value = {
        id: communique.id,
        concour_id: communique.concour_id,
        titre: communique.titre,
        contenu: communique.contenu,
        is_active: communique.is_active,
        publish: false,
        date_limite: communique.date_limite || null,
    };
    isEditing.value = true;
    dialogVisible.value = true;
};

// Récupérer l'intitulé du concours par son ID
const getConcourIntitule = (id) => {
    const concour = props.concours.find((c) => c.id === id);
    return concour ? concour.intitule : "";
};

// Récupérer le statut du concours par son ID
const getConcourStatut = (id) => {
    const concour = props.concours.find((c) => c.id === id);
    return concour ? concour.statut : "";
};
// Sauvegarder le communiqué
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
            detail: "Veuillez saisir le contenu du communiqué",
            life: 3000,
        });
        return;
    }

    router.post(route("communiques.store"), form.value, {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            resetForm();
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Communiqué enregistré",
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
};

// Publier un communiqué
const publishCommunique = (communique) => {
    confirm.require({
        message: `Voulez-vous publier ce communiqué ? Il sera visible sur la page d'accueil.`,
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Publier",
        rejectLabel: "Annuler",
        acceptClass: "p-button-success",
        accept: () => {
            router.patch(
                route("communiques.publish", communique.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.add({
                            severity: "success",
                            summary: "Publié",
                            detail: "Communiqué publié avec succès",
                            life: 3000,
                        });
                    },
                },
            );
        },
    });
};

// Dépublier un communiqué
const unpublishCommunique = (communique) => {
    confirm.require({
        message: `Voulez-vous dépublier ce communiqué ? Il ne sera plus visible sur la page d'accueil.`,
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Dépublier",
        rejectLabel: "Annuler",
        acceptClass: "p-button-warning",
        accept: () => {
            router.patch(
                route("communiques.unpublish", communique.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.add({
                            severity: "info",
                            summary: "Dépublié",
                            detail: "Communiqué dépublié avec succès",
                            life: 3000,
                        });
                    },
                },
            );
        },
    });
};

// Supprimer un communiqué
const deleteCommunique = (communique) => {
    confirm.require({
        message: `Voulez-vous supprimer définitivement ce communiqué ?`,
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Supprimer",
        rejectLabel: "Annuler",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("communiques.destroy", communique.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Supprimé",
                        detail: "Communiqué supprimé avec succès",
                        life: 3000,
                    });
                },
            });
        },
    });
};

// Statut badge
const getStatusBadge = (isActive) => {
    return isActive
        ? { severity: "success", value: "Publié" }
        : { severity: "secondary", value: "Brouillon" };
};

// Vérifier si la date limite est expirée
const isDateExpired = (dateLimite) => {
    if (!dateLimite) return false;
    return new Date(dateLimite) < new Date();
};
</script>

<template>
    <AppLayout>
        <Head title="Gestion des communiqués" />
        <ConfirmDialog />
        <Toast />

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
                                        class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="pi pi-megaphone text-white text-3xl"
                                        ></i>
                                    </div>
                                    <div>
                                        <h1
                                            class="text-3xl font-bold text-900 m-0 mb-2"
                                        >
                                            Gestion des communiqués
                                        </h1>
                                        <p
                                            class="text-600 m-0 flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-info-circle text-emerald-500"
                                            ></i>
                                            Créez et gérez les communiqués pour
                                            informer les candidats
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    label="Nouveau communiqué"
                                    icon="pi pi-plus"
                                    severity="success"
                                    @click="openCreateDialog"
                                    class="shadow-sm"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Tableau des communiqués -->
            <div class="grid">
                <div class="col-12">
                    <Card class="shadow-md">
                        <template #title>
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-list text-emerald-500"></i>
                                <span class="text-lg font-semibold"
                                    >Liste des communiqués</span
                                >
                                <span class="text-sm text-600 ml-auto"
                                    >{{
                                        communiques.length
                                    }}
                                    communiqué(s)</span
                                >
                            </div>
                        </template>
                        <template #content>
                            <DataTable
                                :value="communiques"
                                stripedRows
                                showGridlines
                                responsiveLayout="stack"
                                class="p-datatable-sm"
                            >
                                <Column
                                    field="concour_intitule"
                                    header="Concours"
                                    sortable
                                >
                                    <template #body="slotProps">
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-calendar text-emerald-500"
                                            ></i>
                                            <span>{{
                                                slotProps.data.concour_intitule
                                            }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column field="titre" header="Titre" sortable>
                                    <template #body="slotProps">
                                        <span class="font-medium">{{
                                            slotProps.data.titre
                                        }}</span>
                                    </template>
                                </Column>

                                <Column
                                    field="contenu"
                                    header="Contenu"
                                    style="min-width: 200px"
                                >
                                    <template #body="slotProps">
                                        <div
                                            class="text-sm text-600 line-clamp-2"
                                        >
                                            {{ slotProps.data.contenu }}
                                        </div>
                                    </template>
                                </Column>

                                <Column
                                    field="date_limite"
                                    header="Date limite"
                                    sortable
                                >
                                    <template #body="slotProps">
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-calendar text-400"
                                            ></i>
                                            <span
                                                :class="{
                                                    'text-red-500 font-semibold':
                                                        isDateExpired(
                                                            slotProps.data
                                                                .date_limite,
                                                        ),
                                                    'text-green-600':
                                                        slotProps.data
                                                            .date_limite &&
                                                        !isDateExpired(
                                                            slotProps.data
                                                                .date_limite,
                                                        ),
                                                }"
                                            >
                                                {{
                                                    slotProps.data
                                                        .date_limite ||
                                                    "Illimitée"
                                                }}
                                            </span>
                                        </div>
                                    </template>
                                </Column>

                                <Column
                                    field="published_at"
                                    header="Date publication"
                                    sortable
                                >
                                    <template #body="slotProps">
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <i class="pi pi-clock text-400"></i>
                                            <span>{{
                                                slotProps.data.published_at ||
                                                "-"
                                            }}</span>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Statut">
                                    <template #body="slotProps">
                                        <div class="flex flex-column gap-1">
                                            <Tag
                                                :severity="
                                                    getStatusBadge(
                                                        slotProps.data
                                                            .is_active,
                                                    ).severity
                                                "
                                                :value="
                                                    getStatusBadge(
                                                        slotProps.data
                                                            .is_active,
                                                    ).value
                                                "
                                                rounded
                                            />
                                            <Tag
                                                v-if="
                                                    isDateExpired(
                                                        slotProps.data
                                                            .date_limite,
                                                    )
                                                "
                                                severity="danger"
                                                value="Expiré"
                                                size="small"
                                                class="mt-1"
                                            />
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Actions" style="width: 10rem">
                                    <template #body="slotProps">
                                        <div class="flex gap-1">
                                            <Button
                                                icon="pi pi-pencil"
                                                rounded
                                                text
                                                size="small"
                                                class="text-emerald-600"
                                                v-tooltip.top="'Modifier'"
                                                @click="
                                                    openEditDialog(
                                                        slotProps.data,
                                                    )
                                                "
                                            />
                                            <Button
                                                v-if="!slotProps.data.is_active"
                                                icon="pi pi-check-circle"
                                                rounded
                                                text
                                                size="small"
                                                class="text-green-600"
                                                v-tooltip.top="'Publier'"
                                                @click="
                                                    publishCommunique(
                                                        slotProps.data,
                                                    )
                                                "
                                            />
                                            <Button
                                                v-else
                                                icon="pi pi-eye-slash"
                                                rounded
                                                text
                                                size="small"
                                                class="text-orange-600"
                                                v-tooltip.top="'Dépublier'"
                                                @click="
                                                    unpublishCommunique(
                                                        slotProps.data,
                                                    )
                                                "
                                            />
                                            <Button
                                                icon="pi pi-trash"
                                                rounded
                                                text
                                                size="small"
                                                class="text-red-600"
                                                v-tooltip.top="'Supprimer'"
                                                @click="
                                                    deleteCommunique(
                                                        slotProps.data,
                                                    )
                                                "
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </template>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Dialogue de création/édition -->
        <Dialog
            v-model:visible="dialogVisible"
            :header="
                isEditing ? 'Modifier le communiqué' : 'Nouveau communiqué'
            "
            :modal="true"
            :style="{ width: '90vw', maxWidth: '1000px' }"
            :closable="true"
            :dismissableMask="true"
            class="communique-dialog"
        >
            <div class="dialog-content">
                <!-- Message d'aide -->
                <div class="help-message">
                    <i class="pi pi-info-circle"></i>
                    <div>
                        <strong>À quoi sert un communiqué ?</strong>
                        <p>
                            Un communiqué permet d'informer les candidats sur
                            les étapes à suivre, les dates importantes, les
                            documents requis ou toute information concernant un
                            concours.
                        </p>
                    </div>
                </div>

                <!-- Champ Concours - Structure corrigée -->
                <div class="form-field">
                    <label class="field-label">
                        <i class="pi pi-calendar"></i>
                        Concours concerné
                        <span class="required">*</span>
                    </label>
                    <div class="select-container">
                        <Select
                            v-model="form.concour_id"
                            :options="concours"
                            optionLabel="intitule"
                            optionValue="id"
                            placeholder="Choisissez un concours"
                            class="w-full"
                            :class="{
                                'p-invalid': !form.concour_id && form.submitted,
                            }"
                            :showClear="true"
                            :filter="true"
                        >
                            <template #value="slotProps">
                                <div
                                    v-if="slotProps.value"
                                    class="selected-value"
                                >
                                    <i
                                        class="pi pi-tag text-emerald-500 mr-2"
                                    ></i>
                                    <span class="selected-text">{{
                                        getConcourIntitule(slotProps.value)
                                    }}</span>
                                    <Tag
                                        v-if="getConcourStatut(slotProps.value)"
                                        :value="
                                            getConcourStatut(slotProps.value)
                                        "
                                        :severity="
                                            getConcourStatut(
                                                slotProps.value,
                                            ) === 'Actif'
                                                ? 'success'
                                                : 'secondary'
                                        "
                                        size="small"
                                        class="ml-2"
                                    />
                                </div>
                                <span v-else class="text-400"
                                    >Choisissez un concours</span
                                >
                            </template>
                        </Select>
                    </div>
                    <small class="field-hint"
                        >Sélectionnez le concours auquel ce communiqué est
                        destiné</small
                    >
                </div>

                <!-- Champ Titre -->
                <div class="form-field">
                    <label class="field-label">
                        <i class="pi pi-heading"></i>
                        Titre du communiqué
                        <span class="required">*</span>
                    </label>
                    <InputText
                        v-model="form.titre"
                        placeholder="Ex: Procédure de candidature 2026"
                        class="field-input"
                    />
                    <small class="field-hint"
                        >Un titre clair et concis pour attirer
                        l'attention</small
                    >
                </div>

                <!-- Champ Contenu -->
                <div class="form-field">
                    <label class="field-label">
                        <i class="pi pi-file-edit"></i>
                        Contenu du communiqué
                        <span class="required">*</span>
                    </label>
                    <Textarea
                        v-model="form.contenu"
                        rows="12"
                        placeholder="Décrivez les informations importantes pour les candidats :&#10;&#10;• Étape 1 : Inscription en ligne du [date] au [date]&#10;• Étape 2 : Dépôt des dossiers avant le [date]&#10;• Étape 3 : Entretien prévu le [date]&#10;• Documents à fournir : CV, lettre de motivation, diplômes...&#10;• Contact : email@example.com"
                        class="field-textarea"
                    />
                    <div class="field-footer">
                        <small class="field-hint"
                            >Utilisez • pour créer des listes à puces</small
                        >
                        <small class="char-count"
                            >{{ form.contenu.length }} caractères</small
                        >
                    </div>
                </div>

                <!-- Champ Date limite -->
                <div class="form-field">
                    <label class="field-label">
                        <i class="pi pi-calendar-clock"></i>
                        Date limite de validité
                        <span class="text-400 text-sm ml-2">(Optionnel)</span>
                    </label>
                    <Calendar
                        v-model="form.date_limite"
                        dateFormat="dd/mm/yy"
                        placeholder="Sélectionner une date limite"
                        class="field-input"
                        :showIcon="true"
                        :showButtonBar="true"
                    />
                    <small class="field-hint">
                        Laissez vide pour une validité illimitée. Après cette
                        date, le communiqué ne sera plus affiché.
                    </small>
                </div>

                <!-- Champ Publication -->
                <div class="form-field publication-field">
                    <div class="publication-content">
                        <div class="publication-switch">
                            <InputSwitch v-model="form.is_active" />
                            <div class="publication-info">
                                <span class="publication-label">
                                    {{
                                        form.is_active
                                            ? "✅ Publication immédiate"
                                            : "📝 Enregistrer comme brouillon"
                                    }}
                                </span>
                                <small class="publication-hint">
                                    {{
                                        form.is_active
                                            ? "Le communiqué sera visible sur la page d'accueil"
                                            : "Le communiqué sera sauvegardé mais pas encore visible"
                                    }}
                                </small>
                            </div>
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
                        :label="isEditing ? 'Mettre à jour' : 'Publier'"
                        :icon="isEditing ? 'pi pi-save' : 'pi pi-send'"
                        severity="success"
                        @click="saveCommunique"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    box-orient: vertical;
    overflow: hidden;
}

/* Style du dialogue */
.communique-dialog :deep(.p-dialog) {
    border-radius: 16px;
    overflow: hidden;
}

.communique-dialog :deep(.p-dialog-header) {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 1.5rem 2rem;
}

.communique-dialog :deep(.p-dialog-title) {
    font-size: 1.5rem;
    font-weight: 600;
}

.communique-dialog :deep(.p-dialog-content) {
    padding: 0;
}

.communique-dialog :deep(.p-dialog-footer) {
    padding: 0;
}

/* Contenu du dialogue */
.dialog-content {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Message d'aide */
.help-message {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: #f0fdf4;
    border-radius: 12px;
    border-left: 4px solid #10b981;
}

.help-message i {
    color: #10b981;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.help-message div {
    flex: 1;
}

.help-message strong {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: #166534;
}

.help-message p {
    font-size: 0.75rem;
    color: #4b5563;
    margin: 0;
    line-height: 1.4;
}

/* Champs de formulaire */
.form-field {
    display: block;
    width: 100%;
}

.field-label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: #374151;
}

.field-label i {
    color: #10b981;
    margin-right: 0.5rem;
}

.required {
    color: #ef4444;
    margin-left: 0.25rem;
}

.field-input {
    width: 100%;
    display: block;
}

.field-input :deep(.p-inputtext),
.field-input :deep(.p-calendar) {
    width: 100%;
}

.field-input :deep(.p-inputtext) {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.field-textarea {
    width: 100%;
    display: block;
}

.field-textarea :deep(.p-textarea) {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    font-size: 0.875rem;
    line-height: 1.5;
    resize: vertical;
}

.field-hint {
    display: block;
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.field-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.char-count {
    font-size: 0.7rem;
    font-family: monospace;
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #4b5563;
}

/* Champ publication */
.publication-field {
    background: #f9fafb;
    border-radius: 12px;
    padding: 1rem;
    margin-top: 0.5rem;
}

.publication-content {
    display: block;
}

.publication-switch {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.publication-info {
    flex: 1;
}

.publication-label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    color: #374151;
}

.publication-hint {
    display: block;
    font-size: 0.7rem;
    color: #6b7280;
}

/* Footer du dialogue */
.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1rem 2rem;
    border-top: 1px solid #e5e7eb;
    background: #ffffff;
}

/* Dark mode */
.dark .help-message {
    background: #1a2e1f;
    border-left-color: #10b981;
}

.dark .help-message strong {
    color: #6ee7b7;
}

.dark .help-message p {
    color: #9ca3af;
}

.dark .field-label {
    color: #e5e7eb;
}

.dark .field-input :deep(.p-inputtext),
.dark .field-textarea :deep(.p-textarea) {
    background: #1f2937;
    border-color: #374151;
    color: #f3f4f6;
}

.dark .field-hint,
.dark .char-count {
    color: #9ca3af;
}

.dark .char-count {
    background: #111827;
}

.dark .publication-field {
    background: #1f2937;
}

.dark .publication-label {
    color: #f3f4f6;
}

.dark .dialog-footer {
    border-top-color: #374151;
    background: #1f2937;
}

/* Animation d'entrée */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.p-card {
    animation: fadeInUp 0.3s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .dialog-content {
        padding: 1.5rem;
        gap: 1rem;
    }

    .field-input :deep(.p-inputtext),
    .field-textarea :deep(.p-textarea) {
        padding: 0.625rem 0.875rem;
    }

    .publication-switch {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .dialog-footer {
        padding: 1rem 1.5rem;
        flex-wrap: wrap;
    }

    .dialog-footer .p-button {
        flex: 1;
    }

    .communique-dialog :deep(.p-dialog) {
        margin: 1rem;
        width: calc(100vw - 2rem) !important;
    }

    .communique-dialog :deep(.p-dialog-header) {
        padding: 1rem 1.5rem;
    }

    .communique-dialog :deep(.p-dialog-title) {
        font-size: 1.25rem;
    }
}
</style>
