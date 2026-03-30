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
});

loadToast();

// --- LOGIQUE DE DROITS ---
const page = usePage();

const hasAccess = (permissionName) => {
    const auth = page.props.auth.user;
    if (!auth) return false;
    if (auth.is_superadmin) return true;
    
    const permissions = auth.permissions || [];
    if (Array.isArray(permissionName)) {
        return permissionName.some((p) => permissions.includes(p));
    }
    return permissions.includes(permissionName);
};

// --- LOGIQUE DE GESTION DES UTILISATEURS ---
const deleteDialog = ref(false);
const form = useForm({});

const data = reactive({
    params: {
        search: props.filters?.search || '',
        field: props.filters?.field || '',
        order: props.filters?.order || '',
        createOpen: false,
        editOpen: false,
    },
    user: null,
    roleFilter: props.filters?.role || 'all',
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

// Statistiques par rôle
const stats = computed(() => {
    const users = props.users?.data || [];
    return {
        total: props.users?.total || 0,
        superadmin: users.filter(u => u.roles?.some(r => r.name === 'superadmin')).length,
        admin: users.filter(u => u.roles?.some(r => r.name === 'admin')).length,
        operator: users.filter(u => u.roles?.some(r => r.name === 'operator')).length,
    };
});

const onPageChange = (event) => {
    router.get(
        route("user.index"),
        { 
            page: event.page + 1,
            ...data.params,
            role: data.roleFilter === 'all' ? null : data.roleFilter
        },
        { preserveState: true },
    );
};

// Filtrer par rôle
const filterByRole = (role) => {
    data.roleFilter = role;
    router.get(route("user.index"), { 
        role: role === 'all' ? null : role,
        ...data.params 
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Recherche avec debounce
const performSearch = debounce(() => {
    let params = pickBy({ 
        ...data.params, 
        role: data.roleFilter === 'all' ? null : data.roleFilter 
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
        if (newVal !== oldVal) {
            performSearch();
        }
    }
);

// Debug : Afficher les données dans la console
console.log('Données utilisateurs:', props.users?.data);
console.log('Filtres:', props.filters);


const debugRoleFilter = () => {
    console.log('=== DEBUG FILTRE RÔLE ===');
    console.log('1. roleFilter actuel:', data.roleFilter);
    console.log('2. filters reçu du back:', props.filters);
    console.log('3. Tous les utilisateurs:', props.users?.data);
    console.log('4. Rôles disponibles:', props.roles);
     const stats = {
        total: props.users?.total,
        superadmin: props.users?.data?.filter(u => u.roles?.some(r => r.name === 'superadmin')).length,
        admin: props.users?.data?.filter(u => u.roles?.some(r => r.name === 'admin')).length,
        operator: props.users?.data?.filter(u => u.roles?.some(r => r.name === 'operator')).length,
    };
    console.log('5. Stats calculées:', stats);
};

// Appeler le debug au montage
debugRoleFilter();

// Watch sur les props pour voir les changements
watch(() => props.filters, (newFilters) => {
    console.log('Filtres mis à jour:', newFilters);
}, { deep: true });
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

            <!-- En-tête -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="pi pi-users text-emerald-500"></i>
                        Gestion des utilisateurs
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ stats.total }} utilisateurs au total
                    </p>
                </div>
                
                <div class="flex items-center gap-2">
                    <Button
                        v-if="hasAccess('create user')"
                        label="Nouvel utilisateur"
                        @click="data.createOpen = true"
                        icon="pi pi-plus"
                        class="bg-emerald-500 hover:bg-emerald-600 border-none text-white shadow-lg shadow-emerald-500/30"
                    />
                </div>
            </div>

            <!-- Statistiques par rôle -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <!-- Superadmin -->
                <div 
                    class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800/30 cursor-pointer transition-all hover:shadow-md"
                    :class="{ 'ring-2 ring-purple-500': data.roleFilter === 'superadmin' }"
                    @click="filterByRole('superadmin')"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white">
                            <i class="pi pi-star"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Superadmins</p>
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.superadmin }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Admin -->
                <div 
                    class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/30 cursor-pointer transition-all hover:shadow-md"
                    :class="{ 'ring-2 ring-emerald-500': data.roleFilter === 'admin' }"
                    @click="filterByRole('admin')"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white">
                            <i class="pi pi-shield"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Administrateurs</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.admin }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Operator (Candidat) -->
                <div 
                    class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/30 cursor-pointer transition-all hover:shadow-md"
                    :class="{ 'ring-2 ring-blue-500': data.roleFilter === 'operator' }"
                    @click="filterByRole('operator')"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white">
                            <i class="pi pi-user"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Candidats</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.operator }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm text-gray-500">Filtres:</span>
                    <Button 
                        label="Tous" 
                        class="p-button-text p-button-sm"
                        :class="{ 'text-emerald-500 font-semibold': data.roleFilter === 'all' }"
                        @click="filterByRole('all')"
                    />
                    <Button 
                        label="Superadmins" 
                        class="p-button-text p-button-sm"
                        :class="{ 'text-purple-500 font-semibold': data.roleFilter === 'superadmin' }"
                        @click="filterByRole('superadmin')"
                    />
                    <Button 
                        label="Administrateurs" 
                        class="p-button-text p-button-sm"
                        :class="{ 'text-emerald-500 font-semibold': data.roleFilter === 'admin' }"
                        @click="filterByRole('admin')"
                    />
                    <Button 
                        label="Candidats" 
                        class="p-button-text p-button-sm"
                        :class="{ 'text-blue-500 font-semibold': data.roleFilter === 'operator' }"
                        @click="filterByRole('operator')"
                    />
                </div>
                
                <!-- Recherche améliorée - inclut nom et prénom -->
                <div class="flex items-center gap-2">
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search text-emerald-500" />
                        <InputText
                            v-model="data.params.search"
                            placeholder="Nom, prénom ou email..."
                            class="p-inputtext-sm w-full sm:w-80 border-emerald-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200"
                        />
                    </IconField>
                    <span class="text-xs text-gray-400 hidden sm:block">
                        Recherche sur nom, prénom, email
                    </span>
                </div>
            </div>

            <!-- Tableau des utilisateurs -->
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
                    <template #body="slotProps">
                        <div class="font-medium">{{ slotProps.data.name }}</div>
                    </template>
                </Column>
                
                <Column field="prenom" header="Prénom" style="width: 20%">
                    <template #body="slotProps">
                        <div>{{ slotProps.data.prenom || '-' }}</div>
                    </template>
                </Column>
                
                <Column field="email" header="Email" style="width: 30%">
                    <template #body="slotProps">
                        <div class="text-sm">{{ slotProps.data.email }}</div>
                    </template>
                </Column>
                
                <Column header="Rôle(s)" style="width: 20%">
                    <template #body="slotProps">
                        <div class="flex flex-wrap gap-1">
                            <span
                                v-for="role in slotProps.data.roles"
                                :key="role.id"
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="{
                                    'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': role.name === 'superadmin',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': role.name === 'admin',
                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': role.name === 'operator',
                                }"
                            >
                                {{ role.name === 'operator' ? 'Candidat' : role.name === 'superadmin' ? 'Superadmin' : 'Administrateur' }}
                            </span>
                        </div>
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="width: 10rem">
                    <template #body="slotProps">
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="hasAccess('update user')"
                                icon="pi pi-pencil"
                                class="p-button-rounded p-button-text p-button-sm text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50"
                                @click="
                                    (data.editOpen = true),
                                    (data.user = slotProps.data)
                                "
                                v-tooltip.top="'Modifier'"
                            />
                            <Button
                                v-if="hasAccess('delete user')"
                                icon="pi pi-trash"
                                class="p-button-rounded p-button-text p-button-sm text-red-500 hover:text-red-600 hover:bg-red-50"
                                @click="
                                    deleteDialog = true;
                                    data.user = slotProps.data;
                                "
                                v-tooltip.top="'Supprimer'"
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <!-- Dialog de confirmation -->
            <Dialog
                v-model:visible="deleteDialog"
                :style="{ width: '450px' }"
                header="Confirmation de suppression"
                :modal="true"
                class="p-fluid"
            >
                <div class="flex flex-col items-center gap-4 p-4">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle text-3xl text-red-600" />
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-2">Êtes-vous sûr ?</h3>
                        <p v-if="data.user" class="text-sm text-gray-500">
                            Vous allez supprimer <span class="font-semibold">{{ data.user.name }} {{ data.user.prenom }}</span>.
                            Cette action est irréversible.
                        </p>
                    </div>
                </div>
                <template #footer>
                    <div class="flex justify-center gap-2">
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
        </div>
    </app-layout>
</template>

<style scoped>
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background: #f9fafb;
    color: #374151;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: #f0fdf4;
}

.dark :deep(.p-datatable .p-datatable-tbody > tr:hover) {
    background: rgba(16, 185, 129, 0.1);
}

/* Animation pour les statistiques */
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

.grid > div {
    animation: fadeInUp 0.3s ease-out;
}
</style>