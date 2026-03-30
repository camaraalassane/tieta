<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import { useToast } from 'primevue/usetoast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";

const props = defineProps({
    concours: Array,
    users: Array,
});

const toast = useToast();
const confirm = useConfirm();

const showAssignModal = ref(false);
const selectedConcour = ref(null);

const form = useForm({
    concour_id: null,
    user_id: null,
});

// Statistiques
const stats = computed(() => {
    const totalConcours = props.concours?.length || 0;
    const totalAdmins = props.concours?.reduce((acc, c) => acc + (c.admins?.length || 0), 0) || 0;
    return { totalConcours, totalAdmins };
});

const submit = () => {
    form.concour_id = form.concour_id ? Number(form.concour_id) : null;
    form.user_id = form.user_id ? Number(form.user_id) : null;
    
    form.post(route('concours-admins.store'), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Succès',
                detail: 'Administrateur assigné avec succès',
                life: 3000
            });
            showAssignModal.value = false;
            form.reset('user_id');
        },
        onError: (errors) => {
            toast.add({
                severity: 'error',
                summary: 'Erreur',
                detail: 'Impossible d\'assigner l\'administrateur',
                life: 5000
            });
        }
    });
};

const confirmRemove = (concourId, userId, userName) => {
    confirm.require({
        message: `Voulez-vous vraiment retirer l'accès à ${userName} ?`,
        header: 'Confirmation',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            removeAdmin(concourId, userId);
        }
    });
};

const removeAdmin = (concourId, userId) => {
    const deleteForm = useForm({});
    deleteForm.delete(route('concours-admins.destroy', { 
        concour: Number(concourId), 
        user: Number(userId) 
    }), {
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Succès',
                detail: 'Accès révoqué avec succès',
                life: 3000
            });
        }
    });
};

const openModal = (concour = null) => {
    form.concour_id = concour?.id || null;
    selectedConcour.value = concour;
    showAssignModal.value = true;
};

const getInitials = (name) => {
    return name ? name.charAt(0).toUpperCase() : '?';
};
</script>

<template>
    <app-layout>
        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <!-- En-tête avec statistiques -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-900">
                        <template #content>
                            <div class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4">
                                <div class="flex align-items-center gap-4">
                                    <div class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-center">
                                        <i class="pi pi-shield text-white text-3xl"></i>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold text-900 m-0 mb-2">Gestion des accès</h1>
                                        <p class="text-600 m-0 flex align-items-center gap-2">
                                            <i class="pi pi-check-circle text-emerald-500"></i>
                                            Gérez les administrateurs par concours
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-4 flex-wrap justify-content-center">
                                    <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.totalConcours" severity="info" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Concours</div>
                                    </div>
                                    <div class="text-center px-4 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.totalAdmins" severity="success" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Affections</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Barre d'actions -->
            <div class="grid mb-4">
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
                        :value="props.concours" 
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
                                    <i class="pi pi-briefcase text-emerald-500"></i>
                                    <span class="font-medium">{{ slotProps.data.intitule }}</span>
                                </div>
                            </template>
                        </Column>
                        
                        <!-- Colonne des administrateurs avec badges compacts - Version verte -->
                        <Column header="Administrateurs assignés">
                            <template #body="slotProps">
                                <div class="flex flex-wrap gap-1 align-items-center">
                                    <!-- Affichage des admins - Tags verts compacts -->
                                    <span 
                                        v-for="admin in slotProps.data.admins" 
                                        :key="admin.id" 
                                        class="admin-tag"
                                    >
                                        <span class="admin-tag-content">
                                            <span class="admin-initials">{{ getInitials(admin.name) }}</span>
                                            <span class="admin-name">{{ admin.name }}</span>
                                            <i 
                                                class="pi pi-times-circle remove-icon" 
                                                @click.stop="confirmRemove(slotProps.data.id, admin.id, admin.name)"
                                                v-tooltip.top="'Retirer l\'accès'"
                                            ></i>
                                        </span>
                                    </span>
                                    
                                    <!-- Bouton d'ajout rapide compact -->
                                    <Button 
                                        icon="pi pi-plus" 
                                        class="p-button-rounded p-button-text p-button-sm compact-add-btn" 
                                        @click="openModal(slotProps.data)" 
                                        v-tooltip.top="'Ajouter un administrateur'"
                                    />
                                </div>
                            </template>
                        </Column>

                        <!-- Colonne de statut compacte -->
                        <Column header="Statut" style="width: 8rem">
                            <template #body="slotProps">
                                <Badge 
                                    :value="slotProps.data.admins?.length || 0" 
                                    :severity="slotProps.data.admins?.length > 0 ? 'success' : 'secondary'"
                                    class="compact-status-badge"
                                />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Fenêtre Modale d'Assignation -->
            <Dialog 
                v-model:visible="showAssignModal" 
                modal
                :header="selectedConcour ? `Assigner à : ${selectedConcour.intitule}` : 'Nouvelle affectation'" 
                :style="{ width: '500px' }" 
                class="p-fluid"
                :closable="true"
                @hide="form.reset()"
            >
                <div class="flex align-items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border-round-lg mb-4">
                    <div class="w-10 h-10 bg-emerald-500 border-round-lg flex align-items-center justify-center">
                        <i class="pi pi-user-plus text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-900 m-0">Assigner un administrateur</h3>
                        <p class="text-600 text-sm m-0">Sélectionnez le concours et l'utilisateur</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="flex flex-col gap-4">
                    <div class="field">
                        <label class="font-medium text-sm text-600">Concours cible <span class="text-red-500">*</span></label>
                        <Dropdown 
                            v-model="form.concour_id" 
                            :options="props.concours" 
                            optionLabel="intitule" 
                            optionValue="id" 
                            placeholder="Sélectionner le concours"
                            filter
                            :class="{ 'p-invalid': form.errors.concour_id }"
                        />
                        <small v-if="form.errors.concour_id" class="p-error">{{ form.errors.concour_id }}</small>
                    </div>

                    <div class="field">
                        <label class="font-medium text-sm text-600">Administrateur <span class="text-red-500">*</span></label>
                        <Dropdown 
                            v-model="form.user_id" 
                            :options="props.users" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Choisir l'utilisateur"
                            filter
                            :class="{ 'p-invalid': form.errors.user_id }"
                        >
                            <template #option="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <Avatar 
                                        :label="getInitials(slotProps.option.name)" 
                                        size="small" 
                                        shape="circle" 
                                        class="bg-emerald-500 text-white"
                                    />
                                    <span>{{ slotProps.option.name }}</span>
                                    <small class="text-400 ml-auto">{{ slotProps.option.email }}</small>
                                </div>
                            </template>
                        </Dropdown>
                        <small v-if="form.errors.user_id" class="p-error">{{ form.errors.user_id }}</small>
                    </div>

                    <Divider />

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

            <!-- Confirmation Dialog -->
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

/* ⭐ Tags administrateurs version verte */
.admin-tag {
    display: inline-flex;
    align-items: center;
    background-color: #d1fae5;  /* Fond vert clair emerald-100 */
    color: #065f46;              /* Texte vert foncé emerald-800 */
    border-radius: 30px;
    padding: 2px 2px 2px 2px;
    font-size: 0.75rem;
    margin: 1px;
    border: 1px solid #a7f3d0;   /* Bordure verte emerald-200 */
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
    background-color: #10b981;  /* Fond vert emerald-500 */
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
    color: #065f46;  /* Vert foncé pour l'icône */
    padding: 2px;
    border-radius: 50%;
}

.remove-icon:hover {
    opacity: 1;
    color: #dc2626 !important;  /* Rouge au survol */
    background-color: rgba(220, 38, 38, 0.1);
}

/* Bouton d'ajout compact */
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

/* Badge de statut compact */
.compact-status-badge {
    font-size: 0.7rem !important;
    padding: 0.2rem 0.5rem !important;
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

/* Dark mode */
.dark .admin-tag {
    background-color: #065f46 !important;  /* Vert foncé en dark mode */
    color: #d1fae5 !important;              /* Texte clair */
    border-color: #10b981 !important;
}

.dark .admin-initials {
    background-color: #34d399 !important;
    color: #064e3b !important;
}

.dark .remove-icon {
    color: #d1fae5 !important;
}

.dark .compact-add-btn {
    color: #34d399 !important;
}

.dark .compact-add-btn:hover {
    background-color: #065f46 !important;
}

/* Amélioration du focus */
:deep(.p-dropdown:focus),
:deep(.p-button:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}
</style>