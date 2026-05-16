<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Create from "@/Pages/User/Create.vue";
import Edit from "@/Pages/User/Edit.vue";
import { usePage, useForm, router } from "@inertiajs/vue3";
import { reactive, ref, watch, computed } from "vue";
import pkg from "lodash";
const { _, debounce, pickBy } = pkg;
import { loadToast } from "@/composables/loadToast";

const props = defineProps({
    title: String,
    filters: Object,
    users: Object,
    roles: Object,
    perPage: Number,
    total_superadmin: Number,
    total_admin: Number,
    total_operator: Number,
});

loadToast();

const page = usePage();

const hasAccess = (permissionName) => {
    const auth = page.props.auth.user;
    if (!auth) return false;
    if (auth.is_superadmin) return true;
    const permissions = auth.permissions || [];
    if (Array.isArray(permissionName))
        return permissionName.some((p) => permissions.includes(p));
    return permissions.includes(permissionName);
};

const deleteDialog = ref(false);
const form = useForm({});

const data = reactive({
    params: {
        search: props.filters?.search || "",
        field: props.filters?.field || "",
        order: props.filters?.order || "",
        createOpen: false,
        editOpen: false,
    },
    user: null,
    roleFilter: props.filters?.role || "all",
});

const deleteData = () => {
    deleteDialog.value = false;
    form.delete(route("user.destroy", data.user?.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            data.user = null;
        },
    });
};

const roles = props.roles?.map((role) => ({
    name: role.name,
    code: role.name,
}));

// ⭐ Statistiques CORRIGÉES
const stats = computed(() => ({
    total: props.users?.total || 0,
    superadmin: props.total_superadmin || 0,
    admin: props.total_admin || 0,
    operator: props.total_operator || 0,
}));

const onPageChange = (event) => {
    router.get(
        route("user.index"),
        {
            page: event.page + 1,
            ...data.params,
            role: data.roleFilter === "all" ? null : data.roleFilter,
        },
        { preserveState: true },
    );
};

const performSearch = debounce(() => {
    let params = pickBy({
        ...data.params,
        role: data.roleFilter === "all" ? null : data.roleFilter,
    });
    router.get(route("user.index"), params, {
        replace: true,
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

watch(
    () => data.params.search,
    (newVal, oldVal) => {
        if (newVal !== oldVal) performSearch();
    },
);
// ⭐ Filtrer par rôle avec recherche automatique
const filterByRole = (role) => {
    data.roleFilter = role;
    // Réinitialiser la recherche
    data.params.search = "";
    // Recharger avec le filtre de rôle
    router.get(
        route("user.index"),
        {
            role: role === "all" ? null : role,
            search: "", // Réinitialiser la recherche
            page: 1, // Retour à la première page
        },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <app-layout>
        <div class="card p-6">
            <Create
                :show="data.createOpen"
                @close="data.createOpen = false"
                :roles="roles"
                :title="props.title"
            />
            <Edit
                :show="data.editOpen"
                @close="data.editOpen = false"
                :roles="roles"
                :user="data.user"
                :title="props.title"
            />

            <!-- ⭐ En-tête dynamique -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6"
            >
                <div>
                    <h1
                        class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2"
                    >
                        <i class="pi pi-users theme-text-primary"></i>
                        Gestion des utilisateurs
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ stats.total }} utilisateurs au total
                    </p>
                </div>
                <!-- ⭐ Bouton Nouvel utilisateur - Dynamique -->
                <Button
                    v-if="hasAccess('create user')"
                    label="Nouvel utilisateur"
                    @click="data.createOpen = true"
                    icon="pi pi-plus"
                    class="btn-primary shadow-lg"
                />
            </div>

            <!-- ⭐ Statistiques par rôle -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <!-- Superadmins -->
                <div
                    class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/30 cursor-pointer transition-all hover:shadow-md"
                    :class="{
                        'ring-2 ring-purple-500':
                            data.roleFilter === 'superadmin',
                    }"
                    @click="filterByRole('superadmin')"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white"
                        >
                            <i class="pi pi-star"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Superadmins
                            </p>
                            <p
                                class="text-2xl font-bold text-purple-600 dark:text-purple-400"
                            >
                                {{ stats.superadmin }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Administrateurs -->
                <div
                    class="stat-card-admin rounded-xl p-4 border cursor-pointer transition-all hover:shadow-md"
                    :class="{
                        'ring-2 ring-emerald-500': data.roleFilter === 'admin',
                    }"
                    @click="filterByRole('admin')"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="stat-icon-admin rounded-lg flex items-center justify-center text-white"
                        >
                            <i class="pi pi-shield"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Administrateurs
                            </p>
                            <p class="text-2xl font-bold stat-text-admin">
                                {{ stats.admin }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Candidats -->
                <div
                    class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/30 cursor-pointer transition-all hover:shadow-md"
                    :class="{
                        'ring-2 ring-blue-500': data.roleFilter === 'operator',
                    }"
                    @click="filterByRole('operator')"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white"
                        >
                            <i class="pi pi-user"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Candidats
                            </p>
                            <p
                                class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                            >
                                {{ stats.operator }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ⭐ Filtres -->
            <div
                class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6"
            >
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm text-gray-500">Filtres:</span>
                    <Button
                        label="Tous"
                        class="p-button-text p-button-sm"
                        :class="{
                            'filter-btn-active': data.roleFilter === 'all',
                        }"
                        @click="filterByRole('all')"
                    />
                    <Button
                        label="Superadmins"
                        class="p-button-text p-button-sm"
                        :class="{
                            'text-purple-500 font-semibold':
                                data.roleFilter === 'superadmin',
                        }"
                        @click="filterByRole('superadmin')"
                    />
                    <Button
                        label="Administrateurs"
                        class="p-button-text p-button-sm"
                        :class="{
                            'filter-btn-active': data.roleFilter === 'admin',
                        }"
                        @click="filterByRole('admin')"
                    />
                    <Button
                        label="Candidats"
                        class="p-button-text p-button-sm"
                        :class="{
                            'text-blue-500 font-semibold':
                                data.roleFilter === 'operator',
                        }"
                        @click="filterByRole('operator')"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search theme-text-primary" />
                        <InputText
                            v-model="data.params.search"
                            placeholder="Nom, prénom ou email..."
                            class="p-inputtext-sm w-full sm:w-80"
                        />
                    </IconField>
                </div>
            </div>

            <!-- Tableau -->
            <DataTable
                lazy
                :value="users.data"
                paginator
                :rows="users.per_page"
                :totalRecords="users.total"
                :first="(users.current_page - 1) * users.per_page"
                @page="onPageChange"
                dataKey="id"
                tableStyle="min-width: 50rem"
                class="p-datatable-sm"
                stripedRows
                showGridlines
            >
                <Column field="name" header="Nom" style="width: 20%">
                    <template #body="sp">
                        <div class="font-medium">{{ sp.data.name }}</div>
                    </template>
                </Column>
                <Column field="prenom" header="Prénom" style="width: 20%">
                    <template #body="sp">
                        <div>{{ sp.data.prenom || "-" }}</div>
                    </template>
                </Column>
                <Column field="email" header="Email" style="width: 30%">
                    <template #body="sp">
                        <div class="text-sm">{{ sp.data.email }}</div>
                    </template>
                </Column>
                <Column header="Rôle(s)" style="width: 20%">
                    <template #body="sp">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="role in sp.data.roles"
                                :key="role.id"
                                class="px-2 py-1 text-xs font-medium rounded-full cursor-pointer hover:opacity-80 transition-opacity"
                                :class="{
                                    'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300':
                                        role.name === 'superadmin',
                                    'role-badge-admin': role.name === 'admin',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300':
                                        role.name === 'operator',
                                }"
                                @click.stop="filterByRole(role.name)"
                            >
                                {{
                                    role.name === "operator"
                                        ? "Candidat"
                                        : role.name === "superadmin"
                                          ? "Superadmin"
                                          : "Administrateur"
                                }}
                            </span>
                        </div>
                    </template>
                </Column>
                <Column
                    header="Actions"
                    :exportable="false"
                    style="width: 10rem"
                >
                    <template #body="sp">
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="hasAccess('update user')"
                                icon="pi pi-pencil"
                                class="p-button-rounded p-button-text p-button-sm action-edit-btn"
                                @click="
                                    (data.editOpen = true),
                                        (data.user = sp.data)
                                "
                                v-tooltip.top="'Modifier'"
                            />
                            <Button
                                v-if="hasAccess('delete user')"
                                icon="pi pi-trash"
                                class="p-button-rounded p-button-text p-button-sm text-red-500 hover:text-red-600 hover:bg-red-50"
                                @click="
                                    deleteDialog = true;
                                    data.user = sp.data;
                                "
                                v-tooltip.top="'Supprimer'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- ⭐ Dialog de confirmation -->
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
                        <p v-if="data.user" class="text-sm text-gray-500">
                            Vous allez supprimer
                            <span class="font-semibold"
                                >{{ data.user.name }}
                                {{ data.user.prenom }}</span
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

/* ⭐ Carte statistique Admin - Dynamique */
.stat-card-admin {
    background: var(--color-primary-light);
    border-color: var(--color-primary-light);
}

.dark .stat-card-admin {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
}

.stat-icon-admin {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--color-primary);
}

.stat-text-admin {
    color: var(--color-primary-dark);
}

.dark .stat-text-admin {
    color: var(--color-primary-light);
}

/* ⭐ Badge rôle Admin - Dynamique */
.role-badge-admin {
    background: var(--color-primary-light);
    color: var(--color-primary-dark);
}

/* ⭐ Filtre actif */
.filter-btn-active {
    color: var(--color-primary) !important;
    font-weight: 600 !important;
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
</style>
