<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Create from "@/Pages/Concours/Create.vue";
import Edit from "@/Pages/Concours/Edit.vue";
import { usePage, useForm, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref, watch, computed } from "vue";
import { cloneDeep, debounce, pickBy } from "lodash";
import { loadToast } from '@/composables/loadToast';

// ⭐ Les composants PrimeVue sont automatiquement résolus, pas besoin de les importer
// import Badge from 'primevue/badge';
// import Card from 'primevue/card';
// import Button from 'primevue/button';
// ... etc

const openAvis = (url) => {
    if (url) {
        window.open(url, '_blank');
    }
};

const props = defineProps({
    title: String,
    filters: Object,
    concours: Array,
    perPage: Number,
    total: Number,
});

const can = (permissions) => {
    const auth = usePage().props.auth;
    const user = auth?.user;

    if (!user) return false;
    if (user.is_superadmin) return true;

    const userPermissions = user.permissions || [];
    const searchPermissions = Array.isArray(permissions) ? permissions : [permissions];

    return searchPermissions.some(p => userPermissions.includes(p));
};

loadToast();

const deleteDialog = ref(false);
const form = useForm({});
const menuRef = ref();
const selectedConcours = ref([]);

const data = reactive({
    params: {
        search: props.filters?.search || '',
        field: props.filters?.field || 'created_at',
        order: props.filters?.order || 'desc',
    },
    createOpen: false,
    editOpen: false,
    concours: {},
});

// Statistiques calculées
const stats = computed(() => {
    const actifs = props.concours?.filter(c => c.statut === 'Actif').length || 0;
    const inactifs = props.concours?.filter(c => c.statut !== 'Actif').length || 0;
    return {
        total: props.total || 0,
        actifs,
        inactifs,
        avecAvis: props.concours?.filter(c => c.avis).length || 0
    };
});

const deleteData = () => {
    deleteDialog.value = false;
    if (data.concours?.id) {
        form.delete(route("concours.destroy", data.concours.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                data.concours = {};
            }
        });
    }
};

const onPageChange = (event) => {
    router.get(route('concours.index'), { 
        page: event.page + 1,
        ...data.params 
    }, { preserveState: true });
};

watch(
    () => cloneDeep(data.params),
    debounce(() => {
        let params = pickBy(data.params);
        router.get(route("concours.index"), params, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        });
    }, 150)
);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const items = ref([
    {
        label: 'Modifier',
        icon: 'pi pi-pencil',
        command: () => { data.editOpen = true; }
    },
    {
        label: 'Supprimer',
        icon: 'pi pi-trash',
        command: () => { deleteDialog.value = true; }
    }
]);
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
                                        <i class="pi pi-briefcase text-white text-3xl"></i>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl font-bold text-900 m-0 mb-2">Gestion des Concours</h1>
                                        <p class="text-600 m-0 flex align-items-center gap-2">
                                            <i class="pi pi-check-circle text-emerald-500"></i>
                                            Gérez l'ensemble des concours de la plateforme
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex gap-4 flex-wrap justify-content-center">
                                    <div class="text-center px-3 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.total" severity="info" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Total</div>
                                    </div>
                                    <div class="text-center px-3 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.actifs" severity="success" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Actifs</div>
                                    </div>
                                    <div class="text-center px-3 py-2 bg-white dark:bg-gray-800 border-round-lg">
                                        <Badge :value="stats.avecAvis" severity="warn" class="mb-1"></Badge>
                                        <div class="text-xs text-500">Avis</div>
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
                            <div class="flex flex-column md:flex-row align-items-center justify-content-between gap-4">
                                <!-- Recherche -->
                                <IconField iconPosition="left" class="flex-1 w-full">
                                    <InputIcon class="pi pi-search text-emerald-500" />
                                    <InputText
                                        v-model="data.params.search"
                                        placeholder="Rechercher un concours..."
                                        class="w-full border-round-xl"
                                    />
                                </IconField>

                                <!-- Actions -->
                                <div class="flex gap-2 w-full md:w-auto">
                                    <Button 
                                        v-if="can('create concours')"
                                        label="Nouveau concours"
                                        icon="pi pi-plus"
                                        class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                                        @click="data.createOpen = true"
                                    />
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Tableau des concours -->
            <Card class="shadow-md">
                <template #content>
                    <DataTable 
                        :value="props.concours" 
                        paginator 
                        :rows="10" 
                        :totalRecords="total" 
                        @page="onPageChange"
                        tableStyle="min-width: 60rem"
                        class="p-datatable-sm"
                        stripedRows
                        showGridlines
                        v-model:selection="selectedConcours"
                        :rowsPerPageOptions="[10, 20, 50]"
                        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        currentPageReportTemplate="Affichage de {first} à {last} sur {totalRecords} concours"
                    >
                        <template #empty>
                            <div class="text-center py-8">
                                <i class="pi pi-inbox text-4xl text-300 mb-3"></i>
                                <p class="text-600">Aucun concours trouvé</p>
                            </div>
                        </template>
                        
                        <template #loading>
                            <div class="text-center py-4">
                                <i class="pi pi-spinner pi-spin text-2xl text-emerald-500"></i>
                                <p class="text-600 mt-2">Chargement des données...</p>
                            </div>
                        </template>

                        <Column header="#" headerStyle="width: 5%">
                            <template #body="slotProps">
                                <span class="text-500 text-sm">{{ slotProps.index + 1 }}</span>
                            </template>
                        </Column>

                        <Column field="intitule" header="Intitulé" sortable>
                            <template #body="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <i class="pi pi-briefcase text-emerald-500"></i>
                                    <span class="font-medium">{{ slotProps.data.intitule }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="description" header="Description">
                            <template #body="slotProps">
                                <span class="text-sm text-600 line-clamp-2">{{ slotProps.data.description }}</span>
                            </template>
                        </Column>

                        <Column field="statut" header="Statut" sortable>
                            <template #body="slotProps">
                                <Tag 
                                    :severity="slotProps.data.statut === 'Actif' ? 'success' : 'danger'" 
                                    :value="slotProps.data.statut"
                                    rounded
                                    class="px-3 py-1"
                                />
                            </template>
                        </Column>

                        <Column header="Avis" style="text-align: center">
                            <template #body="slotProps">
                                <a v-if="slotProps.data.avis" :href="slotProps.data.avis" target="_blank">
                                    <Button 
                                        icon="pi pi-external-link" 
                                        rounded 
                                        text 
                                        severity="info" 
                                        v-tooltip="'Ouvrir l\'avis'"
                                        class="hover:text-emerald-500"
                                    />
                                </a>
                                <i v-else class="pi pi-minus text-300"></i>
                            </template>
                        </Column>

                        <Column field="created_at" header="Création" sortable>
                            <template #body="slotProps">
                                <div class="flex flex-column">
                                    <span class="text-sm">{{ formatDate(slotProps.data.created_at) }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column field="updated_at" header="Modification" sortable>
                            <template #body="slotProps">
                                <div class="flex flex-column">
                                    <span class="text-sm">{{ formatDate(slotProps.data.updated_at) }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column :exportable="false" header="Actions" style="width: 8rem">
                            <template #body="slotProps">
                                <div class="flex align-items-center gap-2">
                                    <Button 
                                        v-if="can(['update concours'])"
                                        icon="pi pi-pencil"
                                        rounded
                                        text
                                        severity="info"
                                        @click="(data.editOpen = true), (data.concours = slotProps.data)"
                                        v-tooltip="'Modifier'"
                                    />
                                    <Button 
                                        v-if="can('delete concours')"
                                        icon="pi pi-trash"
                                        rounded
                                        text
                                        severity="danger"
                                        @click="deleteDialog = true; data.concours = slotProps.data"
                                        v-tooltip="'Supprimer'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <!-- Dialog de confirmation -->
            <Dialog 
                v-model:visible="deleteDialog" 
                :style="{ width: '450px' }" 
                header="Confirmation" 
                :modal="true"
                class="p-fluid"
            >
                <div class="flex flex-column align-items-center gap-4 p-4">
                    <div class="w-16 h-16 bg-red-100 border-circle flex align-items-center justify-content-center">
                        <i class="pi pi-exclamation-triangle text-3xl text-red-600"></i>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">Êtes-vous sûr ?</h3>
                        <p v-if="data.concours" class="text-sm text-600">
                            Vous allez supprimer le concours <span class="font-bold">{{ data.concours.intitule }}</span>.
                            Cette action est irréversible.
                        </p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-content-center gap-2">
                        <Button 
                            label="Annuler" 
                            icon="pi pi-times" 
                            class="p-button-outlined p-button-secondary" 
                            @click="deleteDialog = false" 
                        />
                        <Button 
                            label="Supprimer" 
                            icon="pi pi-trash" 
                            class="p-button-danger" 
                            @click="deleteData" 
                        />
                    </div>
                </template>
            </Dialog>

            <!-- Composants Create et Edit -->
            <Create
                :show="data.createOpen"
                @close="data.createOpen = false"
                :title="props.title"
                :concours="props.concours"
            />
            <Edit
                :show="data.editOpen"
                @close="data.editOpen = false"
                :title="props.title"
                :concours="data.concours"
            />
        </div>
    </app-layout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-clamp: 2;
    overflow: hidden;
    max-height: 3em;
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

/* Styles PrimeVue personnalisés */
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

:deep(.p-datatable .p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

/* Dark mode */
:deep(.dark .p-datatable .p-datatable-thead > tr > th) {
    background: #1e293b;
    color: #e2e8f0;
}

:deep(.dark .p-datatable .p-datatable-tbody > tr:hover) {
    background: #1e293b;
}
</style>