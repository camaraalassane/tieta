<script setup>
import { useForm } from "@inertiajs/vue3";
import { watchEffect, ref, computed } from "vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Dropdown from "primevue/dropdown";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import DatePicker from "primevue/datepicker";
import Card from "primevue/card";
import Badge from "primevue/badge";
import Tooltip from "primevue/tooltip";
import { useToast } from "primevue/usetoast";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    title: String,
    services: { type: Array, default: () => [] },
});

const emit = defineEmits(["close"]);
const toast = useToast();
const page = usePage();

const currentUser = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() =>
    currentUser.value?.roles?.includes("superadmin"),
);
const isGerant = computed(() => currentUser.value?.roles?.includes("gerant"));

const form = useForm({
    intitule: "",
    description: "",
    organisateur: "",
    avis: null,
    diplome_min: "",
    date_limite: null,
    age: "",
    statut: "Actif",
    service_id: null,
    has_specialites: false,
    specialites: [],
    pieces: [],
});

const serviceOptions = computed(() => {
    if (!props.services || !Array.isArray(props.services)) return [];
    return props.services.map((service) => ({
        label: service.nom,
        value: service.id,
    }));
});

const statutOptions = ref([
    {
        label: "Actif",
        value: "Actif",
        icon: "pi pi-check-circle",
        color: "success",
    },
    {
        label: "Inactif",
        value: "Inactif",
        icon: "pi pi-times-circle",
        color: "danger",
    },
]);

const diplomeOptions = ref([
    { label: "Aucun diplôme requis", value: "Aucun", icon: "pi pi-ban" },
    { label: "DEF / BEPC", value: "DEF", icon: "pi pi-book" },
    { label: "BAC", value: "BAC", icon: "pi pi-book" },
    { label: "DUT / BTS", value: "DUT", icon: "pi pi-book" },
    { label: "Licence", value: "Licence", icon: "pi pi-book" },
    { label: "Master", value: "Master", icon: "pi pi-book" },
    { label: "Doctorat", value: "Doctorat", icon: "pi pi-star" },
    { label: "CAP", value: "CAP", icon: "pi pi-book" },
    { label: "BT", value: "BT", icon: "pi pi-book" },
]);

// ⭐ Validation du formulaire - CHAMPS OBLIGATOIRES
const isFormValid = computed(() => {
    // Champs obligatoires de base
    if (!form.intitule?.trim()) return false;
    if (!form.description?.trim()) return false;
    if (!form.organisateur?.trim()) return false;
    if (!form.diplome_min) return false;
    if (!form.date_limite) return false;
    if (!form.age?.trim()) return false;
    if (!form.avis) return false;

    // Service obligatoire pour superadmin
    if (isSuperAdmin.value && !form.service_id) return false;

    return true;
});

// Gestion des pièces complémentaires
const addPiece = () => {
    form.pieces.push({
        nom_document: "",
        description: "",
        is_required: true,
    });
};

const removePiece = (index) => {
    form.pieces.splice(index, 1);
};

// Gestion des spécialités
const addSpecialite = () => {
    form.specialites.push({
        nom: "",
        description: "",
        places_disponibles: null,
        is_active: true,
    });
};

const removeSpecialite = (index) => {
    form.specialites.splice(index, 1);
};

// Création du concours
const create = () => {
    form.post(route("concours.store"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Concours créé avec succès",
                life: 3000,
            });
            emit("close");
            form.reset();
        },
        onError: (errors) => {
            console.error("Erreurs:", errors);
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: "Veuillez corriger les erreurs du formulaire",
                life: 5000,
            });
        },
    });
};

watchEffect(() => {
    if (props.show) {
        form.clearErrors();
        form.reset();
        form.specialites = [];
        form.has_specialites = false;
        form.service_id = null;
    }
});

const selectedFileName = ref("");
const onFileSelect = (event) => {
    const file = event.target.files[0];
    form.avis = file;
    selectedFileName.value = file ? file.name : "";
};
</script>

<template>
    <Dialog
        :visible="props.show"
        modal
        :header="'Création d\'un nouveau concours'"
        :style="{ width: '900px', maxWidth: '95vw' }"
        :closable="true"
        class="p-fluid"
        @hide="emit('close')"
    >
        <div
            class="flex align-items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border-round-lg mb-4"
        >
            <div
                class="w-12 h-12 bg-emerald-500 border-round-xl flex align-items-center justify-center"
            >
                <i class="pi pi-plus-circle text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-900 m-0">Nouveau concours</h2>
                <p class="text-600 text-sm m-0 flex align-items-center gap-2">
                    <i class="pi pi-info-circle text-emerald-500"></i>
                    <span
                        >Les champs avec
                        <span class="text-red-500">*</span> sont
                        obligatoires</span
                    >
                </p>
            </div>
        </div>

        <form @submit.prevent="create">
            <div class="grid grid-cols-1 gap-4">
                <!-- Section 1: Informations Générales -->
                <Card class="shadow-sm">
                    <template #title>
                        <div class="flex align-items-center gap-2">
                            <i class="pi pi-info-circle text-emerald-500"></i>
                            <span class="text-lg font-semibold"
                                >Informations générales</span
                            >
                        </div>
                    </template>
                    <template #content>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Intitulé - Obligatoire -->
                            <div class="field col-span-2">
                                <label class="font-medium text-sm text-600">
                                    Intitulé du concours
                                    <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    v-model="form.intitule"
                                    placeholder="Ex: Concours INFAS 2026"
                                    :class="{
                                        'p-invalid': form.errors.intitule,
                                    }"
                                />
                                <small
                                    v-if="form.errors.intitule"
                                    class="p-error"
                                    >{{ form.errors.intitule }}</small
                                >
                            </div>

                            <!-- ⭐ Description - Obligatoire -->
                            <div class="field col-span-2">
                                <label class="font-medium text-sm text-600">
                                    Description
                                    <span class="text-red-500">*</span>
                                </label>
                                <Textarea
                                    v-model="form.description"
                                    placeholder="Description détaillée du concours..."
                                    rows="3"
                                    :class="{
                                        'p-invalid': form.errors.description,
                                    }"
                                />
                                <small
                                    v-if="form.errors.description"
                                    class="p-error"
                                    >{{ form.errors.description }}</small
                                >
                            </div>

                            <!-- Organisateur - Obligatoire -->
                            <div class="field">
                                <label class="font-medium text-sm text-600">
                                    Organisateur
                                    <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    v-model="form.organisateur"
                                    placeholder="Ministère / Structure"
                                    :class="{
                                        'p-invalid': form.errors.organisateur,
                                    }"
                                />
                                <small
                                    v-if="form.errors.organisateur"
                                    class="p-error"
                                    >{{ form.errors.organisateur }}</small
                                >
                            </div>

                            <!-- ⭐ Diplôme requis - Obligatoire -->
                            <div class="field">
                                <label class="font-medium text-sm text-600">
                                    Diplôme requis
                                    <span class="text-red-500">*</span>
                                </label>
                                <Dropdown
                                    v-model="form.diplome_min"
                                    :options="diplomeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner un diplôme"
                                    :class="{
                                        'p-invalid': form.errors.diplome_min,
                                    }"
                                >
                                    <template #option="slotProps">
                                        <div
                                            class="flex align-items-center gap-2"
                                        >
                                            <i
                                                :class="[
                                                    slotProps.option.icon,
                                                    'text-emerald-500',
                                                ]"
                                            ></i>
                                            <span>{{
                                                slotProps.option.label
                                            }}</span>
                                        </div>
                                    </template>
                                </Dropdown>
                                <small
                                    v-if="form.errors.diplome_min"
                                    class="p-error"
                                    >{{ form.errors.diplome_min }}</small
                                >
                            </div>

                            <!-- Date limite - Obligatoire -->
                            <div class="field">
                                <label class="font-medium text-sm text-600">
                                    Date limite
                                    <span class="text-red-500">*</span>
                                </label>
                                <DatePicker
                                    v-model="form.date_limite"
                                    dateFormat="dd/mm/yy"
                                    showIcon
                                    iconDisplay="input"
                                    placeholder="JJ/MM/AAAA"
                                    :minDate="new Date()"
                                    :class="{
                                        'p-invalid': form.errors.date_limite,
                                    }"
                                />
                                <small
                                    v-if="form.errors.date_limite"
                                    class="p-error"
                                    >{{ form.errors.date_limite }}</small
                                >
                            </div>

                            <!-- ⭐ Âge limite - Obligatoire -->
                            <div class="field">
                                <label class="font-medium text-sm text-600">
                                    Âge limite
                                    <span class="text-red-500">*</span>
                                </label>
                                <InputText
                                    id="age"
                                    v-model="form.age"
                                    placeholder="Ex: 35"
                                    :class="{ 'p-invalid': form.errors.age }"
                                />
                                <small v-if="form.errors.age" class="p-error">{{
                                    form.errors.age
                                }}</small>
                            </div>

                            <!-- Statut initial -->
                            <div class="field">
                                <label class="font-medium text-sm text-600"
                                    >Statut initial</label
                                >
                                <Dropdown
                                    v-model="form.statut"
                                    :options="statutOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                >
                                    <template #value="slotProps">
                                        <div
                                            v-if="slotProps.value"
                                            class="flex align-items-center gap-2"
                                        >
                                            <Badge
                                                :value="slotProps.value"
                                                :severity="
                                                    slotProps.value === 'Actif'
                                                        ? 'success'
                                                        : 'danger'
                                                "
                                            />
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Service pour superadmin -->
                            <div class="field col-span-2" v-if="isSuperAdmin">
                                <label class="font-medium text-sm text-600">
                                    Service concerné
                                    <span class="text-red-500">*</span>
                                </label>
                                <Dropdown
                                    v-model="form.service_id"
                                    :options="serviceOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Sélectionner un service"
                                    :class="{
                                        'p-invalid': form.errors.service_id,
                                    }"
                                    :emptyMessage="'Aucun service disponible'"
                                />
                                <small
                                    v-if="form.errors.service_id"
                                    class="p-error"
                                    >{{ form.errors.service_id }}</small
                                >
                                <small v-else class="text-400"
                                    >Le concours sera rattaché à ce
                                    service</small
                                >
                            </div>

                            <!-- Message pour le gérant -->
                            <div class="field col-span-2" v-if="isGerant">
                                <div
                                    class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-sm text-emerald-700 dark:text-emerald-300"
                                >
                                    <i class="pi pi-info-circle mr-2"></i>
                                    Ce concours sera automatiquement rattaché à
                                    votre service.
                                </div>
                            </div>

                            <!-- Avis - Obligatoire -->
                            <div class="field col-span-2">
                                <label class="font-medium text-sm text-600">
                                    Avis (Fichier PDF)
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="flex align-items-center gap-2">
                                    <input
                                        type="file"
                                        @change="onFileSelect"
                                        accept=".pdf"
                                        class="hidden"
                                        ref="fileInput"
                                        id="file-upload"
                                    />
                                    <Button
                                        type="button"
                                        icon="pi pi-upload"
                                        label="Choisir un fichier"
                                        class="p-button-outlined"
                                        @click="$refs.fileInput.click()"
                                    />
                                    <span
                                        v-if="selectedFileName"
                                        class="text-sm text-600 flex align-items-center gap-2"
                                    >
                                        <i
                                            class="pi pi-file-pdf text-emerald-500"
                                        ></i>
                                        {{ selectedFileName }}
                                    </span>
                                </div>
                                <small
                                    v-if="form.errors.avis"
                                    class="p-error"
                                    >{{ form.errors.avis }}</small
                                >
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Section 2: Spécialités -->
                <Card class="shadow-sm">
                    <template #title>
                        <div
                            class="flex align-items-center justify-content-between"
                        >
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-tags text-emerald-500"></i>
                                <span class="text-lg font-semibold"
                                    >Spécialités</span
                                >
                            </div>
                            <div class="flex align-items-center gap-2">
                                <Checkbox
                                    v-model="form.has_specialites"
                                    :binary="true"
                                    inputId="has_specialites"
                                />
                                <label
                                    for="has_specialites"
                                    class="text-sm text-600 cursor-pointer"
                                >
                                    Ce concours a des spécialités
                                </label>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div
                            v-if="form.has_specialites"
                            class="flex flex-col gap-3"
                        >
                            <div class="flex justify-content-end">
                                <Button
                                    type="button"
                                    icon="pi pi-plus"
                                    label="Ajouter une spécialité"
                                    class="p-button-sm p-button-outlined p-button-success"
                                    @click="addSpecialite"
                                />
                            </div>

                            <div
                                v-for="(specialite, index) in form.specialites"
                                :key="index"
                                class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700"
                            >
                                <div class="flex align-items-start gap-2">
                                    <div
                                        class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3"
                                    >
                                        <div class="field">
                                            <label
                                                class="font-medium text-sm text-600"
                                            >
                                                Nom de la spécialité
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <InputText
                                                v-model="specialite.nom"
                                                placeholder="Ex: Génie Civil, Informatique..."
                                                :class="{
                                                    'p-invalid':
                                                        form.errors[
                                                            `specialites.${index}.nom`
                                                        ],
                                                }"
                                            />
                                        </div>
                                        <div class="field">
                                            <label
                                                class="font-medium text-sm text-600"
                                                >Places disponibles</label
                                            >
                                            <InputText
                                                v-model="
                                                    specialite.places_disponibles
                                                "
                                                type="number"
                                                placeholder="Nombre de places"
                                            />
                                        </div>
                                        <div class="field col-span-2">
                                            <label
                                                class="font-medium text-sm text-600"
                                                >Description</label
                                            >
                                            <Textarea
                                                v-model="specialite.description"
                                                placeholder="Description de la spécialité..."
                                                rows="2"
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        @click="removeSpecialite(index)"
                                        v-tooltip.top="
                                            'Supprimer cette spécialité'
                                        "
                                    />
                                </div>
                            </div>

                            <div
                                v-if="form.specialites.length === 0"
                                class="text-center py-6 border-2 border-dashed border-surface-200 rounded-xl"
                            >
                                <i
                                    class="pi pi-tags text-3xl text-300 mb-2"
                                ></i>
                                <p class="text-500 text-sm">
                                    Aucune spécialité ajoutée
                                </p>
                                <p class="text-400 text-xs">
                                    Cliquez sur "Ajouter une spécialité" pour
                                    ajouter des spécialités
                                </p>
                            </div>
                        </div>

                        <div v-else class="text-center py-4 text-500">
                            <i class="pi pi-info-circle mr-2"></i>
                            <span class="text-sm"
                                >Cochez "Ce concours a des spécialités" pour
                                ajouter des filières ou spécialités</span
                            >
                        </div>
                    </template>
                </Card>

                <!-- Section 3: Pièces complémentaires -->
                <Card class="shadow-sm">
                    <template #title>
                        <div
                            class="flex align-items-center justify-content-between"
                        >
                            <div class="flex align-items-center gap-2">
                                <i class="pi pi-file-pdf text-emerald-500"></i>
                                <span class="text-lg font-semibold"
                                    >Pièces complémentaires</span
                                >
                            </div>
                            <Button
                                type="button"
                                icon="pi pi-plus"
                                label="Ajouter une pièce"
                                class="p-button-sm p-button-outlined p-button-success"
                                @click="addPiece"
                            />
                        </div>
                    </template>
                    <template #content>
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="(piece, index) in form.pieces"
                                :key="index"
                                class="p-3 bg-surface-50 dark:bg-surface-800 rounded-lg border border-surface-200 dark:border-surface-700"
                            >
                                <div class="flex align-items-start gap-2">
                                    <div class="flex-1">
                                        <InputText
                                            v-model="piece.nom_document"
                                            placeholder="Nom du document (ex: Diplôme, CV, etc.)"
                                            class="w-full mb-2"
                                            :class="{
                                                'p-invalid':
                                                    form.errors[
                                                        `pieces.${index}.nom_document`
                                                    ],
                                            }"
                                        />
                                        <Textarea
                                            v-model="piece.description"
                                            placeholder="Description ou précisions sur le document..."
                                            rows="2"
                                            class="w-full"
                                        />
                                    </div>
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        @click="removePiece(index)"
                                        v-tooltip.top="'Supprimer cette pièce'"
                                    />
                                </div>
                                <div class="flex align-items-center gap-2 mt-2">
                                    <Checkbox
                                        v-model="piece.is_required"
                                        :binary="true"
                                        :inputId="'req-' + index"
                                    />
                                    <label
                                        :for="'req-' + index"
                                        class="text-sm text-600"
                                        >Document obligatoire</label
                                    >
                                </div>
                                <small
                                    v-if="
                                        form.errors[
                                            `pieces.${index}.nom_document`
                                        ]
                                    "
                                    class="p-error"
                                >
                                    Le nom du document est requis
                                </small>
                            </div>

                            <div
                                v-if="form.pieces.length === 0"
                                class="text-center py-6 border-2 border-dashed border-surface-200 rounded-xl"
                            >
                                <i
                                    class="pi pi-file-pdf text-3xl text-300 mb-2"
                                ></i>
                                <p class="text-500 text-sm">
                                    Aucune pièce complémentaire demandée
                                </p>
                                <p class="text-400 text-xs">
                                    Cliquez sur "Ajouter une pièce" pour
                                    spécifier des documents requis
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <!-- Footer -->
            <div
                class="flex justify-content-end gap-3 mt-6 pt-4 border-top-1 surface-border"
            >
                <Button
                    type="button"
                    label="Annuler"
                    severity="secondary"
                    icon="pi pi-times"
                    @click="emit('close')"
                />
                <Button
                    type="submit"
                    label="Créer le concours"
                    icon="pi pi-check"
                    :loading="form.processing"
                    :disabled="!isFormValid"
                    class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                />
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
.p-error {
    @apply text-red-500 text-xs mt-1 block;
}

.field {
    @apply flex flex-col gap-1;
}

@keyframes slideIn {
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
    animation: slideIn 0.3s ease-out;
}

.hidden {
    display: none;
}

:deep(.p-inputtext:focus),
:deep(.p-dropdown:focus),
:deep(.p-datepicker:focus),
:deep(.p-textarea:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

.text-red-500 {
    color: #ef4444;
}

:deep(.p-badge) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    border-color: #10b981 !important;
    background: #10b981 !important;
}
</style>
