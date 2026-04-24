<script setup>
import { useForm } from "@inertiajs/vue3";
import { watchEffect, reactive, computed, ref, watch } from "vue";

const props = defineProps({
    show: Boolean,
    title: String,
    permissions: Object,
});

const emit = defineEmits(["close"]);

// État pour la recherche
const searchPermission = ref('');

const data = reactive({
    multipleSelect: false,
});

const form = useForm({
    name: "",
    guard_name: "web",
    permissions: [],
});

const create = () => {
    form.post(route("role.store"), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            form.reset();
            searchPermission.value = '';
        },
        onError: () => null,
        onFinish: () => null,
    });
};

// Reset à l'ouverture
watchEffect(() => {
    if (props.show) {
        form.clearErrors();
        form.reset();
        form.guard_name = "web";
        searchPermission.value = '';
    }
});

// Surveiller les changements de permissions sélectionnées
watch(() => form.permissions, (newPermissions) => {
    if (props.permissions?.length > 0) {
        data.multipleSelect = newPermissions.length === props.permissions.length;
    }
}, { deep: true });

// Sélectionner tout
const selectAll = (event) => {
    if (!event.checked) {
        form.permissions = [];
    } else {
        form.permissions = props.permissions.map(p => p.id);
    }
    data.multipleSelect = event.checked;
};

// Sélection individuelle
const select = () => {
    if (props.permissions?.length === form.permissions?.length) {
        data.multipleSelect = true;
    } else {
        data.multipleSelect = false;
    }
};

// Filtrer les permissions
const filteredPermissions = computed(() => {
    if (!props.permissions) return [];
    
    let filtered = props.permissions;
    
    if (searchPermission.value) {
        const search = searchPermission.value.toLowerCase();
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(search)
        );
    }
    
    return filtered;
});

// Statistiques
const stats = computed(() => ({
    total: props.permissions?.length || 0,
    selected: form.permissions?.length || 0,
}));

// Annulation
const cancel = () => {
    form.reset();
    form.clearErrors();
    searchPermission.value = '';
    emit("close");
};
</script>

<template>
    <Dialog 
        v-model:visible="props.show" 
        modal 
        :header="'Ajouter un rôle'" 
        :style="{ width: '650px' }" 
        class="p-fluid"
        :closable="true"
        @hide="cancel"
    >
        <!-- En-tête avec infos -->
        <div class="flex items-center gap-4 mb-6 p-4 bg-gradient-to-r from-emerald-50 to-blue-50 dark:from-emerald-900/20 dark:to-blue-900/20 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                <i class="pi pi-shield"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Nouveau rôle
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ stats.selected }}/{{ stats.total }} permissions sélectionnées
                </p>
            </div>
        </div>

        <form @submit.prevent="create">
            <div class="flex flex-col gap-5">
                <!-- Nom du rôle -->
                <div class="flex flex-col gap-2">
                    <label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <i class="pi pi-tag mr-2 text-emerald-500"></i>
                        Nom du rôle <span class="text-red-500">*</span>
                    </label>
                    <InputText 
                        id="name" 
                        v-model="form.name" 
                        class="w-full"
                        autocomplete="off" 
                        placeholder="Ex: admin, superadmin, operator..." 
                        :class="{ 'p-invalid': form.errors.name }"
                    />    
                    <small v-if="form.errors.name" class="text-red-500 text-xs">
                        {{ form.errors.name }}
                    </small>  
                </div>

                <!-- Section permissions -->
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="pi pi-lock mr-2 text-emerald-500"></i>
                            Permissions
                        </label>
                        <span class="text-xs px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full">
                            {{ stats.selected }} sélectionnée(s)
                        </span>
                    </div>

                    <!-- Barre d'outils -->
                    <div class="flex flex-col sm:flex-row gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <!-- Check all -->
                        <div class="flex items-center gap-2">
                            <Checkbox 
                                v-model="data.multipleSelect" 
                                @change="selectAll" 
                                inputId="check_all" 
                                :binary="true"
                                :disabled="!props.permissions?.length"
                            />
                            <label for="check_all" class="text-sm font-medium cursor-pointer select-none">
                                {{ data.multipleSelect ? 'Tout désélectionner' : 'Tout sélectionner' }}
                            </label>
                        </div>

                        <!-- Recherche -->
                        <div class="flex-1 relative">
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <InputText 
                                v-model="searchPermission"
                                placeholder="Rechercher une permission..."
                                class="w-full pl-8 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Liste des permissions -->
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="max-h-80 overflow-y-auto p-4 bg-white dark:bg-gray-800">
                            <div v-if="filteredPermissions.length === 0" class="text-center py-8 text-gray-500">
                                <i class="pi pi-search text-3xl mb-2 opacity-50"></i>
                                <p>Aucune permission trouvée</p>
                            </div>
                            
                            <div 
                                v-else
                                class="grid grid-cols-1 md:grid-cols-2 gap-2"
                            >
                                <div
                                    v-for="(permission, index) in filteredPermissions"
                                    :key="permission.id"
                                    class="flex items-center p-2 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors group"
                                >
                                    <Checkbox 
                                        v-model="form.permissions" 
                                        @change="select" 
                                        :inputId="'permission_' + permission.id" 
                                        :value="permission.id"
                                        :binary="false"
                                    />
                                    <label 
                                        :for="'permission_' + permission.id" 
                                        class="ml-3 text-sm cursor-pointer flex-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors select-none"
                                    >
                                        {{ permission.name }}
                                    </label>
                                    <i 
                                        class="pi text-xs transition-opacity"
                                        :class="form.permissions.includes(permission.id) ? 'pi-check-circle text-emerald-500 opacity-100' : 'pi-circle text-gray-300 opacity-0 group-hover:opacity-50'"
                                    ></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <small v-if="form.errors.permissions" class="text-red-500 text-xs">
                        {{ form.errors.permissions }}
                    </small>  
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button 
                        type="button" 
                        label="Annuler" 
                        icon="pi pi-times"
                        class="p-button-outlined p-button-secondary" 
                        @click="cancel"
                        :disabled="form.processing"
                    />
                    <Button 
                        type="submit" 
                        label="Créer" 
                        icon="pi pi-check"
                        class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                        :loading="form.processing"
                    />
                </div> 
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
:deep(.p-dialog .p-dialog-header) {
    @apply pb-2;
}

:deep(.p-dialog .p-dialog-content) {
    @apply pt-2;
}

:deep(.p-inputtext) {
    @apply border-gray-200 dark:border-gray-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all;
}

/* Styles pour les checkboxes */
:deep(.p-checkbox) {
    @apply relative;
}

:deep(.p-checkbox-box) {
    @apply w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-md transition-all;
}

:deep(.p-checkbox-box.p-highlight) {
    @apply border-emerald-500 bg-emerald-500;
}

:deep(.p-checkbox:not(.p-disabled):hover .p-checkbox-box) {
    @apply border-emerald-500;
}

:deep(.p-checkbox-box .p-checkbox-icon) {
    @apply text-white text-sm;
}

/* Scrollbar personnalisée */
.max-h-80::-webkit-scrollbar {
    width: 6px;
}

.max-h-80::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.max-h-80::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 10px;
}

.max-h-80::-webkit-scrollbar-thumb:hover {
    background: #059669;
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > div {
    animation: fadeIn 0.2s ease-out;
}

.select-none {
    user-select: none;
}
</style>