<template>
    <AppLayout>
        <Head title="Gestion des Services" />
        <Toast />
        <ConfirmDialog />

        <div class="p-3 sm:p-4 md:p-6 lg:px-8 py-4 sm:py-5">
            <!-- En-tête -->
            <div class="mb-4 sm:mb-6">
                <Card
                    class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/10 dark:to-gray-900"
                >
                    <template #content>
                        <div
                            class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4"
                        >
                            <div
                                class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto"
                            >
                                <div
                                    class="w-12 h-12 sm:w-16 sm:h-16 bg-emerald-500 rounded-xl flex items-center justify-center shadow-2 flex-shrink-0"
                                >
                                    <i
                                        class="pi pi-building text-white text-2xl sm:text-3xl"
                                    ></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1
                                        class="text-xl sm:text-2xl md:text-3xl font-bold text-900 m-0 truncate"
                                    >
                                        Gestion des Services
                                    </h1>
                                    <p
                                        class="text-xs sm:text-sm text-600 mt-1 flex items-center gap-1 sm:gap-2"
                                    >
                                        <i
                                            class="pi pi-check-circle text-emerald-500 text-xs sm:text-sm"
                                        ></i>
                                        <span class="truncate"
                                            >Gérez les services et leurs
                                            responsables</span
                                        >
                                    </p>
                                </div>
                            </div>
                            <Button
                                label="Nouveau service"
                                icon="pi pi-plus"
                                class="p-button-success w-full sm:w-auto shadow-2 justify-center"
                                @click="openCreateDialog"
                            />
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Liste des services -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4"
            >
                <div v-for="service in services" :key="service.id" class="col">
                    <Card
                        class="h-full shadow-md hover:shadow-xl transition-all duration-300 border-t-4 border-emerald-500"
                    >
                        <template #content>
                            <div class="flex flex-col gap-3 p-2 sm:p-3">
                                <!-- En-tête avec logo et titre -->
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div
                                        class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0"
                                    >
                                        <div
                                            class="w-10 h-10 sm:w-12 sm:h-12 rounded-full overflow-hidden bg-gray-100 border flex-shrink-0"
                                        >
                                            <img
                                                v-if="service.logo_url"
                                                :src="service.logo_url"
                                                :alt="service.nom"
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center"
                                            >
                                                <i
                                                    class="pi pi-building text-emerald-500 text-lg sm:text-xl"
                                                ></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h3
                                                class="text-base sm:text-lg font-semibold text-900 m-0 truncate"
                                            >
                                                {{ service.nom }}
                                            </h3>
                                            <Badge
                                                :value="
                                                    service.is_active
                                                        ? 'Actif'
                                                        : 'Inactif'
                                                "
                                                :severity="
                                                    service.is_active
                                                        ? 'success'
                                                        : 'danger'
                                                "
                                                class="mt-1 text-xs"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Description -->
                                <p
                                    class="text-gray-600 text-xs sm:text-sm m-0 line-clamp-2"
                                >
                                    {{
                                        service.description ||
                                        "Aucune description fournie."
                                    }}
                                </p>

                                <!-- Gérant -->
                                <div
                                    class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                >
                                    <i
                                        class="pi pi-user text-emerald-500 text-sm"
                                    ></i>
                                    <div class="flex flex-col min-w-0 flex-1">
                                        <span class="text-xs text-gray-500"
                                            >Gérant</span
                                        >
                                        <span
                                            class="text-sm font-semibold text-gray-700 dark:text-gray-300 truncate"
                                        >
                                            {{
                                                service.gerant?.name ||
                                                "Non assigné"
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Statistiques -->
                                <div class="flex gap-2">
                                    <div
                                        class="flex-1 text-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
                                    >
                                        <span
                                            class="block text-lg sm:text-xl font-bold text-blue-700"
                                            >{{
                                                service.concours?.length || 0
                                            }}</span
                                        >
                                        <span
                                            class="text-xs text-gray-500 uppercase"
                                            >Concours</span
                                        >
                                    </div>
                                    <div
                                        class="flex-1 text-center p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg"
                                    >
                                        <span
                                            class="block text-lg sm:text-xl font-bold text-purple-700"
                                            >{{
                                                service.personnel?.length || 0
                                            }}</span
                                        >
                                        <span
                                            class="text-xs text-gray-500 uppercase"
                                            >Personnel</span
                                        >
                                    </div>
                                </div>

                                <!-- Boutons d'action -->
                                <div
                                    class="flex flex-wrap justify-center gap-1 pt-2 border-t"
                                >
                                    <Link
                                        :href="
                                            route('services.show', service.id)
                                        "
                                        class="p-button-text p-button-rounded p-button-sm w-8 h-8 flex items-center justify-center"
                                    >
                                        <i class="pi pi-eye text-base"></i>
                                    </Link>
                                    <Button
                                        icon="pi pi-pencil"
                                        class="p-button-text p-button-rounded p-button-warning p-button-sm w-8 h-8"
                                        @click="openEditDialog(service)"
                                    />
                                    <Button
                                        icon="pi pi-user-plus"
                                        class="p-button-text p-button-rounded p-button-info p-button-sm w-8 h-8"
                                        @click="openAssignAdminDialog(service)"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="p-button-text p-button-rounded p-button-danger p-button-sm w-8 h-8"
                                        @click="confirmDelete(service)"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Dialog Création/Modification -->
            <Dialog
                v-model:visible="showServiceDialog"
                :header="
                    editingService ? 'Modifier le service' : 'Nouveau service'
                "
                :style="{ width: '90vw', maxWidth: '500px' }"
                modal
            >
                <form @submit.prevent="saveService" class="p-fluid">
                    <div class="field mb-4">
                        <label class="font-bold text-900 block mb-2"
                            >Nom du service
                            <span class="text-red-500">*</span></label
                        >
                        <InputText
                            v-model="serviceForm.nom"
                            placeholder="Ex: Direction IT"
                            :class="{ 'p-invalid': serviceForm.errors.nom }"
                        />
                        <small v-if="serviceForm.errors.nom" class="p-error">{{
                            serviceForm.errors.nom
                        }}</small>
                    </div>

                    <div class="field mb-4">
                        <label class="font-bold text-900 block mb-2"
                            >Description</label
                        >
                        <Textarea
                            v-model="serviceForm.description"
                            rows="3"
                            placeholder="Missions du service..."
                        />
                    </div>

                    <div class="field mb-4">
                        <label class="font-bold text-900 block mb-2"
                            >Logo</label
                        >
                        <div
                            class="flex flex-col sm:flex-row items-center gap-3 border-1 border-300 p-3 rounded-lg"
                        >
                            <div
                                class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border flex-shrink-0"
                            >
                                <img
                                    v-if="logoPreview || serviceForm.logo_url"
                                    :src="logoPreview || serviceForm.logo_url"
                                    class="w-full h-full object-cover"
                                />
                                <i
                                    v-else
                                    class="pi pi-image text-gray-400 text-2xl"
                                ></i>
                            </div>
                            <div class="flex-1 w-full">
                                <input
                                    type="file"
                                    ref="logoInput"
                                    @change="handleLogoUpload"
                                    accept="image/*"
                                    class="hidden"
                                />
                                <Button
                                    type="button"
                                    label="Changer le logo"
                                    icon="pi pi-upload"
                                    class="p-button-outlined p-button-sm w-full sm:w-auto"
                                    @click="logoInput.click()"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ⭐ Champ Gérant avec gestion liste vide -->
                    <div class="field mb-4">
                        <label class="font-bold text-900 block mb-2"
                            >Responsable (Gérant)
                            <span class="text-red-500">*</span></label
                        >

                        <!-- Si la liste des gérants disponibles est vide -->
                        <div
                            v-if="availableGerants.length === 0"
                            class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-center"
                        >
                            <i
                                class="pi pi-info-circle text-yellow-600 text-xl mb-2 block"
                            ></i>
                            <p
                                class="text-yellow-700 dark:text-yellow-400 font-medium mb-1"
                            >
                                Aucun gérant disponible
                            </p>
                            <p
                                class="text-yellow-600 dark:text-yellow-500 text-sm"
                            >
                                Veuillez d'abord créer un utilisateur avec le
                                rôle "gérant" <br />
                                qui n'est pas encore associé à un service.
                            </p>
                        </div>

                        <!-- Sinon afficher le select -->
                        <Select
                            v-else
                            v-model="serviceForm.gerant_id"
                            :options="availableGerants"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Choisir un responsable"
                            :class="{
                                'p-invalid': serviceForm.errors.gerant_id,
                            }"
                            filter
                            class="w-full"
                        >
                            <template #option="slotProps">
                                <div class="flex flex-col">
                                    <span class="font-medium"
                                        >{{ slotProps.option.name }}
                                        {{
                                            slotProps.option.prenom || ""
                                        }}</span
                                    >
                                    <small class="text-400">{{
                                        slotProps.option.email
                                    }}</small>
                                </div>
                            </template>
                        </Select>

                        <small
                            v-if="serviceForm.errors.gerant_id"
                            class="p-error text-xs"
                            >{{ serviceForm.errors.gerant_id }}</small
                        >
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <Button
                            label="Annuler"
                            icon="pi pi-times"
                            class="p-button-text p-button-secondary"
                            @click="showServiceDialog = false"
                        />
                        <Button
                            type="submit"
                            label="Enregistrer"
                            icon="pi pi-check"
                            :loading="serviceForm.processing"
                            :disabled="availableGerants.length === 0"
                        />
                    </div>
                </form>
            </Dialog>

            <!-- Dialog Assigner Admin -->
            <Dialog
                v-model:visible="showAssignAdminDialog"
                header="Ajouter un Administrateur"
                :style="{ width: '90vw', maxWidth: '400px' }"
                modal
            >
                <div class="p-fluid">
                    <div class="field mb-4">
                        <label class="font-bold block mb-2"
                            >Utilisateur à ajouter</label
                        >
                        <Select
                            v-model="selectedAdminId"
                            :options="availableAdmins"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Rechercher..."
                            filter
                            class="w-full"
                        />
                    </div>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button
                            label="Fermer"
                            class="p-button-text"
                            @click="showAssignAdminDialog = false"
                        />
                        <Button
                            label="Assigner"
                            icon="pi pi-user-plus"
                            @click="assignAdmin"
                            :disabled="!selectedAdminId"
                        />
                    </div>
                </div>
            </Dialog>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { useForm, Head, Link, router } from "@inertiajs/vue3";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import Badge from "primevue/badge";
import Toast from "primevue/toast";
import ConfirmDialog from "primevue/confirmdialog";
import { useToast } from "primevue/usetoast";
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    services: { type: Array, default: () => [] },
    availableGerants: { type: Array, default: () => [] },
    availableAdmins: { type: Array, default: () => [] },
});

const toast = useToast();
const confirm = useConfirm();

const showServiceDialog = ref(false);
const showAssignAdminDialog = ref(false);
const editingService = ref(null);
const selectedService = ref(null);
const selectedAdminId = ref(null);
const logoFile = ref(null);
const logoPreview = ref(null);
const logoInput = ref(null);

const serviceForm = useForm({
    nom: "",
    description: "",
    logo: null,
    gerant_id: null,
    logo_url: null,
});

// ⭐ Computed pour vérifier si des gérants sont disponibles
const hasAvailableGerants = computed(() => props.availableGerants?.length > 0);

const openCreateDialog = () => {
    editingService.value = null;
    serviceForm.reset();
    logoPreview.value = null;
    logoFile.value = null;
    showServiceDialog.value = true;
};

const openEditDialog = (service) => {
    editingService.value = service;
    serviceForm.nom = service.nom;
    serviceForm.description = service.description || "";
    serviceForm.gerant_id = service.gerant_id;
    serviceForm.logo_url = service.logo_url;
    serviceForm.logo = null;
    logoPreview.value = service.logo_url;
    logoFile.value = null;
    showServiceDialog.value = true;
};

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: "Le fichier ne doit pas dépasser 2 Mo",
                life: 3000,
            });
            return;
        }
        logoFile.value = file;
        serviceForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const saveService = () => {
    if (editingService.value) {
        serviceForm
            .transform((data) => ({ ...data, _method: "PUT" }))
            .post(route("admin.services.update", editingService.value.id), {
                forceFormData: true,
                onSuccess: () => {
                    showServiceDialog.value = false;
                    toast.add({
                        severity: "success",
                        summary: "Succès",
                        detail: "Service mis à jour",
                        life: 3000,
                    });
                },
            });
    } else {
        serviceForm.post(route("admin.services.store"), {
            onSuccess: () => {
                showServiceDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Service créé",
                    life: 3000,
                });
            },
        });
    }
};

const confirmDelete = (service) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer le service "${service.nom}" ?`,
        header: "Confirmation de suppression",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            router.delete(route("admin.services.destroy", service.id), {
                onSuccess: () =>
                    toast.add({
                        severity: "info",
                        summary: "Supprimé",
                        detail: "Service supprimé",
                        life: 3000,
                    }),
            });
        },
    });
};

const openAssignAdminDialog = (service) => {
    selectedService.value = service;
    selectedAdminId.value = null;
    showAssignAdminDialog.value = true;
};

const assignAdmin = () => {
    router.post(
        route("admin.services.assign-admin", selectedService.value.id),
        {
            user_id: selectedAdminId.value,
        },
        {
            onSuccess: () => {
                showAssignAdminDialog.value = false;
                toast.add({
                    severity: "success",
                    summary: "Ajouté",
                    detail: "Administrateur assigné",
                    life: 3000,
                });
            },
        },
    );
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Animation d'entrée des cartes */
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

/* Styles PrimeVue personnalisés */
:deep(.p-card) {
    transition: all 0.3s ease;
}

:deep(.p-card:hover) {
    transform: translateY(-2px);
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #f8fafc;
    color: #1e293b;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 1rem;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: #f0fdf4;
}

:deep(.p-button.p-button-text) {
    color: #10b981;
}

:deep(.p-button.p-button-text:hover) {
    background: rgba(16, 185, 129, 0.1);
}

:deep(.p-button.p-button-danger.p-button-text) {
    color: #ef4444;
}

:deep(.p-button.p-button-danger.p-button-text:hover) {
    background: rgba(239, 68, 68, 0.1);
}

:deep(.p-datatable .p-paginator) {
    padding: 1rem;
    border-top: 1px solid var(--surface-border);
}

:deep(
        .p-datatable
            .p-paginator
            .p-paginator-pages
            .p-paginator-page.p-highlight
    ) {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

/* Styles pour les champs de formulaire */
.field {
    @apply flex flex-col gap-1;
}

.p-error {
    @apply text-red-500 text-xs mt-1 block;
}

/* Styles pour la checkbox */
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    border-color: #10b981 !important;
    background: #10b981 !important;
}

/* Styles pour les dropdowns */
:deep(.p-dropdown:focus),
:deep(.p-button:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

:deep(.p-dropdown-panel .p-dropdown-item.p-highlight) {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
}

/* Styles pour les tags */
:deep(.p-tag) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

:deep(.p-tag-value) {
    font-weight: 500;
}

/* Styles pour les badges */
:deep(.p-badge) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Styles pour les inputs */
:deep(.p-inputtext:enabled:focus) {
    border-color: #10b981;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
}

/* Styles pour les boutons primaires */
:deep(.p-button.p-button-success) {
    background: #10b981;
    border-color: #10b981;
}

:deep(.p-button.p-button-success:hover) {
    background: #059669;
    border-color: #059669;
}

:deep(.p-button.p-button-success:focus) {
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.5);
}

/* Styles pour les dialogs */
:deep(.p-dialog .p-dialog-header) {
    padding: 1.5rem;
    border-bottom: 1px solid var(--surface-border);
}

:deep(.p-dialog .p-dialog-content) {
    padding: 1.5rem;
}

:deep(.p-dialog .p-dialog-footer) {
    padding: 1.5rem;
    border-top: 1px solid var(--surface-border);
}

/* Styles pour les messages */
:deep(.p-message) {
    border-radius: 0.5rem;
}

:deep(.p-message.p-message-success) {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #065f46;
}

:deep(.p-message.p-message-error) {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}

:deep(.p-message.p-message-info) {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1e40af;
}

:deep(.p-message.p-message-warn) {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}

/* Améliorations pour mobile */
@media (max-width: 640px) {
    .p-button-rounded {
        width: 2rem !important;
        height: 2rem !important;
    }

    .p-button-rounded .pi {
        font-size: 0.875rem !important;
    }

    :deep(.p-dialog .p-dialog-header) {
        padding: 1rem;
    }

    :deep(.p-dialog .p-dialog-content) {
        padding: 1rem;
    }

    :deep(.p-dialog .p-dialog-footer) {
        padding: 1rem;
    }

    :deep(.p-card .p-card-content) {
        padding: 1rem;
    }
}

/* Dark mode */
:deep(.dark .p-datatable .p-datatable-thead > tr > th) {
    background: #1e293b;
    color: #e2e8f0;
}

:deep(.dark .p-datatable .p-datatable-tbody > tr:hover) {
    background: #1e293b;
}

:deep(.dark .p-card) {
    background: #1f2937;
    border-color: #374151;
}

:deep(.dark .p-dialog .p-dialog-header) {
    background: #1f2937;
    color: #f3f4f6;
    border-color: #374151;
}

:deep(.dark .p-dialog .p-dialog-content) {
    background: #1f2937;
    color: #f3f4f6;
}

:deep(.dark .p-dialog .p-dialog-footer) {
    background: #1f2937;
    border-color: #374151;
}

:deep(.dark .p-inputtext) {
    background: #374151;
    border-color: #4b5563;
    color: #f3f4f6;
}

:deep(.dark .p-inputtext:enabled:focus) {
    border-color: #10b981;
}

:deep(.dark .p-dropdown) {
    background: #374151;
    border-color: #4b5563;
    color: #f3f4f6;
}

:deep(.dark .p-dropdown-panel) {
    background: #374151;
    color: #f3f4f6;
}

:deep(.dark .p-dropdown-panel .p-dropdown-item) {
    color: #f3f4f6;
}

:deep(.dark .p-dropdown-panel .p-dropdown-item.p-highlight) {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

:deep(.dark .p-dropdown-panel .p-dropdown-item:hover) {
    background: #4b5563;
}

:deep(.dark .p-checkbox .p-checkbox-box) {
    background: #374151;
    border-color: #4b5563;
}

:deep(.dark .p-checkbox .p-checkbox-box.p-highlight) {
    border-color: #10b981;
    background: #10b981;
}

:deep(.dark .p-badge) {
    background: #374151;
    color: #f3f4f6;
}

:deep(.dark .p-badge.p-badge-success) {
    background: #059669;
    color: #f3f4f6;
}

:deep(.dark .p-badge.p-badge-info) {
    background: #3b82f6;
    color: #f3f4f6;
}

:deep(.dark .p-badge.p-badge-warning) {
    background: #d97706;
    color: #f3f4f6;
}

:deep(.dark .p-badge.p-badge-danger) {
    background: #dc2626;
    color: #f3f4f6;
}

:deep(.dark .p-tag) {
    background: #374151;
    color: #f3f4f6;
}

:deep(.dark .p-tag.p-tag-success) {
    background: #059669;
    color: #f3f4f6;
}

:deep(.dark .p-tag.p-tag-info) {
    background: #3b82f6;
    color: #f3f4f6;
}

:deep(.dark .p-tag.p-tag-warning) {
    background: #d97706;
    color: #f3f4f6;
}

:deep(.dark .p-tag.p-tag-danger) {
    background: #dc2626;
    color: #f3f4f6;
}
</style>
