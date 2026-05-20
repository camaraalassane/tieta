<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Create from "@/Pages/Role/Create.vue";
import Edit from "@/Pages/Role/Edit.vue";
import { usePage, useForm, router } from "@inertiajs/vue3";
import { onMounted, reactive, ref, watch, computed } from "vue";
import pkg from "lodash";
const { _, debounce, pickBy } = pkg;
import { loadToast } from "@/composables/loadToast";

const props = defineProps({
    title: String,
    filters: Object,
    permissions: Object,
    roles: Object,
    perPage: Number,
});

loadToast();

// --- LOGIQUE DE DROITS ---
const page = usePage();

const hasAccess = (permissionName) => {
    const auth = page.props.auth.user;
    if (!auth) return false;
    if (auth.is_superadmin) return true;
    const permissions = auth.permissions || [];
    return permissions.includes(permissionName);
};

// --- ÉTATS ---
const deleteDialog = ref(false);
const permissionDialog = ref(false);
const form = useForm({});

const data = reactive({
    params: {
        search: props.filters?.search || "",
        field: props.filters?.field || "",
        order: props.filters?.order || "",
        createOpen: false,
        editOpen: false,
    },
    role: null,
});

// --- STATISTIQUES ---
const stats = computed(() => {
    const roles = props.roles?.data || [];
    return {
        total: props.roles?.total || 0,
        withPermissions: roles.filter((r) => r.permissions?.length > 0).length,
        withoutPermissions: roles.filter((r) => !r.permissions?.length).length,
    };
});

// --- ACTIONS ---
const deleteData = () => {
    deleteDialog.value = false;
    form.delete(route("role.destroy", data.role?.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            data.role = null;
        },
    });
};

const onPageChange = (event) => {
    router.get(
        route("role.index"),
        {
            page: event.page + 1,
            ...data.params,
        },
        { preserveState: true },
    );
};

// --- RECHERCHE ---
const performSearch = debounce(() => {
    let params = pickBy(data.params);
    router.get(route("role.index"), params, {
        replace: true,
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch(
    () => data.params.search,
    () => {
        performSearch();
    },
);

// --- FORMATAGE ---
const formatPermissionName = (name) => {
    return name
        .split(" ")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
};
</script>

<template>
    <app-layout>
        <div class="card p-6">
            <Create
                :show="data.createOpen"
                @close="data.createOpen = false"
                :title="props.title"
                :permissions="props.permissions"
            />
            <Edit
                :show="data.editOpen"
                @close="data.editOpen = false"
                :role="data.role"
                :title="props.title"
                :permissions="props.permissions"
            />

            <!-- ⭐ En-tête dynamique -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2"
                    >
                        <i class="pi pi-shield theme-text-primary"></i>
                        Gestion des rôles
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ stats.total }} rôles au total
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <!-- ⭐ Bouton Nouveau rôle - Dynamique -->
                    <Button
                        v-if="hasAccess('create role')"
                        label="Nouveau rôle"
                        @click="data.createOpen = true"
                        icon="pi pi-plus"
                        class="btn-primary shadow-lg"
                    />
                </div>
            </div>

            <!-- ⭐ Statistiques rapides -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <!-- Total rôles -->
                <div class="stat-card-total rounded-xl p-4 border">
                    <div class="flex items-center gap-3">
                        <div
                            class="stat-icon-total rounded-lg flex items-center justify-center text-white"
                        >
                            <i class="pi pi-shield"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Total rôles
                            </p>
                            <p class="text-2xl font-bold stat-text-total">
                                {{ stats.total }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Avec permissions -->
                <div
                    class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/30"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white"
                        >
                            <i class="pi pi-check"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Avec permissions
                            </p>
                            <p
                                class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                            >
                                {{ stats.withPermissions }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Sans permission -->
                <div
                    class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-gray-500 rounded-lg flex items-center justify-center text-white"
                        >
                            <i class="pi pi-ban"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Sans permission
                            </p>
                            <p
                                class="text-2xl font-bold text-gray-600 dark:text-gray-400"
                            >
                                {{ stats.withoutPermissions }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ⭐ Barre de recherche -->
            <div class="flex justify-end mb-4">
                <IconField iconPosition="left">
                    <InputIcon>
                        <i class="pi pi-search theme-text-primary" />
                    </InputIcon>
                    <InputText
                        v-model="data.params.search"
                        placeholder="Rechercher un rôle..."
                        class="p-inputtext-sm w-full sm:w-80"
                    />
                </IconField>
            </div>

            <!-- Tableau des rôles -->
            <DataTable
                lazy
                :value="roles.data"
                paginator
                :rows="roles.per_page"
                :totalRecords="roles.total"
                :first="(roles.current_page - 1) * roles.per_page"
                @page="onPageChange"
                tableStyle="min-width: 50rem"
                class="p-datatable-sm"
                stripedRows
                showGridlines
            >
                <Column header="#" headerStyle="width: 5%">
                    <template #body="slotProps">
                        <span class="text-gray-500">{{
                            slotProps.index + 1
                        }}</span>
                    </template>
                </Column>

                <Column field="name" header="Nom" style="width: 25%">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <div class="role-avatar">
                                {{
                                    slotProps.data.name?.charAt(0).toUpperCase()
                                }}
                            </div>
                            <span class="font-medium">{{
                                slotProps.data.name
                            }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Permissions" style="width: 50%">
                    <template #body="slotProps">
                        <div class="flex items-center">
                            <!-- Toutes les permissions -->
                            <div
                                v-if="
                                    slotProps.data.permissions?.length ===
                                    props.permissions?.length
                                "
                                @click="
                                    (permissionDialog = true),
                                        (data.role = slotProps.data)
                                "
                                class="cursor-pointer group"
                            >
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1.5 permission-badge-all rounded-lg text-sm font-medium transition-colors"
                                >
                                    <i
                                        class="pi pi-check-circle theme-text-primary"
                                    ></i>
                                    Toutes les permissions
                                    <i
                                        class="pi pi-chevron-right text-xs opacity-50 group-hover:opacity-100 transition-opacity"
                                    ></i>
                                </span>
                            </div>

                            <!-- Permissions partielles -->
                            <div
                                v-else-if="
                                    slotProps.data.permissions?.length > 0
                                "
                                @click="
                                    (permissionDialog = true),
                                        (data.role = slotProps.data)
                                "
                                class="cursor-pointer group"
                            >
                                <span
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                >
                                    <i class="pi pi-list text-blue-500"></i>
                                    {{ slotProps.data.permissions.length }}
                                    permission(s)
                                    <i
                                        class="pi pi-chevron-right text-xs opacity-50 group-hover:opacity-100 transition-opacity"
                                    ></i>
                                </span>
                            </div>

                            <!-- Aucune permission -->
                            <div
                                v-else
                                class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded-lg text-sm"
                            >
                                <i class="pi pi-ban mr-1"></i>
                                Aucune permission
                            </div>
                        </div>
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="width: 15%">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="hasAccess('update role')"
                                icon="pi pi-pencil"
                                class="p-button-rounded p-button-text p-button-sm action-edit-btn"
                                @click="
                                    (data.editOpen = true),
                                        (data.role = slotProps.data)
                                "
                                v-tooltip.top="'Modifier'"
                            />
                            <Button
                                v-if="hasAccess('delete role')"
                                icon="pi pi-trash"
                                class="p-button-rounded p-button-text p-button-sm text-red-500 hover:text-red-600 hover:bg-red-50"
                                @click="
                                    deleteDialog = true;
                                    data.role = slotProps.data;
                                "
                                v-tooltip.top="'Supprimer'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- ⭐ Dialog de confirmation de suppression -->
            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '450px' }"
                header="Confirmation de suppression"
                :modal="true"
                class="p-fluid"
            >
                <div class="flex flex-col items-center gap-4 p-4">
                    <div
                        class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center"
                    >
                        <i
                            class="pi pi-exclamation-triangle text-3xl text-red-600"
                        />
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">
                            Êtes-vous sûr ?
                        </h3>
                        <p v-if="data.role" class="text-sm text-gray-500">
                            Vous allez supprimer le rôle
                            <span class="font-semibold">{{
                                data.role.name
                            }}</span
                            >. Cette action est irréversible.
                        </p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-2">
                        <Button
                            label="Annuler"
                            icon="pi pi-times"
                            class="btn-outlined-primary"
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

            <!-- ⭐ Dialog des permissions -->
            <Dialog
                v-model:visible="permissionDialog"
                modal
                :header="'Permissions du rôle : ' + data.role?.name"
                :style="{ width: '600px' }"
                class="p-fluid"
            >
                <div class="p-4">
                    <div class="mb-4 p-3 dialog-permission-info rounded-lg">
                        <div
                            class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                        >
                            <i class="pi pi-info-circle theme-text-primary"></i>
                            <span
                                >Total:
                                <strong>{{
                                    data.role?.permissions?.length || 0
                                }}</strong>
                                permission(s)</span
                            >
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-96 overflow-y-auto p-2"
                    >
                        <div
                            v-for="(permission, index) in data.role
                                ?.permissions"
                            :key="index"
                            class="px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-emerald-500 transition-colors"
                        >
                            <div class="flex items-start gap-2">
                                <i
                                    class="pi pi-check-circle theme-text-primary mt-0.5 text-xs"
                                ></i>
                                <span
                                    class="text-sm text-gray-700 dark:text-gray-300"
                                >
                                    {{ formatPermissionName(permission.name) }}
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="!data.role?.permissions?.length"
                            class="col-span-2 text-center py-8 text-gray-500"
                        >
                            <i class="pi pi-ban text-3xl mb-2 opacity-50"></i>
                            <p>Aucune permission assignée à ce rôle</p>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <Button
                        label="Fermer"
                        icon="pi pi-times"
                        class="btn-outlined-primary"
                        @click="permissionDialog = false"
                    />
                </template>
            </Dialog>
        </div>
    </app-layout>
</template>

<style scoped>
/* ⭐ Icône thème */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ⭐ Bouton primaire */
.btn-primary {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-1px);
}

/* ⭐ Bouton outlined */
.btn-outlined-primary {
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    background: transparent !important;
}

.btn-outlined-primary:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Carte statistique Total - Dynamique */
.stat-card-total {
    background: var(--color-primary-light);
    border-color: var(--color-primary-light);
}

.dark .stat-card-total {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
}

.stat-icon-total {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--color-primary);
}

.stat-text-total {
    color: var(--color-primary-dark);
}

.dark .stat-text-total {
    color: var(--color-primary-light);
}

/* ⭐ Avatar rôle */
.role-avatar {
    width: 2rem;
    height: 2rem;
    background: var(--color-primary-light);
    color: var(--color-primary-dark);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
}

.dark .role-avatar {
    background: rgba(16, 185, 129, 0.3);
    color: var(--color-primary-light);
}

/* ⭐ Badge toutes permissions */
.permission-badge-all {
    background: var(--color-primary-light);
    color: var(--color-primary-dark);
}

.dark .permission-badge-all {
    background: rgba(16, 185, 129, 0.3);
    color: var(--color-primary-light);
}

.permission-badge-all:hover {
    background: var(--color-primary);
    color: white;
}

.dark .permission-badge-all:hover {
    background: rgba(16, 185, 129, 0.5);
    color: white;
}

/* ⭐ Dialog permission info */
.dialog-permission-info {
    background: var(--color-primary-light);
}

.dark .dialog-permission-info {
    background: rgba(16, 185, 129, 0.1);
}

/* ⭐ Bouton édition */
.action-edit-btn {
    color: var(--color-primary) !important;
}

.action-edit-btn:hover {
    color: var(--color-primary-dark) !important;
    background: var(--color-primary-light) !important;
}

/* ⭐ Focus */
:deep(.p-inputtext:focus) {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Pagination */
:deep(.p-paginator .p-paginator-pages .p-paginator-page.p-highlight) {
    background: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
}

/* ⭐ Datatable hover */
:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: var(--color-primary-light) !important;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    border-bottom: 2px solid var(--color-primary-light);
}
</style>
