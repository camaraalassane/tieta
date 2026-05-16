<template>
    <AppLayout>
        <Head :title="`Personnel - ${service.nom}`" />
        <Toast />
        <ConfirmDialog />

        <div class="p-3 sm:p-4 md:p-6 lg:px-8">
            <!-- En-tête -->
            <div class="mb-4 sm:mb-6">
                <!-- ⭐ Card Header Dynamique -->
                <Card class="header-card shadow-lg border-none overflow-hidden">
                    <template #content>
                        <div
                            class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4"
                        >
                            <div
                                class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto"
                            >
                                <!-- ⭐ Logo service - Dynamique -->
                                <div
                                    class="service-logo w-10 h-10 sm:w-12 sm:h-12"
                                >
                                    <img
                                        v-if="service.logo_url"
                                        :src="service.logo_url"
                                        :alt="service.nom"
                                        class="w-full h-full object-cover rounded-xl"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full bg-primary-light rounded-xl flex items-center justify-center"
                                    >
                                        <i
                                            class="pi pi-building theme-text-primary text-xl sm:text-2xl"
                                        ></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1
                                        class="text-xl sm:text-2xl font-bold text-900 m-0 truncate"
                                    >
                                        Personnel - {{ service.nom }}
                                    </h1>
                                    <p
                                        class="text-xs sm:text-sm text-600 mt-0.5"
                                    >
                                        Gérez les membres de votre service
                                    </p>
                                </div>
                            </div>
                            <!-- ⭐ Bouton Ajouter un membre - Dynamique -->
                            <Button
                                label="Ajouter un membre"
                                icon="pi pi-plus"
                                class="btn-add-member w-full sm:w-auto justify-center"
                                @click="openCreateDialog"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Liste du personnel -->
            <Card class="shadow-md rounded-xl overflow-hidden">
                <template #content>
                    <div class="overflow-x-auto">
                        <DataTable
                            :value="personnel"
                            class="p-datatable-sm"
                            stripedRows
                            showGridlines
                            responsiveLayout="scroll"
                        >
                            <Column
                                field="name"
                                header="Nom"
                                sortable
                                style="min-width: 120px"
                            ></Column>
                            <Column
                                field="prenom"
                                header="Prénom"
                                sortable
                                style="min-width: 100px"
                            ></Column>
                            <Column
                                field="email"
                                header="Email"
                                sortable
                                style="min-width: 180px"
                            ></Column>
                            <Column
                                field="pivot.role_in_service"
                                header="Rôle"
                                sortable
                                style="min-width: 100px"
                            >
                                <template #body="slotProps">
                                    <Tag
                                        :value="
                                            slotProps.data.pivot
                                                .role_in_service === 'gerant'
                                                ? 'Gérant'
                                                : 'Admin'
                                        "
                                        :severity="
                                            slotProps.data.pivot
                                                .role_in_service === 'gerant'
                                                ? 'warning'
                                                : 'info'
                                        "
                                        class="px-2 py-1 text-xs"
                                    />
                                </template>
                            </Column>
                            <Column header="Actions" style="width: 100px">
                                <template #body="slotProps">
                                    <div class="flex gap-1 justify-center">
                                        <Button
                                            icon="pi pi-pencil"
                                            class="p-button-rounded p-button-text p-button-sm text-blue-500 hover:bg-blue-50"
                                            v-tooltip.top="'Modifier'"
                                            @click="
                                                openEditDialog(slotProps.data)
                                            "
                                        />
                                        <Button
                                            icon="pi pi-trash"
                                            class="p-button-rounded p-button-text p-button-sm text-red-500 hover:bg-red-50"
                                            v-tooltip.top="'Supprimer'"
                                            @click="
                                                confirmDelete(slotProps.data)
                                            "
                                        />
                                    </div>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Dialog Création/Modification -->
        <Dialog
            v-model:visible="showDialog"
            :header="editingUser ? 'Modifier le membre' : 'Ajouter un membre'"
            :style="{ width: '90vw', maxWidth: '500px' }"
            modal
            class="p-fluid"
        >
            <form @submit.prevent="saveUser" class="p-fluid">
                <div class="field mb-3">
                    <label class="font-medium block mb-1"
                        >Nom <span class="text-red-500">*</span></label
                    >
                    <InputText
                        v-model="userForm.name"
                        placeholder="Nom"
                        :class="{ 'p-invalid': userForm.errors.name }"
                        class="w-full"
                    />
                    <small v-if="userForm.errors.name" class="p-error">{{
                        userForm.errors.name
                    }}</small>
                </div>

                <div class="field mb-3">
                    <label class="font-medium block mb-1">Prénom</label>
                    <InputText
                        v-model="userForm.prenom"
                        placeholder="Prénom"
                        class="w-full"
                    />
                </div>

                <div class="field mb-3">
                    <label class="font-medium block mb-1"
                        >Email <span class="text-red-500">*</span></label
                    >
                    <InputText
                        v-model="userForm.email"
                        type="email"
                        placeholder="email@exemple.com"
                        :class="{ 'p-invalid': userForm.errors.email }"
                        class="w-full"
                    />
                    <small v-if="userForm.errors.email" class="p-error">{{
                        userForm.errors.email
                    }}</small>
                </div>

                <div class="field mb-3">
                    <label class="font-medium block mb-1"
                        >Rôle <span class="text-red-500">*</span></label
                    >
                    <Select
                        v-model="userForm.role_in_service"
                        :options="roleOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Sélectionner un rôle"
                        class="w-full"
                    />
                </div>

                <div class="field mb-3">
                    <label class="font-medium block mb-1">
                        Mot de passe
                        <span v-if="!editingUser" class="text-red-500">*</span>
                        <span v-else class="text-gray-400 text-xs"
                            >(Laisser vide pour ne pas modifier)</span
                        >
                    </label>
                    <InputText
                        v-model="userForm.password"
                        type="password"
                        placeholder="********"
                        class="w-full"
                    />
                    <small v-if="!editingUser" class="text-gray-400 text-xs"
                        >Minimum 8 caractères</small
                    >
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <!-- ⭐ Bouton Annuler - Outlined dynamique -->
                    <Button
                        label="Annuler"
                        icon="pi pi-times"
                        outlined
                        class="btn-outlined-primary"
                        @click="showDialog = false"
                    />
                    <!-- ⭐ Bouton Enregistrer - Primaire dynamique -->
                    <Button
                        type="submit"
                        label="Enregistrer"
                        icon="pi pi-save"
                        :loading="userForm.processing"
                        class="btn-primary"
                    />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { useForm, Head, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import Tag from "primevue/tag";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    service: { type: Object, required: true },
    personnel: { type: Array, default: () => [] },
    isSuperAdmin: { type: Boolean, default: false },
    isGerant: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
});

const toast = useToast();
const confirm = useConfirm();

const showDialog = ref(false);
const editingUser = ref(null);
const roleOptions = [
    { label: "Administrateur", value: "admin" },
    { label: "Gérant", value: "gerant" },
];

const userForm = useForm({
    name: "",
    prenom: "",
    email: "",
    role_in_service: "admin",
    password: "",
});

const openCreateDialog = () => {
    editingUser.value = null;
    userForm.reset();
    userForm.role_in_service = "admin";
    showDialog.value = true;
};

const openEditDialog = (user) => {
    editingUser.value = user;
    userForm.name = user.name;
    userForm.prenom = user.prenom || "";
    userForm.email = user.email;
    userForm.role_in_service = user.pivot?.role_in_service || "admin";
    userForm.password = "";
    showDialog.value = true;
};

const saveUser = () => {
    if (editingUser.value) {
        userForm.put(
            route("services.personnel.update", [
                props.service.id,
                editingUser.value.id,
            ]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showDialog.value = false;
                    toast.add({
                        severity: "success",
                        summary: "Succès",
                        detail: "Membre modifié avec succès",
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Erreur",
                        detail:
                            errors.message || "Erreur lors de la modification",
                        life: 3000,
                    });
                },
            },
        );
    } else {
        userForm.post(route("services.personnel.store", props.service.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Membre ajouté avec succès",
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: errors.message || "Erreur lors de l'ajout",
                    life: 3000,
                });
            },
        });
    }
};

const confirmDelete = (user) => {
    confirm.require({
        message: `Voulez-vous vraiment retirer ${user.name} ${user.prenom || ""} de ce service ?`,
        header: "Confirmation de suppression",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(
                route("services.personnel.destroy", [
                    props.service.id,
                    user.id,
                ]),
                {
                    onSuccess: () => {
                        toast.add({
                            severity: "success",
                            summary: "Succès",
                            detail: "Membre retiré du service",
                            life: 3000,
                        });
                    },
                    onError: (errors) => {
                        toast.add({
                            severity: "error",
                            summary: "Erreur",
                            detail:
                                errors.message ||
                                "Erreur lors de la suppression",
                            life: 3000,
                        });
                    },
                },
            );
        },
    });
};
</script>

<style scoped>
/* ============================================ */
/* ⭐ SERVICE LOGO - DYNAMIQUE */
/* ============================================ */
.service-logo {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    overflow: hidden;
    background: var(--color-primary-light);
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .service-logo {
        width: 60px;
        height: 60px;
    }
}

/* ============================================ */
/* ⭐ DATATABLE - DYNAMIQUE */
/* ============================================ */
:deep(.p-datatable .p-datatable-tbody > tr) {
    transition: background-color 0.2s;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: var(--color-primary-light) !important;
}

/* ⭐ Row sélectionnée */
:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Header du datatable */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #f9fafb;
    color: var(--color-primary-dark);
    font-weight: 600;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.75rem 1rem;
    border-bottom: 2px solid var(--color-primary-light);
}

/* ⭐ Pagination */
:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    color: white !important;
}

:deep(.p-paginator .p-paginator-pages .p-paginator-page:hover) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Boutons d'action dans le datatable */
:deep(.p-button-text) {
    transition: all 0.2s;
}

:deep(.p-button-text:hover) {
    transform: scale(1.05);
    color: var(--color-primary) !important;
}

:deep(.p-button-text:focus) {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Boutons outlined */
:deep(.p-button-outlined) {
    border-color: var(--color-primary) !important;
    color: var(--color-primary) !important;
}

:deep(.p-button-outlined:hover) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
}

/* ⭐ Badges de statut */
:deep(.p-badge.p-badge-success) {
    background: var(--color-primary) !important;
}

:deep(.p-badge.p-badge-info) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Dropdown filters */
:deep(.p-dropdown-panel .p-dropdown-items .p-dropdown-item.p-highlight) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Input filters focus */
:deep(.p-inputtext:focus) {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Checkbox */
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    border-color: var(--color-primary) !important;
    background: var(--color-primary) !important;
}

/* ⭐ Liens */
:deep(.p-link) {
    color: var(--color-primary) !important;
}

:deep(.p-link:hover) {
    color: var(--color-primary-dark) !important;
}

/* ⭐ Tooltip */
:deep(.p-tooltip .p-tooltip-text) {
    background: var(--color-primary-dark) !important;
}

/* ⭐ Toast */
:deep(.p-toast .p-toast-message.p-toast-message-success) {
    border-left: 6px solid var(--color-primary) !important;
}

:deep(.p-toast .p-toast-message.p-toast-message-success .p-toast-message-icon) {
    color: var(--color-primary) !important;
}

/* ⭐ Dialog */
:deep(.p-dialog .p-dialog-header) {
    border-bottom: 2px solid var(--color-primary-light);
}

/* ⭐ Boutons dans les dialogs */
:deep(.p-dialog .p-button:not(.p-button-text):not(.p-button-outlined)) {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

:deep(.p-dialog .p-button:not(.p-button-text):not(.p-button-outlined):hover) {
    background: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
}

/* ⭐ Confirm dialog */
:deep(.p-confirm-dialog .p-dialog-content) {
    border-top: 3px solid var(--color-primary);
}

/* ⭐ Messages */
:deep(.p-message.p-message-success) {
    border-left: 6px solid var(--color-primary) !important;
}

:deep(.p-message.p-message-success .p-message-icon) {
    color: var(--color-primary) !important;
}

/* ⭐ Progress bar */
:deep(.p-progressbar .p-progressbar-value) {
    background: var(--color-primary) !important;
}

/* ⭐ Skeleton loading */
:deep(.p-skeleton) {
    background: var(--color-primary-light) !important;
}

/* ⭐ Tag */
:deep(.p-tag.p-tag-success) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ============================================ */
/* ⭐ ANIMATIONS */
/* ============================================ */
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

:deep(.p-datatable) {
    animation: fadeInUp 0.4s ease-out;
}

/* ============================================ */
/* ⭐ DARK MODE */
/* ============================================ */
.dark :deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #1f2937;
    color: var(--color-primary-light);
    border-bottom-color: var(--color-primary);
}

.dark :deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background-color: rgba(16, 185, 129, 0.1) !important;
}

.dark :deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
    background: rgba(16, 185, 129, 0.15) !important;
}

.dark .service-logo {
    background: rgba(16, 185, 129, 0.2);
}

/* ============================================ */
/* ⭐ RESPONSIVE */
/* ============================================ */
@media (max-width: 640px) {
    :deep(.p-datatable .p-datatable-thead > tr > th) {
        font-size: 0.6rem;
        padding: 0.5rem;
    }

    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        font-size: 0.7rem;
        padding: 0.5rem;
    }
}
/* ============================================ */
/* ⭐ HEADER CARD - DYNAMIQUE */
/* ============================================ */
.header-card {
    background: linear-gradient(
        to right,
        var(--color-primary-light),
        white
    ) !important;
}

.dark .header-card {
    background: linear-gradient(
        to right,
        rgba(16, 185, 129, 0.1),
        #1f2937
    ) !important;
}

/* ⭐ Logo placeholder */
.bg-primary-light {
    background: var(--color-primary-light) !important;
}

/* ⭐ Icône thème */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ⭐ Bouton Ajouter un membre */
.btn-add-member {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
}

.btn-add-member:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-add-member:focus {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ============================================ */
/* ⭐ ANIMATION */
/* ============================================ */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header-card {
    animation: slideDown 0.4s ease-out;
}
/* ⭐ Bouton primaire (fond plein) */
.btn-primary {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary:focus {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Bouton outlined (bordure seule) */
.btn-outlined-primary {
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    background: transparent !important;
    transition: all 0.2s ease;
}

.btn-outlined-primary:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    border-color: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-outlined-primary:focus {
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Ajustement pour les boutons dans les dialogs */
:deep(.p-dialog .btn-primary) {
    min-width: 100px;
}

:deep(.p-dialog .btn-outlined-primary) {
    min-width: 100px;
}
</style>
