<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Tag from "primevue/tag";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import Avatar from "primevue/avatar";
import Badge from "primevue/badge";
import Message from "primevue/message";
import { useToast } from "primevue/usetoast";
import ConfirmDialog from "primevue/confirmdialog";
import { useConfirm } from "primevue/useconfirm";
import axios from "axios";

const props = defineProps({
    concours: Array,
    personnel: Array,
    servicesList: Array,
    user_role: String,
    is_superadmin: { type: Boolean, default: false },
    is_gerant: { type: Boolean, default: false },
    is_admin: { type: Boolean, default: false },
});

const toast = useToast();
const confirm = useConfirm();

const showAssignModal = ref(false);
const selectedConcour = ref(null);
const filteredPersonnel = ref([]);
const isLoadingPersonnel = ref(false);

const form = useForm({
    concour_id: null,
    user_id: null,
});

// ⭐ Filtrer les concours pour l'admin (déjà fait dans le contrôleur)
const stats = computed(() => {
    const totalConcours = props.concours?.length || 0;
    const totalAdmins =
        props.concours?.reduce((acc, c) => acc + (c.admins?.length || 0), 0) ||
        0;
    return { totalConcours, totalAdmins };
});

// ⭐ Quand le concours change, charger le personnel correspondant
watch(
    () => form.concour_id,
    async (newConcourId) => {
        if (!newConcourId) {
            filteredPersonnel.value = [];
            return;
        }

        const selectedConcourData = props.concours?.find(
            (c) => c.id === newConcourId,
        );
        if (!selectedConcourData) return;

        isLoadingPersonnel.value = true;

        try {
            if (props.is_superadmin) {
                // Superadmin : filtrer le personnel par service
                const serviceId = selectedConcourData.service_id;
                const response = await axios.get(
                    `/api/personnel/by-service/${serviceId}`,
                );
                filteredPersonnel.value = response.data;
            } else if (props.is_gerant) {
                // Gérant : personnel de son service (déjà chargé)
                filteredPersonnel.value = props.personnel || [];
            }
        } catch (error) {
            console.error("Erreur chargement personnel:", error);
            filteredPersonnel.value = [];
        } finally {
            isLoadingPersonnel.value = false;
        }
    },
);

const submit = () => {
    form.concour_id = form.concour_id ? Number(form.concour_id) : null;
    form.user_id = form.user_id ? Number(form.user_id) : null;

    form.post(route("concours-admins.store"), {
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Administrateur assigné avec succès",
                life: 3000,
            });
            showAssignModal.value = false;
            form.reset();
            filteredPersonnel.value = [];
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail:
                    errors.message || "Impossible d'assigner l'administrateur",
                life: 5000,
            });
        },
    });
};

const confirmRemove = (concourId, userId, userName) => {
    confirm.require({
        message: `Voulez-vous vraiment retirer l'accès à ${userName} ?`,
        header: "Confirmation",
        icon: "pi pi-exclamation-triangle",
        acceptClass: "p-button-danger",
        accept: () => {
            removeAdmin(concourId, userId);
        },
    });
};

const removeAdmin = (concourId, userId) => {
    const deleteForm = useForm({});
    deleteForm.delete(
        route("concours-admins.destroy", { concour: concourId, user: userId }),
        {
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Accès révoqué avec succès",
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: errors.message || "Impossible de retirer l'accès",
                    life: 5000,
                });
            },
        },
    );
};

const openModal = (concour = null) => {
    form.concour_id = concour?.id || null;
    selectedConcour.value = concour;
    filteredPersonnel.value = [];
    showAssignModal.value = true;
};

const getInitials = (name) => {
    return name ? name.charAt(0).toUpperCase() : "?";
};

const canAssign = computed(() => {
    return props.is_superadmin || props.is_gerant;
});

const canRemove = computed(() => {
    return props.is_superadmin || props.is_gerant;
});
</script>

<template>
    <app-layout>
        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <!-- Message pour les admins -->
            <div v-if="is_admin" class="mb-4">
                <Message severity="info" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-info-circle"></i>
                        <span
                            >Vous êtes en mode consultation. Seul un super
                            administrateur ou un gérant peut modifier les
                            affectations.</span
                        >
                    </div>
                </Message>
            </div>

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
                                            class="pi pi-shield text-white text-3xl"
                                        ></i>
                                    </div>
                                    <div>
                                        <h1
                                            class="text-3xl font-bold text-900 m-0 mb-2"
                                        >
                                            Gestion des accès
                                        </h1>
                                        <p
                                            class="text-600 m-0 flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-check-circle text-emerald-500"
                                            ></i>
                                            <span v-if="is_superadmin"
                                                >Gérez les administrateurs par
                                                concours</span
                                            >
                                            <span v-else-if="is_gerant"
                                                >Gérez les administrateurs des
                                                concours de votre service</span
                                            >
                                            <span v-else
                                                >Consultez les administrateurs
                                                assignés à vos concours</span
                                            >
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="flex gap-4 flex-wrap justify-content-center"
                                >
                                    <div
                                        class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg"
                                    >
                                        <Badge
                                            :value="stats.totalConcours"
                                            severity="info"
                                            class="mb-1"
                                        ></Badge>
                                        <div class="text-xs text-500">
                                            Concours
                                        </div>
                                    </div>
                                    <div
                                        class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg"
                                    >
                                        <Badge
                                            :value="stats.totalAdmins"
                                            severity="success"
                                            class="mb-1"
                                        ></Badge>
                                        <div class="text-xs text-500">
                                            Affectations
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Barre d'actions -->
            <div class="grid mb-4" v-if="canAssign">
                <div class="col-12">
                    <Card class="shadow-sm">
                        <template #content>
                            <div class="flex justify-content-end">
                                <Button
                                    label="Nouvelle affectation"
                                    icon="pi pi-user-plus"
                                    class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                                    @click="openModal()"
                                />
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Tableau des affectations -->
            <Card class="shadow-md">
                <template #content>
                    <DataTable
                        :value="concours"
                        paginator
                        :rows="10"
                        responsiveLayout="scroll"
                        stripedRows
                        showGridlines
                        class="p-datatable-sm"
                    >
                        <Column field="intitule" header="Concours" sortable>
                            <template #body="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <i
                                        class="pi pi-briefcase text-emerald-500"
                                    ></i>
                                    <span class="font-medium">{{
                                        slotProps.data.intitule
                                    }}</span>
                                    <Tag
                                        v-if="slotProps.data.service?.nom"
                                        :value="slotProps.data.service.nom"
                                        severity="info"
                                        class="text-xs ml-2"
                                    />
                                </div>
                            </template>
                        </Column>

                        <Column header="Administrateurs assignés">
                            <template #body="slotProps">
                                <div
                                    class="flex flex-wrap gap-1 align-items-center"
                                >
                                    <span
                                        v-for="admin in slotProps.data.admins"
                                        :key="admin.id"
                                        class="admin-tag"
                                    >
                                        <span class="admin-tag-content">
                                            <span class="admin-initials">{{
                                                getInitials(admin.name)
                                            }}</span>
                                            <span class="admin-name">{{
                                                admin.name
                                            }}</span>
                                            <i
                                                v-if="canRemove"
                                                class="pi pi-times-circle remove-icon"
                                                @click.stop="
                                                    confirmRemove(
                                                        slotProps.data.id,
                                                        admin.id,
                                                        admin.name,
                                                    )
                                                "
                                                v-tooltip.top="
                                                    'Retirer l\'accès'
                                                "
                                            ></i>
                                        </span>
                                    </span>

                                    <Button
                                        v-if="canAssign"
                                        icon="pi pi-plus"
                                        class="p-button-rounded p-button-text p-button-sm compact-add-btn"
                                        @click="openModal(slotProps.data)"
                                        v-tooltip.top="
                                            'Ajouter un administrateur'
                                        "
                                    />
                                </div>
                            </template>
                        </Column>

                        <Column header="Statut" style="width: 8rem">
                            <template #body="slotProps">
                                <Badge
                                    :value="slotProps.data.admins?.length || 0"
                                    :severity="
                                        slotProps.data.admins?.length > 0
                                            ? 'success'
                                            : 'secondary'
                                    "
                                    class="compact-status-badge"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Modal d'assignation -->
            <Dialog
                v-if="canAssign"
                v-model:visible="showAssignModal"
                modal
                :header="
                    selectedConcour
                        ? `Assigner à : ${selectedConcour.intitule}`
                        : 'Nouvelle affectation'
                "
                :style="{ width: '500px' }"
                class="p-fluid"
                :closable="true"
                @hide="
                    form.reset();
                    filteredPersonnel = [];
                "
            >
                <div
                    class="flex align-items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border-round-lg mb-4"
                >
                    <div
                        class="w-10 h-10 bg-emerald-500 border-round-lg flex align-items-center justify-center"
                    >
                        <i class="pi pi-user-plus text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-900 m-0">
                            Assigner un administrateur
                        </h3>
                        <p class="text-600 text-sm m-0">
                            Sélectionnez le concours et l'utilisateur
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div class="field">
                        <label class="font-medium text-sm text-600"
                            >Concours cible
                            <span class="text-red-500">*</span></label
                        >
                        <Dropdown
                            v-model="form.concour_id"
                            :options="concours"
                            optionLabel="intitule"
                            optionValue="id"
                            placeholder="Sélectionner le concours"
                            filter
                            :class="{ 'p-invalid': form.errors.concour_id }"
                        />
                        <small v-if="form.errors.concour_id" class="p-error">{{
                            form.errors.concour_id
                        }}</small>
                    </div>

                    <div class="field">
                        <label class="font-medium text-sm text-600"
                            >Administrateur
                            <span class="text-red-500">*</span></label
                        >
                        <Dropdown
                            v-model="form.user_id"
                            :options="filteredPersonnel"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Choisir l'administrateur"
                            filter
                            :disabled="!form.concour_id || isLoadingPersonnel"
                            :loading="isLoadingPersonnel"
                            :class="{ 'p-invalid': form.errors.user_id }"
                        >
                            <template #option="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <Avatar
                                        :label="
                                            getInitials(slotProps.option.name)
                                        "
                                        size="small"
                                        shape="circle"
                                        class="bg-emerald-500 text-white"
                                    />
                                    <span
                                        >{{ slotProps.option.name }}
                                        {{
                                            slotProps.option.prenom || ""
                                        }}</span
                                    >
                                    <small class="text-400 ml-auto">{{
                                        slotProps.option.email
                                    }}</small>
                                </div>
                            </template>
                        </Dropdown>
                        <small v-if="form.errors.user_id" class="p-error">{{
                            form.errors.user_id
                        }}</small>
                        <small v-else-if="!form.concour_id" class="text-400"
                            >Sélectionnez d'abord un concours</small
                        >
                    </div>

                    <div class="flex justify-content-end gap-2">
                        <Button
                            type="button"
                            label="Annuler"
                            severity="secondary"
                            icon="pi pi-times"
                            @click="showAssignModal = false"
                        />
                        <Button
                            type="submit"
                            label="Confirmer l'accès"
                            icon="pi pi-check"
                            :loading="form.processing"
                            class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                        />
                    </div>
                </form>
            </Dialog>

            <ConfirmDialog />
        </div>
    </app-layout>
</template>

<style scoped>
.field {
    @apply flex flex-col gap-1;
}

.p-error {
    @apply text-red-500 text-xs mt-1 block;
}

.admin-tag {
    display: inline-flex;
    align-items: center;
    background-color: #d1fae5;
    color: #065f46;
    border-radius: 30px;
    padding: 2px 2px 2px 2px;
    font-size: 0.75rem;
    margin: 1px;
    border: 1px solid #a7f3d0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.admin-tag-content {
    display: flex;
    align-items: center;
    gap: 2px;
}

.admin-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background-color: #10b981;
    color: white;
    border-radius: 50%;
    font-size: 0.625rem;
    font-weight: bold;
    margin-right: 2px;
}

.admin-name {
    max-width: 100px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0 4px;
}

.remove-icon {
    font-size: 0.75rem !important;
    opacity: 0.6;
    transition: all 0.2s;
    color: #065f46;
    padding: 2px;
    border-radius: 50%;
    cursor: pointer;
}

.remove-icon:hover {
    opacity: 1;
    color: #dc2626 !important;
    background-color: rgba(220, 38, 38, 0.1);
}

.compact-add-btn {
    width: 24px !important;
    height: 24px !important;
    font-size: 0.75rem !important;
    color: #10b981 !important;
}

.compact-add-btn:hover {
    background-color: #d1fae5 !important;
}

.compact-add-btn .pi {
    font-size: 0.625rem !important;
}

.compact-status-badge {
    font-size: 0.7rem !important;
    padding: 0.2rem 0.5rem !important;
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

/* Dark mode */
.dark .admin-tag {
    background-color: #065f46;
    color: #d1fae5;
    border-color: #10b981;
}

.dark .admin-initials {
    background-color: #34d399;
    color: #064e3b;
}

.dark .remove-icon {
    color: #d1fae5;
}

.dark .compact-add-btn {
    color: #34d399;
}

.dark .compact-add-btn:hover {
    background-color: #065f46;
}

:deep(.p-dropdown:focus),
:deep(.p-button:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}
</style>
