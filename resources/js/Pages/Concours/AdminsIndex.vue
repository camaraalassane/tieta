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
import Select from "primevue/select";
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

const form = useForm({ concour_id: null, user_id: null });

const stats = computed(() => ({
    totalConcours: props.concours?.length || 0,
    totalAdmins:
        props.concours?.reduce((acc, c) => acc + (c.admins?.length || 0), 0) ||
        0,
}));

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
                const response = await axios.get(
                    `/api/personnel/by-service/${selectedConcourData.service_id}`,
                );
                filteredPersonnel.value = response.data;
            } else if (props.is_gerant) {
                filteredPersonnel.value = props.personnel || [];
            }
        } catch (error) {
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
                detail: errors.message || "Impossible d'assigner",
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
        accept: () => removeAdmin(concourId, userId),
    });
};

const removeAdmin = (concourId, userId) => {
    useForm({}).delete(
        route("concours-admins.destroy", { concour: concourId, user: userId }),
        {
            onSuccess: () =>
                toast.add({
                    severity: "success",
                    summary: "Succès",
                    detail: "Accès révoqué",
                    life: 3000,
                }),
            onError: (errors) =>
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: errors.message || "Impossible de retirer",
                    life: 5000,
                }),
        },
    );
};

const openModal = (concour = null) => {
    form.concour_id = concour?.id || null;
    selectedConcour.value = concour;
    filteredPersonnel.value = [];
    showAssignModal.value = true;
};

const getInitials = (name) => (name ? name.charAt(0).toUpperCase() : "?");
const canAssign = computed(() => props.is_superadmin || props.is_gerant);
const canRemove = computed(() => props.is_superadmin || props.is_gerant);
</script>

<template>
    <app-layout>
        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <div v-if="is_admin" class="mb-4">
                <Message severity="info" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-info-circle"></i
                        ><span>Mode consultation.</span>
                    </div>
                </Message>
            </div>

            <div class="grid mb-6">
                <div class="col-12">
                    <Card
                        class="header-card shadow-lg border-none overflow-hidden"
                        ><template #content>
                            <div
                                class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4"
                            >
                                <div class="flex align-items-center gap-4">
                                    <div class="header-icon-wrapper">
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
                                                class="pi pi-check-circle theme-text-primary"
                                            ></i
                                            ><span v-if="is_superadmin"
                                                >Gérez les administrateurs par
                                                concours</span
                                            ><span v-else-if="is_gerant"
                                                >Gérez les administrateurs de
                                                votre service</span
                                            ><span v-else
                                                >Consultez les
                                                administrateurs</span
                                            >
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex gap-4 flex-wrap justify-content-center"
                                >
                                    <div class="stat-badge-item">
                                        <Badge
                                            :value="stats.totalConcours"
                                            severity="info"
                                            class="mb-1"
                                        />
                                        <div class="text-xs text-500">
                                            Concours
                                        </div>
                                    </div>
                                    <div class="stat-badge-item">
                                        <Badge
                                            :value="stats.totalAdmins"
                                            severity="success"
                                            class="mb-1"
                                        />
                                        <div class="text-xs text-500">
                                            Affectations
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template></Card
                    >
                </div>
            </div>

            <div class="grid mb-4" v-if="canAssign">
                <div class="col-12">
                    <Card class="shadow-sm"
                        ><template #content>
                            <div class="flex justify-content-end">
                                <Button
                                    label="Nouvelle affectation"
                                    icon="pi pi-user-plus"
                                    class="btn-primary"
                                    @click="openModal()"
                                />
                            </div> </template
                    ></Card>
                </div>
            </div>

            <Card class="shadow-md"
                ><template #content>
                    <DataTable
                        :value="concours"
                        paginator
                        :rows="10"
                        stripedRows
                        showGridlines
                        class="p-datatable-sm"
                    >
                        <Column field="intitule" header="Concours" sortable
                            ><template #body="sp">
                                <div class="flex align-items-center gap-2">
                                    <i
                                        class="pi pi-briefcase theme-text-primary"
                                    ></i
                                    ><span class="font-medium">{{
                                        sp.data.intitule
                                    }}</span
                                    ><Tag
                                        v-if="sp.data.service?.nom"
                                        :value="sp.data.service.nom"
                                        severity="info"
                                        class="text-xs ml-2"
                                    />
                                </div> </template
                        ></Column>
                        <Column header="Administrateurs assignés"
                            ><template #body="sp">
                                <div
                                    class="flex flex-wrap gap-1 align-items-center"
                                >
                                    <span
                                        v-for="admin in sp.data.admins"
                                        :key="admin.id"
                                        class="admin-tag"
                                        ><span class="admin-tag-content"
                                            ><span class="admin-initials">{{
                                                getInitials(admin.name)
                                            }}</span
                                            ><span class="admin-name">{{
                                                admin.name
                                            }}</span
                                            ><i
                                                v-if="canRemove"
                                                class="pi pi-times-circle remove-icon"
                                                @click.stop="
                                                    confirmRemove(
                                                        sp.data.id,
                                                        admin.id,
                                                        admin.name,
                                                    )
                                                "
                                                v-tooltip.top="
                                                    'Retirer l\'accès'
                                                "
                                            ></i></span
                                    ></span>
                                    <Button
                                        v-if="canAssign"
                                        icon="pi pi-plus"
                                        class="p-button-rounded p-button-text p-button-sm compact-add-btn"
                                        @click="openModal(sp.data)"
                                        v-tooltip.top="
                                            'Ajouter un administrateur'
                                        "
                                    />
                                </div> </template
                        ></Column>
                        <Column header="Statut" style="width: 8rem"
                            ><template #body="sp"
                                ><Badge
                                    :value="sp.data.admins?.length || 0"
                                    :severity="
                                        sp.data.admins?.length > 0
                                            ? 'success'
                                            : 'secondary'
                                    "
                                    class="compact-status-badge" /></template
                        ></Column>
                    </DataTable> </template
            ></Card>

            <!-- ⭐ DIALOG AVEC SELECT -->
            <Dialog
                v-if="canAssign"
                v-model:visible="showAssignModal"
                modal
                :style="{ width: '90vw', maxWidth: '500px' }"
                class="p-fluid"
                :closable="true"
                @hide="
                    form.reset();
                    filteredPersonnel = [];
                "
            >
                <template #header>
                    <div class="dialog-header-responsive">
                        <span class="dialog-header-text">
                            {{
                                selectedConcour
                                    ? `Assigner à : ${selectedConcour.intitule}`
                                    : "Nouvelle affectation"
                            }}
                        </span>
                    </div>
                </template>
                <div
                    class="info-banner flex align-items-center gap-3 p-3 border-round-lg mb-4"
                >
                    <div class="banner-icon-wrapper">
                        <i class="pi pi-user-plus text-white text-xl"></i>
                    </div>
                    <div>
                        <h3
                            class="font-semibold text-900 m-0 flex align-items-center gap-2"
                        >
                            <i class="pi pi-user-plus theme-text-primary"></i
                            >Assigner un administrateur
                        </h3>
                        <p class="text-600 text-sm m-0 mt-1">
                            Sélectionnez le concours et l'utilisateur
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <!-- ⭐ SELECT Concours -->
                    <div class="field">
                        <label class="font-medium text-sm text-600"
                            >Concours cible
                            <span class="text-red-500">*</span></label
                        >
                        <!-- ⭐ SELECT Concours -->
                        <Select
                            v-model="form.concour_id"
                            :options="concours"
                            optionLabel="intitule"
                            optionValue="id"
                            placeholder="Sélectionner le concours"
                            filter
                            :class="{ 'p-invalid': form.errors.concour_id }"
                            class="w-full"
                            panelClass="responsive-select-panel"
                            :pt="{
                                label: {
                                    style: 'white-space: normal !important; word-break: break-word !important; overflow-wrap: break-word !important; line-height: 1.3 !important; overflow: visible !important; text-overflow: clip !important; height: auto !important; min-height: 42px !important; display: flex !important; align-items: center !important; max-width: 100% !important;',
                                },
                                item: {
                                    style: 'white-space: normal !important; word-break: break-word !important; overflow-wrap: break-word !important; line-height: 1.3 !important; overflow: visible !important; text-overflow: clip !important; height: auto !important; min-height: 38px !important; display: flex !important; align-items: center !important; max-width: 100% !important; box-sizing: border-box !important;',
                                },
                                panel: {
                                    style: 'min-width: 100% !important; max-width: 100% !important; width: 100% !important; left: 0 !important; right: 0 !important; box-sizing: border-box !important; overflow: hidden !important;',
                                },
                                listContainer: {
                                    style: 'max-height: 220px !important; overflow-x: hidden !important; overflow-y: auto !important; width: 100% !important;',
                                },
                            }"
                        />
                        <small v-if="form.errors.concour_id" class="p-error">{{
                            form.errors.concour_id
                        }}</small>
                    </div>

                    <!-- ⭐ SELECT Administrateur -->
                    <div class="field">
                        <label class="font-medium text-sm text-600"
                            >Administrateur
                            <span class="text-red-500">*</span></label
                        >
                        <!-- ⭐ SELECT Administrateur -->
                        <Select
                            v-model="form.user_id"
                            :options="filteredPersonnel"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Choisir l'administrateur"
                            filter
                            :disabled="!form.concour_id || isLoadingPersonnel"
                            :loading="isLoadingPersonnel"
                            :class="{ 'p-invalid': form.errors.user_id }"
                            class="w-full"
                            panelClass="responsive-select-panel"
                            :pt="{
                                label: {
                                    style: 'white-space: normal !important; word-break: break-word !important; overflow-wrap: break-word !important; line-height: 1.3 !important; overflow: visible !important; text-overflow: clip !important; height: auto !important; min-height: 42px !important; display: flex !important; align-items: center !important;',
                                },
                                item: {
                                    style: 'white-space: normal !important; word-break: break-word !important; overflow-wrap: break-word !important; line-height: 1.3 !important; overflow: visible !important; text-overflow: clip !important; height: auto !important; min-height: 38px !important; display: flex !important; align-items: center !important;',
                                },
                                panel: {
                                    style: 'max-width: 100% !important; width: 100% !important; min-width: unset !important; left: 0 !important; box-sizing: border-box !important; overflow: hidden !important;',
                                },
                                listContainer: {
                                    style: 'max-height: 220px !important; overflow-x: hidden !important; overflow-y: auto !important;',
                                },
                            }"
                        >
                            v-model="form.user_id" :options="filteredPersonnel"
                            optionLabel="name" optionValue="id"
                            placeholder="Choisir l'administrateur" filter
                            :disabled="!form.concour_id || isLoadingPersonnel"
                            :loading="isLoadingPersonnel" :class="{ 'p-invalid':
                            form.errors.user_id }" class="w-full"
                            panelClass="responsive-select-panel" :pt="{ label: {
                            class: 'whitespace-normal break-words
                            leading-relaxed', }, item: { class:
                            'whitespace-normal break-words', }, }" >
                            <template #option="sp">
                                <div class="flex align-items-center gap-2">
                                    <Avatar
                                        :label="getInitials(sp.option.name)"
                                        size="small"
                                        shape="circle"
                                        class="bg-emerald-500 text-white flex-shrink-0"
                                    />
                                    <div class="flex flex-column min-w-0">
                                        <span
                                            class="font-medium text-sm truncate"
                                            >{{ sp.option.name }}
                                            {{ sp.option.prenom || "" }}</span
                                        >
                                        <small
                                            class="text-400 text-xs truncate"
                                            >{{ sp.option.email }}</small
                                        >
                                    </div>
                                </div>
                            </template>
                        </Select>
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
                            icon="pi pi-times"
                            outlined
                            class="btn-outlined-primary"
                            @click="showAssignModal = false"
                        />
                        <Button
                            type="submit"
                            label="Confirmer l'accès"
                            icon="pi pi-check"
                            :loading="form.processing"
                            class="btn-primary"
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
    background-color: var(--color-primary-light);
    color: var(--color-primary-dark);
    border-radius: 30px;
    padding: 2px;
    font-size: 0.75rem;
    margin: 1px;
    border: 1px solid var(--color-primary-light);
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
    background-color: var(--color-primary);
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
    color: var(--color-primary-dark);
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
    color: var(--color-primary) !important;
}
.compact-add-btn:hover {
    background-color: var(--color-primary-light) !important;
}
.compact-add-btn .pi {
    font-size: 0.625rem !important;
}
.compact-status-badge {
    font-size: 0.7rem !important;
    padding: 0.2rem 0.5rem !important;
}
.info-banner {
    background: var(--color-primary-light);
}
.dark .info-banner {
    background: rgba(16, 185, 129, 0.15);
}
.banner-icon-wrapper {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--color-primary);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.theme-text-primary {
    color: var(--color-primary) !important;
}
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
        rgba(16, 185, 129, 0.15),
        #1f2937
    ) !important;
}
.header-icon-wrapper {
    width: 4rem;
    height: 4rem;
    background: var(--color-primary);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.stat-badge-item {
    text-align: center;
    padding: 0.75rem 1rem;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}
.dark .stat-badge-item {
    background: #1f2937;
}
.stat-badge-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.btn-primary {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.btn-primary:focus {
    box-shadow: 0 0 0 3px var(--color-primary-light) !important;
}
.btn-primary:disabled {
    background: #9ca3af !important;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
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
:deep(.p-dialog .btn-primary),
:deep(.p-dialog .btn-outlined-primary) {
    min-width: 120px;
}

/* ============================================ */
/* ⭐ SELECT RESPONSIVE - IDENTIQUE À POSTULER */
/* ============================================ */
:deep(.p-select) {
    width: 100% !important;
}

:deep(.p-select .p-select-label) {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.4 !important;
    padding: 0.75rem !important;
    font-size: 0.875rem !important;
}

@media (max-width: 640px) {
    :deep(.p-select .p-select-label) {
        font-size: 0.8rem !important;
        padding: 0.65rem 0.75rem !important;
    }
    :deep(.p-select .p-select-dropdown) {
        width: 2.5rem !important;
    }
}

/* Panel - ciblé par panelClass */
:deep(.responsive-select-panel) {
    max-width: 100vw !important;
}

@media (max-width: 640px) {
    :deep(.responsive-select-panel) {
        width: 92vw !important;
        left: 4vw !important;
        max-height: 65vh !important;
    }
}

@media (min-width: 641px) {
    :deep(.responsive-select-panel) {
        max-width: 500px !important;
        min-width: 280px !important;
    }
}

/* Items */
:deep(.responsive-select-panel .p-select-item) {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
}

@media (max-width: 640px) {
    :deep(.responsive-select-panel .p-select-item) {
        padding: 0.875rem 1rem !important;
        font-size: 0.8rem !important;
    }
}

:deep(.responsive-select-panel .p-select-empty-message) {
    padding: 1.5rem !important;
    text-align: center !important;
}
:deep(.p-tag.p-tag-success) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}
:deep(.p-badge.p-badge-success) {
    background: var(--color-primary) !important;
}
:deep(.p-badge.p-badge-info) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
    font-weight: 600;
}
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--color-primary-light) !important;
}
:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}
:deep(.p-datatable .p-datatable-thead > tr > th) {
    border-bottom: 2px solid var(--color-primary-light);
}
:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}
:deep(.p-dialog .p-dialog-header) {
    border-bottom: 2px solid var(--color-primary-light);
}
:deep(.p-message.p-message-success) {
    border-left: 6px solid var(--color-primary) !important;
}
:deep(.p-message.p-message-success .p-message-icon) {
    color: var(--color-primary) !important;
}
:deep(.p-toast .p-toast-message.p-toast-message-success) {
    border-left: 6px solid var(--color-primary) !important;
}
:deep(.p-toast .p-toast-message.p-toast-message-success .p-toast-message-icon) {
    color: var(--color-primary) !important;
}
.dark .admin-tag {
    background-color: rgba(16, 185, 129, 0.2);
    color: var(--color-primary-light);
    border-color: var(--color-primary);
}
.dark .admin-initials {
    background-color: var(--color-primary);
    color: white;
}
.dark .remove-icon {
    color: var(--color-primary-light);
}
.dark .compact-add-btn {
    color: var(--color-primary-light) !important;
}
.dark .compact-add-btn:hover {
    background-color: rgba(16, 185, 129, 0.2) !important;
}
:deep(.dark .p-datatable .p-datatable-thead > tr > th) {
    background: #1f2937;
    color: var(--color-primary-light);
    border-bottom-color: var(--color-primary);
}
:deep(.dark .p-dialog .p-dialog-header) {
    background: #1f2937;
    color: #f3f4f6;
    border-color: rgba(16, 185, 129, 0.2);
}
:deep(.dark .p-dialog .p-dialog-content) {
    background: #1f2937;
    color: #f3f4f6;
}
:deep(.dark .p-inputtext) {
    background: #374151;
    border-color: #4b5563;
    color: #f3f4f6;
}
:deep(.dark .p-inputtext:focus) {
    border-color: var(--color-primary) !important;
}
:deep(.dark .p-select) {
    background: #374151;
    border-color: #4b5563;
    color: #f3f4f6;
}
:deep(.dark .p-select-panel) {
    background: #374151;
    color: #f3f4f6;
}
:deep(.dark .p-select-panel .p-select-item) {
    color: #f3f4f6;
}
:deep(.dark .p-select-panel .p-select-item.p-highlight) {
    background: rgba(16, 185, 129, 0.2) !important;
    color: var(--color-primary-light) !important;
}
:deep(.dark .p-select-panel .p-select-item:hover) {
    background: rgba(16, 185, 129, 0.1) !important;
}
:deep(.dark .p-checkbox .p-checkbox-box) {
    background: #374151;
    border-color: #4b5563;
}
:deep(.dark .p-checkbox .p-checkbox-box.p-highlight) {
    border-color: var(--color-primary) !important;
    background: var(--color-primary) !important;
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
.p-card {
    animation: fadeInUp 0.3s ease-out;
}
.header-card {
    animation: slideDown 0.4s ease-out;
}
@media (max-width: 640px) {
    .header-icon-wrapper {
        width: 3rem;
        height: 3rem;
    }
    .header-icon-wrapper i {
        font-size: 1.5rem !important;
    }
    .stat-badge-item {
        padding: 0.5rem 0.75rem;
    }
}
/* ⭐ Header Dialog responsive */
.dialog-header-responsive {
    display: flex;
    align-items: center;
    width: 100%;
    padding-right: 2.5rem; /* espace pour le bouton fermer */
}

.dialog-header-text {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.3 !important;
    font-size: 1.1rem !important;
    font-weight: 600 !important;
}
</style>
