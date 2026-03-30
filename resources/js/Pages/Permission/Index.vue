<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Create from "@/Pages/Permission/Create.vue";
import Edit from "@/Pages/Permission/Edit.vue";
import { usePage, useForm, router } from '@inertiajs/vue3';
import { reactive, ref, watch, computed } from "vue";
import pkg from "lodash";
const { _, debounce, pickBy } = pkg;
import { loadToast } from '@/composables/loadToast';

const props = defineProps({
    title: String,
    filters: Object,
    permissions: Object,
    roles: Object,
    perPage: Number,
});

loadToast();

// --- LOGIQUE DE DROITS (Basée sur votre HandleInertiaRequests) ---
const page = usePage();

const hasAccess = (permissionName) => {
    const auth = page.props.auth.user;
    if (!auth) return false;

    // Autorisation automatique si SuperAdmin
    if (auth.is_superadmin) return true;

    // Sinon vérification par permission
    const userPermissions = auth.permissions || [];
    if (Array.isArray(permissionName)) {
        return permissionName.some(p => userPermissions.includes(p));
    }
    return userPermissions.includes(permissionName);
};

// --- LOGIQUE DU COMPOSANT ---
const deleteDialog = ref(false);
const form = useForm({});

const data = reactive({
    params: {
        search: props.filters.search,
        field: props.filters.field,
        order: props.filters.order,
        createOpen: false,
        editOpen: false,
    },
    permission: null,
});

const deleteData = () => {
    deleteDialog.value = false;
    form.delete(route("permission.destroy", data.permission?.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

const onPageChange = (event) => {
    router.get(route('permission.index'), { page: event.page + 1 }, { preserveState: true });
};

watch(
    () => _.cloneDeep(data.params),
    debounce(() => {
        let params = pickBy(data.params);
        router.get(route("permission.index"), params, {
            replace: true,
            preserveState: true,
            preserveScroll: true,
        });
    }, 150)
);

</script>

<template>
    <app-layout>            
        <div class="card">            
            <Create
                :show="data.createOpen"
                @close="data.createOpen = false"
                :title="props.title"
            />
            <Edit
                :show="data.editOpen"
                @close="data.editOpen = false"
                :permission="data.permission"
                :title="props.title"
            />

            <div class="flex justify-between items-center mb-4">
                <h5 class="m-0">Gestion des Permissions</h5>
                <Button 
                    v-if="hasAccess('create permission')" 
                    label="Nouvelle Permission" 
                    @click="data.createOpen = true" 
                    icon="pi pi-plus" 
                    severity="success"
                />
            </div>

            <DataTable lazy :value="permissions.data" paginator :rows="permissions.per_page" :totalRecords="permissions.total" :first="(permissions.current_page - 1) * permissions.per_page" @page="onPageChange" tableStyle="min-width: 50rem">
                <template #header>
                    <div class="flex justify-end">
                        <IconField>
                            <InputIcon>
                                <i class="pi pi-search" />
                            </InputIcon>
                            <InputText v-model="data.params.search" placeholder="Rechercher..." />
                        </IconField>
                    </div>
                </template>

                <template #empty> Aucune donnée trouvée. </template>

                <Column header="No">
                    <template #body="slotProps">
                        {{slotProps.index + 1}}
                    </template>
                </Column>

                <Column field="name" header="Nom"></Column>
                <Column field="guard_name" header="Guard"></Column>
                <Column field="created_at" header="Créé le"></Column>
                
                <Column :exportable="false" style="min-width: 12rem" header="Actions">
                    <template #body="slotProps">
                        <Button 
                            v-if="hasAccess('update permission')" 
                            icon="pi pi-pencil" outlined rounded class="mr-2" 
                            @click="(data.editOpen = true), (data.permission = slotProps.data)" 
                        />
                        <Button 
                            v-if="hasAccess('delete permission')" 
                            icon="pi pi-trash" outlined rounded severity="danger" 
                            @click="deleteDialog = true; data.permission = slotProps.data" 
                        />
                    </template>
                </Column>
            </DataTable>

            <Dialog v-model:visible="deleteDialog" :style="{ width: '450px' }" header="Confirmation" :modal="true">
                <div class="flex items-center gap-4">
                    <i class="pi pi-exclamation-triangle !text-3xl" />
                    <span v-if="data.permission">Voulez-vous supprimer <b>{{ data.permission.name }}</b> ?</span>
                </div>
                <template #footer>
                    <Button label="Non" icon="pi pi-times" text @click="deleteDialog = false" />
                    <Button label="Oui" icon="pi pi-check" severity="danger" @click="deleteData" />
                </template>
            </Dialog>
        </div>
    </app-layout>
</template>