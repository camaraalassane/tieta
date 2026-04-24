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
import Divider from "primevue/divider";
import { useToast } from "primevue/usetoast";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    title: String,
    concours: Object,
    services: { type: Array, default: () => [] }, // ⭐ Ajout des services
});

const emit = defineEmits(["close"]);
const toast = useToast();
const page = usePage();

// Récupérer le rôle de l'utilisateur
const currentUser = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() =>
    currentUser.value?.roles?.includes("superadmin"),
);
const isGerant = computed(() => currentUser.value?.roles?.includes("gerant"));

// Formulaire principal
const form = useForm({
    _method: "put",
    intitule: "",
    description: "",
    organisateur: "",
    avis: null,
    diplome_min: "",
    date_limite: null,
    age: "",
    statut: "",
    service_id: null, // ⭐ Ajout du service_id
    has_specialites: false,
    specialites: [],
    pieces: [],
});

// ⭐ Options pour les services - affiche uniquement le nom
const serviceOptions = computed(() => {
    if (!props.services || !Array.isArray(props.services)) return [];
    return props.services.map((service) => ({
        label: service.nom,
        value: service.id,
    }));
});

// Options pour les selects
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

// État pour le fichier existant
const existingFile = ref(null);
const selectedFileName = ref("");

// Validation du formulaire
const isFormValid = computed(() => {
    let valid = form.intitule && form.organisateur && form.date_limite;

    if (isSuperAdmin.value && !form.service_id) {
        valid = false;
    }

    return valid;
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
    if (form.pieces[index]?.id) {
        if (!form.removed_pieces_ids) form.removed_pieces_ids = [];
        form.removed_pieces_ids.push(form.pieces[index].id);
    }
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

// Mise à jour du concours
const update = () => {
    form.post(route("concours.update", props.concours.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Concours mis à jour avec succès",
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

const handleFileUpload = (event) => {
    if (event.target.files.length > 0) {
        const file = event.target.files[0];
        form.avis = file;
        selectedFileName.value = file.name;
    }
};

const removeExistingFile = () => {
    existingFile.value = null;
    form.avis = null;
};

watchEffect(() => {
    if (props.show && props.concours) {
        form.clearErrors();
        form.intitule = props.concours.intitule;
        form.description = props.concours.description;
        form.organisateur = props.concours.organisateur;
        form.diplome_min = props.concours.diplome_min;
        form.date_limite = props.concours.date_limite
            ? new Date(props.concours.date_limite)
            : null;
        form.age = props.concours.age;
        form.statut = props.concours.statut;
        form.service_id = props.concours.service_id || null; // ⭐ Charger le service_id
        form.avis = null;
        form.has_specialites = props.concours.has_specialites || false;
        form.removed_pieces_ids = [];

        existingFile.value = props.concours.avis;
        selectedFileName.value = "";

        // Charger les pièces
        form.pieces = props.concours.pieces
            ? props.concours.pieces.map((p) => ({
                  id: Number(p.id),
                  nom_document: p.nom_document,
                  description: p.description || "",
                  is_required: !!p.is_required,
              }))
            : [];

        // Charger les spécialités
        form.specialites = props.concours.specialites
            ? props.concours.specialites.map((s) => ({
                  id: Number(s.id),
                  nom: s.nom,
                  description: s.description || "",
                  places_disponibles: s.places_disponibles || null,
                  is_active: s.is_active !== false,
              }))
            : [];
    }
});
</script>

<template>
    <Dialog
        :visible="props.show"
        modal
        :header="'Modification du concours'"
        :style="{ width: '900px', maxWidth: '95vw' }"
        :closable="true"
        class="p-fluid"
        @hide="emit('close')"
    >
        <!-- En-tête avec progression -->
        <div
            class="flex align-items-center gap-3 p-3 bg-emerald-50 dark:bg-emerald-900/20 border-round-lg mb-4"
        >
            <div
                class="w-12 h-12 bg-emerald-500 border-round-xl flex align-items-center justify-center"
            >
                <i class="pi pi-pencil text-white text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-900 m-0">
                    Modifier le concours
                </h2>
                <p class="text-600 text-sm m-0 flex align-items-center gap-2">
                    <i class="pi pi-info-circle text-emerald-500"></i>
                    ID: {{ props.concours?.id }} •
                    {{ props.concours?.intitule }}
                </p>
            </div>
        </div>

        <form @submit.prevent="update">
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
                            <div class="field col-span-2">
                                <label class="font-medium text-sm text-600"
                                    >Intitulé du concours
                                    <span class="text-red-500">*</span></label
                                >
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

                            <div class="field col-span-2">
                                <label class="font-medium text-sm text-600"
                                    >Description</label
                                >
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

                            <div class="field">
                                <label class="font-medium text-sm text-600"
                                    >Organisateur
                                    <span class="text-red-500">*</span></label
                                >
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

                            <div class="field">
                                <label class="font-medium text-sm text-600"
                                    >Diplôme requis</label
                                >
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

                            <div class="field">
                                <label class="font-medium text-sm text-600"
                                    >Date limite
                                    <span class="text-red-500">*</span></label
                                >
                                <DatePicker
                                    v-model="form.date_limite"
                                    dateFormat="yy-mm-dd"
                                    showIcon
                                    iconDisplay="input"
                                    placeholder="AAAA-MM-JJ"
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

                            <div class="field">
                                <label class="font-medium text-sm text-600"
                                    >Âge limite</label
                                >
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

                            <div class="field">
                                <label class="font-medium text-sm text-600"
                                    >Statut</label
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

                            <!-- ⭐ Sélection du service pour le superadmin -->
                            <div class="field col-span-2" v-if="isSuperAdmin">
                                <label class="font-medium text-sm text-600"
                                    >Service concerné
                                    <span class="text-red-500">*</span></label
                                >
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
                                    >Le concours est rattaché à ce
                                    service</small
                                >
                            </div>

                            <!-- ⭐ Message pour le gérant -->
                            <div class="field col-span-2" v-if="isGerant">
                                <div
                                    class="p-3 bg-emerald-50 rounded-lg text-sm text-emerald-700"
                                >
                                    <i class="pi pi-info-circle mr-2"></i>
                                    Ce concours est rattaché à votre service.
                                </div>
                            </div>

                            <div class="field col-span-2">
                                <label class="font-medium text-sm text-600"
                                    >Avis (Fichier PDF)</label
                                >

                                <!-- Fichier existant -->
                                <div
                                    v-if="existingFile"
                                    class="flex align-items-center gap-2 p-2 bg-emerald-50 border-round-lg mb-2"
                                >
                                    <i
                                        class="pi pi-file-pdf text-emerald-500 text-xl"
                                    ></i>
                                    <span
                                        class="flex-1 text-sm text-600 truncate"
                                        >{{ existingFile }}</span
                                    >
                                    <Button
                                        icon="pi pi-times"
                                        class="p-button-rounded p-button-text p-button-sm"
                                        @click="removeExistingFile"
                                        v-tooltip.top="'Remplacer le fichier'"
                                    />
                                </div>

                                <!-- Upload nouveau fichier -->
                                <div class="flex align-items-center gap-2">
                                    <input
                                        type="file"
                                        @change="handleFileUpload"
                                        accept=".pdf"
                                        class="hidden"
                                        ref="fileInput"
                                        id="file-upload"
                                    />
                                    <Button
                                        type="button"
                                        icon="pi pi-upload"
                                        :label="
                                            existingFile
                                                ? 'Remplacer le fichier'
                                                : 'Choisir un fichier'
                                        "
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
                                <small class="text-400 text-xs"
                                    >Laissez vide pour conserver le fichier
                                    actuel</small
                                >
                                <small
                                    v-if="form.errors.avis"
                                    class="p-error"
                                    >{{ form.errors.avis }}</small
                                >
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Section 2: Spécialités (Optionnel) -->
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
                                <!-- Badge pour spécialité existante -->
                                <div
                                    v-if="specialite.id"
                                    class="flex align-items-center gap-1 mb-2"
                                >
                                    <Badge
                                        value="Déjà enregistrée"
                                        severity="success"
                                        class="text-xs"
                                    ></Badge>
                                    <small class="text-emerald-600 text-xs"
                                        >ID: {{ specialite.id }}</small
                                    >
                                </div>

                                <div class="flex align-items-start gap-2">
                                    <div
                                        class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3"
                                    >
                                        <div class="field">
                                            <label
                                                class="font-medium text-sm text-600"
                                                >Nom de la spécialité
                                                <span class="text-red-500"
                                                    >*</span
                                                ></label
                                            >
                                            <InputText
                                                v-model="specialite.nom"
                                                placeholder="Ex: Génie Civil, Informatique, Médecine..."
                                                :class="{
                                                    'p-invalid':
                                                        form.errors[
                                                            `specialites.${index}.nom`
                                                        ],
                                                }"
                                            />
                                            <small
                                                v-if="
                                                    form.errors[
                                                        `specialites.${index}.nom`
                                                    ]
                                                "
                                                class="p-error"
                                            >
                                                {{
                                                    form.errors[
                                                        `specialites.${index}.nom`
                                                    ]
                                                }}
                                            </small>
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
                                    Aucune spécialité pour ce concours
                                </p>
                                <p class="text-400 text-xs">
                                    Cliquez sur "Ajouter une spécialité" pour
                                    ajouter des filières ou spécialités
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
                                <!-- Badge pour pièce existante -->
                                <div
                                    v-if="piece.id"
                                    class="flex align-items-center gap-1 mb-2"
                                >
                                    <Badge
                                        value="Enregistrée"
                                        severity="success"
                                        class="text-xs"
                                    ></Badge>
                                    <small class="text-emerald-600 text-xs"
                                        >ID: {{ piece.id }}</small
                                    >
                                </div>

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
                                    Aucune pièce complémentaire
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

            <!-- Footer / Boutons -->
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
                    label="Enregistrer les modifications"
                    icon="pi pi-save"
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

/* Animation d'entrée */
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

/* Style pour l'input file caché */
.hidden {
    display: none;
}

/* Amélioration du focus */
:deep(.p-inputtext:focus),
:deep(.p-dropdown:focus),
:deep(.p-datepicker:focus),
:deep(.p-textarea:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

/* Style pour les champs obligatoires */
.text-red-500 {
    color: #ef4444;
}

/* Badge personnalisé */
:deep(.p-badge) {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Style pour la checkbox */
:deep(.p-checkbox .p-checkbox-box.p-highlight) {
    border-color: #10b981 !important;
    background: #10b981 !important;
}
</style>
