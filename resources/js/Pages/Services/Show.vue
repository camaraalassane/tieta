<template>
    <app-layout>
        <div class="p-3 sm:p-4 md:p-6">
            <!-- En-tête - style similaire à l'index -->
            <div class="mb-4 sm:mb-6">
                <Card
                    class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/10 dark:to-gray-900"
                >
                    <template #content>
                        <div
                            class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4"
                        >
                            <div
                                class="flex items-center gap-3 sm:gap-4 w-full sm:w-auto"
                            >
                                <div
                                    class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-500 rounded-xl flex items-center justify-center shadow-2 flex-shrink-0"
                                >
                                    <i
                                        class="pi pi-building text-white text-xl sm:text-2xl"
                                    ></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h1
                                        class="text-xl sm:text-2xl font-bold text-900 m-0 truncate"
                                    >
                                        {{
                                            isEditing
                                                ? "Modifier le service"
                                                : service.nom
                                        }}
                                    </h1>
                                    <p
                                        class="text-xs sm:text-sm text-600 mt-0.5 flex items-center gap-1"
                                    >
                                        <i
                                            class="pi pi-check-circle text-emerald-500 text-xs"
                                        ></i>
                                        <span class="truncate">{{
                                            isEditing
                                                ? "Modifiez les informations du service"
                                                : "Détails du service"
                                        }}</span>
                                    </p>
                                </div>
                            </div>
                            <button
                                v-if="canManage && !isEditing"
                                @click="enableEditing"
                                class="px-3 py-1.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition flex items-center gap-1.5 font-medium text-sm w-full sm:w-auto justify-center"
                            >
                                <i class="pi pi-pencil text-xs"></i> Modifier
                            </button>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Mode édition -->
            <form
                v-if="isEditing"
                @submit.prevent="updateService"
                class="space-y-4"
            >
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <div
                        class="border-b border-gray-100 dark:border-gray-700 px-4 py-2.5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800/50 dark:to-gray-800"
                    >
                        <h2
                            class="text-sm font-semibold text-gray-800 dark:text-white flex items-center gap-2"
                        >
                            <i
                                class="pi pi-info-circle text-emerald-500 text-sm"
                            ></i>
                            Modifier les informations
                        </h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                >
                                    Nom du service
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.nom"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white transition-all text-sm"
                                    placeholder="Ex: DTTIA"
                                />
                                <p
                                    v-if="errors.nom"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ errors.nom }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >Description</label
                                >
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white transition-all text-sm"
                                    placeholder="Description du service..."
                                ></textarea>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >Logo</label
                                >
                                <div
                                    class="flex flex-col sm:flex-row items-center gap-3"
                                >
                                    <div class="flex-1 w-full">
                                        <input
                                            type="file"
                                            @change="handleLogoUpload"
                                            accept="image/*"
                                            class="w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                        />
                                    </div>
                                    <div
                                        v-if="form.logo_preview"
                                        class="flex-shrink-0"
                                    >
                                        <img
                                            :src="form.logo_preview"
                                            class="w-10 h-10 rounded-full object-cover border-2 border-emerald-500 shadow-sm"
                                        />
                                    </div>
                                    <div
                                        v-else-if="service.logo_url"
                                        class="flex-shrink-0"
                                    >
                                        <img
                                            :src="service.logo_url"
                                            class="w-10 h-10 rounded-full object-cover border border-gray-300"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        @click="cancelEditing"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-medium text-sm"
                    >
                        Annuler
                    </button>
                    <button
                        type="submit"
                        :disabled="processing"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 transition font-medium text-sm disabled:opacity-50 shadow-sm"
                    >
                        <i class="pi pi-save mr-1 text-xs"></i>
                        {{ processing ? "Enregistrement..." : "Enregistrer" }}
                    </button>
                </div>
            </form>

            <!-- Mode consultation -->
            <template v-else>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-3 border-l-4 border-emerald-500"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Statut</p>
                                <p
                                    class="text-lg font-bold mt-0.5"
                                    :class="
                                        service.is_active
                                            ? 'text-emerald-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        service.is_active ? "Actif" : "Inactif"
                                    }}
                                </p>
                            </div>
                            <div
                                class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center"
                            >
                                <i
                                    class="pi pi-check-circle text-emerald-500 text-sm"
                                ></i>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-3 border-l-4 border-blue-500"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Personnel</p>
                                <p
                                    class="text-lg font-bold mt-0.5 text-blue-600"
                                >
                                    {{ service.personnel?.length || 0 }}
                                </p>
                            </div>
                            <div
                                class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center"
                            >
                                <i
                                    class="pi pi-users text-blue-500 text-sm"
                                ></i>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-3 border-l-4 border-purple-500"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500">Slug</p>
                                <p
                                    class="text-xs font-mono mt-0.5 text-purple-600 truncate"
                                >
                                    {{ service.slug }}
                                </p>
                            </div>
                            <div
                                class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center"
                            >
                                <i
                                    class="pi pi-hashtag text-purple-500 text-sm"
                                ></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div
                        class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden"
                    >
                        <div
                            class="border-b border-gray-100 dark:border-gray-700 px-4 py-2.5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800/50 dark:to-gray-800"
                        >
                            <h2
                                class="text-sm font-semibold text-gray-800 dark:text-white flex items-center gap-2"
                            >
                                <i
                                    class="pi pi-info-circle text-emerald-500 text-sm"
                                ></i>
                                Informations générales
                            </h2>
                        </div>
                        <div class="p-4">
                            <div class="space-y-2">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center py-1.5 border-b border-gray-100 dark:border-gray-700"
                                >
                                    <div
                                        class="sm:w-24 text-xs font-medium text-gray-500"
                                    >
                                        Nom
                                    </div>
                                    <div
                                        class="flex-1 text-gray-800 dark:text-white font-medium text-sm"
                                    >
                                        {{ service.nom }}
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center py-1.5 border-b border-gray-100 dark:border-gray-700"
                                >
                                    <div
                                        class="sm:w-24 text-xs font-medium text-gray-500"
                                    >
                                        Gérant
                                    </div>
                                    <div
                                        class="flex-1 text-gray-800 dark:text-white"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                            >
                                                {{
                                                    service.gerant?.name?.charAt(
                                                        0,
                                                    ) || "G"
                                                }}
                                            </div>
                                            <div>
                                                <div class="text-sm">
                                                    {{ service.gerant?.name }}
                                                    {{
                                                        service.gerant
                                                            ?.prenom || ""
                                                    }}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ service.gerant?.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col sm:flex-row sm:items-start py-1.5"
                                >
                                    <div
                                        class="sm:w-24 text-xs font-medium text-gray-500"
                                    >
                                        Description
                                    </div>
                                    <div
                                        class="flex-1 text-gray-600 dark:text-gray-400 leading-relaxed text-sm"
                                    >
                                        {{
                                            service.description ||
                                            "Aucune description"
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden"
                    >
                        <div
                            class="border-b border-gray-100 dark:border-gray-700 px-4 py-2.5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800/50 dark:to-gray-800"
                        >
                            <h2
                                class="text-sm font-semibold text-gray-800 dark:text-white flex items-center gap-2"
                            >
                                <i
                                    class="pi pi-image text-emerald-500 text-sm"
                                ></i>
                                Logo
                            </h2>
                        </div>
                        <div class="p-4 flex flex-col items-center">
                            <div
                                v-if="service.logo_url"
                                class="w-20 h-20 rounded-xl overflow-hidden bg-gray-100 shadow-md"
                            >
                                <img
                                    :src="service.logo_url"
                                    :alt="service.nom"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div
                                v-else
                                class="w-20 h-20 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-md"
                            >
                                <i
                                    class="pi pi-building text-2xl text-white"
                                ></i>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Logo du service
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <div
                        class="border-b border-gray-100 dark:border-gray-700 px-4 py-2.5 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800/50 dark:to-gray-800 flex justify-between items-center"
                    >
                        <h2
                            class="text-sm font-semibold text-gray-800 dark:text-white flex items-center gap-2"
                        >
                            <i class="pi pi-users text-emerald-500 text-sm"></i>
                            Personnel du service
                        </h2>
                        <Link
                            v-if="canManage"
                            :href="
                                route('services.personnel.index', service.id)
                            "
                            class="px-3 py-1 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-lg hover:from-emerald-600 hover:to-teal-700 transition text-xs font-medium shadow-sm flex items-center gap-1"
                        >
                            <i class="pi pi-user-plus text-xs"></i> Gérer
                        </Link>
                    </div>
                    <div class="p-0 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900/30">
                                <tr>
                                    <th
                                        class="text-left py-2 px-3 text-xs font-semibold text-gray-500"
                                    >
                                        Nom
                                    </th>
                                    <th
                                        class="text-left py-2 px-3 text-xs font-semibold text-gray-500"
                                    >
                                        Email
                                    </th>
                                    <th
                                        class="text-left py-2 px-3 text-xs font-semibold text-gray-500"
                                    >
                                        Rôle
                                    </th>
                                    <th
                                        class="text-left py-2 px-3 text-xs font-semibold text-gray-500"
                                    >
                                        Statut
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-700"
                            >
                                <tr
                                    v-for="member in service.personnel"
                                    :key="member.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                                >
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full flex items-center justify-center text-white text-xs font-medium"
                                            >
                                                {{
                                                    member.name?.charAt(0) ||
                                                    "U"
                                                }}
                                            </div>
                                            <span
                                                class="font-medium text-gray-800 dark:text-white text-sm"
                                                >{{ member.name }}
                                                {{ member.prenom || "" }}</span
                                            >
                                        </div>
                                    </td>
                                    <td
                                        class="py-2 px-3 text-gray-600 dark:text-gray-400 text-sm"
                                    >
                                        {{ member.email }}
                                    </td>
                                    <td class="py-2 px-3">
                                        <span
                                            :class="{
                                                'px-2 py-0.5 text-xs font-semibold rounded-full': true,
                                                'bg-emerald-100 text-emerald-700':
                                                    member.pivot
                                                        ?.role_in_service ===
                                                    'gerant',
                                                'bg-blue-100 text-blue-700':
                                                    member.pivot
                                                        ?.role_in_service ===
                                                    'admin',
                                            }"
                                        >
                                            <i
                                                :class="
                                                    member.pivot
                                                        ?.role_in_service ===
                                                    'gerant'
                                                        ? 'pi pi-star'
                                                        : 'pi pi-shield'
                                                "
                                                class="mr-1 text-xs"
                                            ></i>
                                            {{
                                                member.pivot
                                                    ?.role_in_service ===
                                                "gerant"
                                                    ? "Gérant"
                                                    : "Admin"
                                            }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3">
                                        <span
                                            :class="
                                                member.pivot?.is_active
                                                    ? 'text-green-600'
                                                    : 'text-red-600'
                                            "
                                            class="flex items-center gap-1 text-sm"
                                        >
                                            <i
                                                :class="
                                                    member.pivot?.is_active
                                                        ? 'pi pi-circle-on'
                                                        : 'pi pi-circle-off'
                                                "
                                                class="text-xs"
                                            ></i>
                                            {{
                                                member.pivot?.is_active
                                                    ? "Actif"
                                                    : "Inactif"
                                            }}
                                        </span>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !service.personnel ||
                                        service.personnel.length === 0
                                    "
                                >
                                    <td
                                        colspan="4"
                                        class="py-8 text-center text-gray-500 text-sm"
                                    >
                                        <i
                                            class="pi pi-users text-3xl mb-2 opacity-50 block"
                                        ></i
                                        >Aucun personnel dans ce service
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>
        </div>
    </app-layout>
</template>

<script setup>
import { ref, reactive } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import { Link, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    service: { type: Object, required: true },
    isSuperAdmin: { type: Boolean, default: false },
    isGerant: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
});

const toast = useToast();
const isEditing = ref(false);
const processing = ref(false);
const errors = ref({});

const form = reactive({
    nom: props.service.nom,
    description: props.service.description || "",
    logo: null,
    logo_preview: null,
});

const enableEditing = () => {
    isEditing.value = true;
    form.nom = props.service.nom;
    form.description = props.service.description || "";
    form.logo_preview = null;
};

const cancelEditing = () => {
    isEditing.value = false;
    errors.value = {};
    form.logo_preview = null;
};

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.logo = file;
        form.logo_preview = URL.createObjectURL(file);
    }
};

const updateService = () => {
    processing.value = true;
    errors.value = {};

    const data = new FormData();
    data.append("nom", form.nom);
    data.append("description", form.description);
    if (form.logo) {
        data.append("logo", form.logo);
    }
    data.append("_method", "PUT");

    router.post(route("services.update", props.service.id), data, {
        onSuccess: () => {
            isEditing.value = false;
            processing.value = false;
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Service mis à jour avec succès",
                life: 3000,
            });
        },
        onError: (err) => {
            errors.value = err;
            processing.value = false;
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: "Une erreur est survenue lors de la mise à jour",
                life: 3000,
            });
        },
    });
};
</script>
