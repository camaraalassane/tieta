<script setup>
import { useForm } from "@inertiajs/vue3";
import { watchEffect, reactive, computed, ref, watch } from "vue";

const props = defineProps({
    show: Boolean,
    title: String,
    role: Object,
    permissions: Object,
});

const emit = defineEmits(["close"]);

// État pour la recherche dans les permissions
const searchPermission = ref("");
const selectedCategory = ref("all");

const data = reactive({
    multipleSelect: false,
});

const form = useForm({
    name: "",
    permissions: [],
});

const update = () => {
    form.put(route("role.update", props.role?.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            form.reset();
        },
        onError: () => null,
        onFinish: () => null,
    });
};

// Initialisation
watchEffect(() => {
    if (props.show && props.role) {
        form.clearErrors();
        form.name = props.role?.name || "";
        // 🔑 CORRECTION ICI : S'assurer que c'est un tableau d'IDs
        form.permissions = props.role.permissions?.map((d) => d.id) || [];
    }
});

// 🔑 CORRECTION : Surveiller les changements de form.permissions pour mettre à jour multipleSelect
watch(
    () => form.permissions,
    (newPermissions) => {
        if (props.permissions?.length > 0) {
            data.multipleSelect =
                newPermissions.length === props.permissions.length;
        }
    },
    { deep: true },
);

// 🔑 CORRECTION : Surveiller l'ouverture/fermeture
watch(
    () => props.show,
    (newVal) => {
        if (!newVal) {
            // Reset quand on ferme
            form.reset();
            form.clearErrors();
            searchPermission.value = "";
        }
    },
);

// Actions sur les permissions
const selectAll = (event) => {
    // 🔑 CORRECTION : Utiliser event.checked au lieu de event.target.checked
    if (!event.checked) {
        form.permissions = [];
    } else {
        form.permissions = props.permissions.map((p) => p.id);
    }
    data.multipleSelect = event.checked;
};

const select = () => {
    // 🔑 CORRECTION : Mettre à jour multipleSelect basé sur la longueur
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

    // Filtre par recherche
    if (searchPermission.value) {
        const search = searchPermission.value.toLowerCase();
        filtered = filtered.filter((p) =>
            p.name.toLowerCase().includes(search),
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
    emit("close");
};

// 🔑 DEBUG : Pour voir ce qui se passe
watch(
    () => form.permissions,
    (newVal) => {
        console.log("Permissions sélectionnées:", newVal);
    },
    { deep: true },
);
</script>

<template>
    <Dialog
        v-model:visible="props.show"
        modal
        :header="'Modifier le rôle : ' + form.name"
        :style="{ width: '650px' }"
        class="p-fluid"
        :closable="true"
        @hide="cancel"
    >
        <!-- ⭐ En-tête dynamique -->
        <div
            class="flex items-center gap-4 mb-6 p-4 dialog-header-edit rounded-lg border"
        >
            <div class="dialog-avatar-edit">
                <i class="pi pi-shield"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ form.name || "Nouveau rôle" }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    ID: {{ props.role?.id }} • {{ stats.selected }}/{{
                        stats.total
                    }}
                    permissions sélectionnées
                </p>
            </div>
        </div>

        <form @submit.prevent="update">
            <div class="flex flex-col gap-5">
                <!-- Nom du rôle -->
                <div class="flex flex-col gap-2">
                    <label
                        for="name"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-tag mr-2 theme-text-primary"></i>
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

                <!-- ⭐ Section permissions -->
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            <i class="pi pi-lock mr-2 theme-text-primary"></i>
                            Permissions
                        </label>
                        <span
                            class="text-xs px-2 py-1 permission-count-badge rounded-full"
                        >
                            {{ stats.selected }} sélectionnée(s)
                        </span>
                    </div>

                    <!-- Barre d'outils des permissions -->
                    <div
                        class="flex flex-col sm:flex-row gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                    >
                        <!-- Check all -->
                        <div class="flex items-center gap-2">
                            <Checkbox
                                v-model="data.multipleSelect"
                                @change="selectAll"
                                inputId="check_all"
                                :binary="true"
                                :disabled="!props.permissions?.length"
                            />
                            <label
                                for="check_all"
                                class="text-sm font-medium cursor-pointer select-none"
                            >
                                {{
                                    data.multipleSelect
                                        ? "Tout désélectionner"
                                        : "Tout sélectionner"
                                }}
                            </label>
                        </div>

                        <!-- Recherche -->
                        <div class="flex-1 relative">
                            <i
                                class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"
                            ></i>
                            <InputText
                                v-model="searchPermission"
                                placeholder="Rechercher une permission..."
                                class="w-full pl-8 text-sm"
                            />
                        </div>
                    </div>

                    <!-- Liste des permissions -->
                    <div
                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                    >
                        <div
                            class="max-h-80 overflow-y-auto p-4 bg-white dark:bg-gray-800"
                        >
                            <div
                                v-if="filteredPermissions.length === 0"
                                class="text-center py-8 text-gray-500"
                            >
                                <i
                                    class="pi pi-search text-3xl mb-2 opacity-50"
                                ></i>
                                <p>Aucune permission trouvée</p>
                            </div>

                            <div
                                v-else
                                class="grid grid-cols-1 md:grid-cols-2 gap-2"
                            >
                                <div
                                    v-for="(
                                        permission, index
                                    ) in filteredPermissions"
                                    :key="permission.id"
                                    class="flex items-center p-2 rounded-lg permission-item transition-colors group"
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
                                        class="ml-3 text-sm cursor-pointer flex-1 transition-colors select-none permission-label"
                                    >
                                        {{ permission.name }}
                                    </label>
                                    <i
                                        class="pi text-xs transition-opacity"
                                        :class="
                                            form.permissions.includes(
                                                permission.id,
                                            )
                                                ? 'pi-check-circle theme-text-primary opacity-100'
                                                : 'pi-circle text-gray-300 opacity-0 group-hover:opacity-50'
                                        "
                                    ></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <small
                        v-if="form.errors.permissions"
                        class="text-red-500 text-xs"
                    >
                        {{ form.errors.permissions }}
                    </small>
                </div>

                <!-- ⭐ Boutons d'action -->
                <div
                    class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <Button
                        type="button"
                        label="Annuler"
                        icon="pi pi-times"
                        class="btn-outlined-primary"
                        @click="cancel"
                        :disabled="form.processing"
                    />
                    <Button
                        type="submit"
                        label="Mettre à jour"
                        icon="pi pi-check"
                        class="btn-primary"
                        :loading="form.processing"
                    />
                </div>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
/* ============================================ */
/* ⭐ DIALOG HEADER */
/* ============================================ */
:deep(.p-dialog .p-dialog-header) {
    @apply pb-2;
}

:deep(.p-dialog .p-dialog-content) {
    @apply pt-2;
}

/* ============================================ */
/* ⭐ EN-TÊTE DU DIALOGUE - DYNAMIQUE */
/* ============================================ */
.dialog-header-edit {
    background: linear-gradient(to right, var(--color-primary-light), #eff6ff);
    border-color: var(--color-primary-light);
}

.dark .dialog-header-edit {
    background: linear-gradient(
        to right,
        rgba(16, 185, 129, 0.1),
        rgba(59, 130, 246, 0.1)
    );
    border-color: rgba(16, 185, 129, 0.3);
}

.dialog-avatar-edit {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(
        135deg,
        var(--color-primary),
        var(--color-primary-dark)
    );
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* ============================================ */
/* ⭐ ICÔNE THÈME */
/* ============================================ */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ============================================ */
/* ⭐ BADGE COMPTEUR PERMISSIONS */
/* ============================================ */
.permission-count-badge {
    background: var(--color-primary-light);
    color: var(--color-primary-dark);
}

.dark .permission-count-badge {
    background: rgba(16, 185, 129, 0.3);
    color: var(--color-primary-light);
}

/* ============================================ */
/* ⭐ ITEM PERMISSION HOVER */
/* ============================================ */
.permission-item:hover {
    background: var(--color-primary-light);
}

.dark .permission-item:hover {
    background: rgba(16, 185, 129, 0.1);
}

/* ⭐ Label permission hover */
.permission-label:hover {
    color: var(--color-primary-dark);
}

.dark .permission-label:hover {
    color: var(--color-primary-light);
}

/* ============================================ */
/* ⭐ BOUTONS */
/* ============================================ */
.btn-primary {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
}

/* ============================================ */
/* ⭐ INPUT FOCUS */
/* ============================================ */
:deep(.p-inputtext) {
    @apply border-gray-200 dark:border-gray-700 transition-all;
}

:deep(.p-inputtext:focus) {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ============================================ */
/* ⭐ CHECKBOX */
/* ============================================ */
:deep(.p-checkbox) {
    @apply relative;
}

:deep(.p-checkbox-box) {
    @apply w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-md transition-all;
}

:deep(.p-checkbox-box.p-highlight) {
    border-color: var(--color-primary) !important;
    background: var(--color-primary) !important;
}

:deep(.p-checkbox:not(.p-disabled):hover .p-checkbox-box) {
    border-color: var(--color-primary) !important;
}

:deep(.p-checkbox-box .p-checkbox-icon) {
    @apply text-white text-sm;
}

/* ============================================ */
/* ⭐ SCROLLBAR - DYNAMIQUE */
/* ============================================ */
.max-h-80::-webkit-scrollbar {
    width: 6px;
}

.max-h-80::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.max-h-80::-webkit-scrollbar-thumb {
    background: var(--color-primary);
    border-radius: 10px;
}

.max-h-80::-webkit-scrollbar-thumb:hover {
    background: var(--color-primary-dark);
}

.dark .max-h-80::-webkit-scrollbar-track {
    background: #374151;
}

/* ============================================ */
/* ⭐ ANIMATION */
/* ============================================ */
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

/* Empêcher la sélection de texte sur les labels */
.select-none {
    user-select: none;
}
</style>
