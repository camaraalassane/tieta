<script setup>
import { ref, computed } from "vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";
import Message from "primevue/message";
import Badge from "primevue/badge";
import Tag from "primevue/tag";

const props = defineProps({
    concours: Array,
    // ⭐ AJOUT : Nouvelles props pour les rôles et service
    user_role: String,
    is_superadmin: {
        type: Boolean,
        default: false,
    },
    is_gerant: {
        type: Boolean,
        default: false,
    },
    is_admin: {
        type: Boolean,
        default: false,
    },
    userService: {
        type: Object,
        default: null,
    },
});

const toast = useToast();
const selectedConcours = ref(null);
const resultatExiste = ref(false);
const resultatCount = ref(0);
const montrerFormulaire = ref(false);
const currentStep = ref(1);
const checking = ref(false);

const statusOptions = [
    {
        label: "Brouillon - Accès restreint",
        value: "en preparation",
        icon: "pi pi-lock",
        color: "warning",
    },
    {
        label: "Publié - Visible par tous",
        value: "publié",
        icon: "pi pi-globe",
        color: "success",
    },
];

const form = useForm({
    concour_id: null,
    intitule: "",
    statut: "en preparation",
});

// Concours sélectionné formaté
const selectedConcourInfo = computed(() => {
    if (!selectedConcours.value) return null;
    return {
        nom: selectedConcours.value.nom,
        id: selectedConcours.value.id,
        date: selectedConcours.value.date_limite,
        candidats: selectedConcours.value.nb_candidats || 0,
        organisateur: selectedConcours.value.organisateur || "Non spécifié",
    };
});

// ⭐ AJOUT : Nombre de concours disponibles
const nombreConcoursDisponibles = computed(() => {
    return props.concours?.length || 0;
});

const verifierResultatExistant = async () => {
    if (selectedConcours.value) {
        checking.value = true;
        try {
            const response = await axios.get(
                `/api/check-resultat/${selectedConcours.value.id}`,
            );

            resultatExiste.value = response.data.exists;
            resultatCount.value = response.data.count || 0;

            if (response.data.exists) {
                // ⭐ MODIFICATION : On passe directement à l'étape 3 avec un message informatif
                montrerFormulaire.value = false;
                currentStep.value = 2;
            } else {
                // Aucun résultat existant, on passe directement à la création
                initialiserNouveauFormulaire();
            }
        } catch (error) {
            console.error("Erreur vérification:", error);

            let errorMessage = "Vérification impossible";
            if (error.response?.status === 403) {
                errorMessage = "Vous n'avez pas accès à ce concours";
            }

            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: errorMessage,
                life: 3000,
            });

            // Réinitialiser en cas d'erreur d'accès
            selectedConcours.value = null;
            currentStep.value = 1;
        } finally {
            checking.value = false;
        }
    }
};

const initialiserNouveauFormulaire = () => {
    resultatExiste.value = false;
    montrerFormulaire.value = true;
    currentStep.value = 3;
    form.concour_id = selectedConcours.value.id;

    // ⭐ AMÉLIORATION : Générer un intitulé avec numéro si plusieurs résultats
    if (resultatCount.value > 0) {
        form.intitule = `Résultats ${resultatCount.value + 1} - ${selectedConcours.value.nom}`;
    } else {
        form.intitule = `Résultats définitifs - ${selectedConcours.value.nom}`;
    }
};

const reinitialiserTout = () => {
    selectedConcours.value = null;
    resultatExiste.value = false;
    resultatCount.value = 0;
    montrerFormulaire.value = false;
    currentStep.value = 1;
    form.reset();
};

const soumettreResultat = () => {
    form.post(route("concour-creerResultat.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail:
                    page.props.flash?.success ||
                    "Le résultat a été généré avec succès",
                life: 3000,
            });
            reinitialiserTout();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail:
                    Object.values(errors).flat().join(", ") ||
                    "Veuillez corriger les erreurs du formulaire",
                life: 5000,
            });
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="p-fluid px-4 md:px-6 lg:px-8">
            <!-- En-tête -->
            <div class="grid mb-6">
                <div class="col-12">
                    <Card
                        class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-900"
                    >
                        <template #content>
                            <div
                                class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4"
                            >
                                <div class="flex align-items-center gap-4">
                                    <div
                                        class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-center"
                                    >
                                        <i
                                            class="pi pi-file-export text-white text-3xl"
                                        ></i>
                                    </div>
                                    <div>
                                        <h1
                                            class="text-3xl font-bold text-900 m-0 mb-2"
                                        >
                                            Création des résultats officiels
                                        </h1>
                                        <p
                                            class="text-600 m-0 flex align-items-center gap-2"
                                        >
                                            <i
                                                class="pi pi-check-circle text-emerald-500"
                                            ></i>
                                            Gérez l'édition et la diffusion des
                                            résultats
                                        </p>
                                    </div>
                                </div>

                                <!-- ⭐ AJOUT : Badge avec nombre de concours -->
                                <div class="flex gap-2">
                                    <Badge
                                        :value="nombreConcoursDisponibles"
                                        severity="info"
                                        size="large"
                                    >
                                        <template #default>
                                            <span class="px-2"
                                                >Concours disponibles</span
                                            >
                                        </template>
                                    </Badge>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- ⭐ AJOUT : Messages d'information selon le rôle -->
            <div v-if="is_superadmin" class="mb-4">
                <Message severity="success" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-crown"></i>
                        <span
                            ><strong>Super Administrateur</strong> - Vous avez
                            accès à tous les concours.</span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_gerant && userService" class="mb-4">
                <Message severity="success" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-building"></i>
                        <span
                            ><strong
                                >Gérant - Service {{ userService.nom }}</strong
                            >
                            - Vous gérez tous les concours de votre
                            service.</span
                        >
                    </div>
                </Message>
            </div>

            <div v-if="is_admin && userService" class="mb-4">
                <Message severity="info" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-user"></i>
                        <span
                            ><strong
                                >Administrateur - Service
                                {{ userService.nom }}</strong
                            >
                            - Vous gérez uniquement les concours qui vous sont
                            assignés.</span
                        >
                    </div>
                </Message>
            </div>

            <!-- ⭐ AJOUT : Message si aucun concours disponible -->
            <div v-if="nombreConcoursDisponibles === 0" class="mb-4">
                <Message severity="warn" :closable="false">
                    <div class="flex align-items-center gap-2">
                        <i class="pi pi-exclamation-triangle"></i>
                        <span
                            >Aucun concours disponible pour votre profil.</span
                        >
                    </div>
                </Message>
            </div>

            <!-- Stepper de progression -->
            <div class="grid mb-4" v-if="nombreConcoursDisponibles > 0">
                <div class="col-12">
                    <Card class="shadow-sm">
                        <template #content>
                            <div
                                class="flex justify-content-between align-items-center px-4 py-2"
                            >
                                <div class="flex align-items-center gap-4">
                                    <div
                                        class="flex align-items-center"
                                        :class="{
                                            'opacity-50': currentStep < 1,
                                        }"
                                    >
                                        <span
                                            class="w-8 h-8 border-circle flex align-items-center justify-content-center font-bold mr-2"
                                            :class="
                                                currentStep >= 1
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'bg-200 text-600'
                                            "
                                            >1</span
                                        >
                                        <span
                                            class="font-medium"
                                            :class="
                                                currentStep >= 1
                                                    ? 'text-900'
                                                    : 'text-500'
                                            "
                                            >Choix concours</span
                                        >
                                    </div>
                                    <i class="pi pi-arrow-right text-400"></i>
                                    <div
                                        class="flex align-items-center"
                                        :class="{
                                            'opacity-50': currentStep < 2,
                                        }"
                                    >
                                        <span
                                            class="w-8 h-8 border-circle flex align-items-center justify-content-center font-bold mr-2"
                                            :class="
                                                currentStep >= 2
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'bg-200 text-600'
                                            "
                                            >2</span
                                        >
                                        <span
                                            class="font-medium"
                                            :class="
                                                currentStep >= 2
                                                    ? 'text-900'
                                                    : 'text-500'
                                            "
                                            >Vérification</span
                                        >
                                    </div>
                                    <i class="pi pi-arrow-right text-400"></i>
                                    <div
                                        class="flex align-items-center"
                                        :class="{
                                            'opacity-50': currentStep < 3,
                                        }"
                                    >
                                        <span
                                            class="w-8 h-8 border-circle flex align-items-center justify-content-center font-bold mr-2"
                                            :class="
                                                currentStep >= 3
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'bg-200 text-600'
                                            "
                                            >3</span
                                        >
                                        <span
                                            class="font-medium"
                                            :class="
                                                currentStep >= 3
                                                    ? 'text-900'
                                                    : 'text-500'
                                            "
                                            >Configuration</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <!-- Carte principale -->
            <Card class="shadow-md" v-if="nombreConcoursDisponibles > 0">
                <template #content>
                    <!-- Étape 1 : Sélection -->
                    <div class="p-4" v-if="currentStep === 1">
                        <div class="flex align-items-center gap-3 mb-4">
                            <div
                                class="w-12 h-12 bg-emerald-100 border-round-lg flex align-items-center justify-center"
                            >
                                <i
                                    class="pi pi-search text-emerald-500 text-2xl"
                                ></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold m-0">
                                    Étape 1 : Choix du concours
                                </h2>
                                <p class="text-500 text-sm m-0">
                                    Sélectionnez le concours pour lequel vous
                                    souhaitez publier les résultats
                                </p>
                            </div>
                        </div>

                        <div class="field">
                            <label class="font-medium text-600 block mb-2">
                                <i
                                    class="pi pi-briefcase mr-2 text-emerald-500"
                                ></i>
                                Concours concerné
                            </label>
                            <Dropdown
                                v-model="selectedConcours"
                                :options="props.concours"
                                optionLabel="nom"
                                placeholder="Commencez à taper le nom du concours..."
                                @change="verifierResultatExistant"
                                :loading="checking"
                                :disabled="checking"
                                filter
                                class="w-full"
                            >
                                <template #option="slotProps">
                                    <div class="flex align-items-center py-2">
                                        <i
                                            class="pi pi-briefcase mr-3 text-emerald-500"
                                        ></i>
                                        <div class="flex-1">
                                            <div class="font-medium">
                                                {{ slotProps.option.nom }}
                                            </div>
                                            <small class="text-400">{{
                                                slotProps.option.organisateur ||
                                                "Organisateur non spécifié"
                                            }}</small>
                                        </div>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- Info concours sélectionné -->
                        <transition name="fade">
                            <div
                                v-if="selectedConcourInfo"
                                class="mt-4 p-3 bg-emerald-50 border-round-lg"
                            >
                                <div class="flex align-items-center gap-2">
                                    <i
                                        class="pi pi-info-circle text-emerald-500"
                                    ></i>
                                    <span class="text-sm text-600"
                                        >Concours sélectionné :</span
                                    >
                                    <span class="font-semibold">{{
                                        selectedConcourInfo.nom
                                    }}</span>
                                </div>
                                <div
                                    class="flex align-items-center gap-2 mt-1 text-sm text-500"
                                >
                                    <i class="pi pi-user"></i>
                                    <span
                                        >Organisateur :
                                        {{
                                            selectedConcourInfo.organisateur
                                        }}</span
                                    >
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- ⭐ Étape 2 : Information résultat(s) existant(s) - NON BLOQUANT -->
                    <transition name="slide">
                        <div
                            v-if="
                                currentStep === 2 &&
                                resultatExiste &&
                                !montrerFormulaire
                            "
                            class="p-4 border-top-1 surface-border"
                        >
                            <div
                                class="bg-blue-50 border-left-4 border-blue-500 p-4 border-round-lg"
                            >
                                <div
                                    class="flex flex-column md:flex-row align-items-center justify-content-between gap-4"
                                >
                                    <div class="flex align-items-start gap-3">
                                        <div
                                            class="w-10 h-10 bg-blue-100 border-round-lg flex align-items-center justify-center flex-shrink-0"
                                        >
                                            <i
                                                class="pi pi-info-circle text-blue-500 text-2xl"
                                            ></i>
                                        </div>
                                        <div>
                                            <h3
                                                class="text-lg font-semibold text-blue-800 m-0"
                                            >
                                                {{ resultatCount }} résultat(s)
                                                existant(s)
                                            </h3>
                                            <p
                                                class="text-blue-600 text-sm mt-2 mb-0 max-w-30rem"
                                            >
                                                Il y a déjà
                                                {{ resultatCount }} résultat(s)
                                                pour ce concours. Vous pouvez
                                                créer un nouveau résultat
                                                supplémentaire.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 w-full md:w-auto">
                                        <Button
                                            label="Créer un nouveau résultat"
                                            icon="pi pi-plus-circle"
                                            class="p-button-success flex-1 md:flex-initial"
                                            @click="
                                                initialiserNouveauFormulaire
                                            "
                                        />
                                        <Button
                                            label="Annuler"
                                            icon="pi pi-times"
                                            class="p-button-outlined p-button-secondary flex-1 md:flex-initial"
                                            @click="reinitialiserTout"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>

                    <!-- Étape 3 : Formulaire de création -->
                    <transition name="slide">
                        <div
                            v-if="montrerFormulaire"
                            class="p-4 border-top-1 surface-border"
                        >
                            <div class="flex align-items-center gap-3 mb-4">
                                <div
                                    class="w-12 h-12 bg-emerald-100 border-round-lg flex align-items-center justify-center"
                                >
                                    <i
                                        class="pi pi-cog text-emerald-500 text-2xl"
                                    ></i>
                                </div>
                                <div>
                                    <h2 class="text-xl font-semibold m-0">
                                        Étape 3 : Configuration finale
                                    </h2>
                                    <p class="text-500 text-sm m-0">
                                        Paramétrez les informations du résultat
                                        à publier
                                    </p>
                                </div>
                            </div>

                            <form
                                @submit.prevent="soumettreResultat"
                                class="grid"
                            >
                                <!-- Intitulé -->
                                <div class="col-12 mb-4">
                                    <label
                                        class="font-medium text-600 block mb-2"
                                    >
                                        <i
                                            class="pi pi-tag mr-2 text-emerald-500"
                                        ></i>
                                        Intitulé officiel du résultat
                                    </label>
                                    <InputText
                                        v-model="form.intitule"
                                        class="w-full"
                                        :class="{
                                            'p-invalid': form.errors.intitule,
                                        }"
                                        placeholder="Ex: Résultats définitifs - Concours INFAS 2026"
                                    />
                                    <small
                                        v-if="form.errors.intitule"
                                        class="p-error block mt-1"
                                    >
                                        {{ form.errors.intitule }}
                                    </small>
                                </div>

                                <!-- Visibilité -->
                                <div class="col-12 md:col-8 mb-4">
                                    <label
                                        class="font-medium text-600 block mb-2"
                                    >
                                        <i
                                            class="pi pi-eye mr-2 text-emerald-500"
                                        ></i>
                                        Niveau de visibilité
                                    </label>
                                    <div class="flex gap-2">
                                        <Button
                                            v-for="option in statusOptions"
                                            :key="option.value"
                                            :label="
                                                option.label.split(' - ')[0]
                                            "
                                            :icon="option.icon"
                                            class="flex-1"
                                            :class="{
                                                'p-button-success':
                                                    option.value === 'publié' &&
                                                    form.statut === 'publié',
                                                'p-button-warning':
                                                    option.value ===
                                                        'en preparation' &&
                                                    form.statut ===
                                                        'en preparation',
                                                'p-button-outlined':
                                                    form.statut !==
                                                    option.value,
                                            }"
                                            type="button"
                                            @click="form.statut = option.value"
                                        />
                                    </div>
                                </div>

                                <!-- Référence -->
                                <div class="col-12 md:col-4 mb-4">
                                    <label
                                        class="font-medium text-600 block mb-2"
                                    >
                                        <i
                                            class="pi pi-hashtag mr-2 text-emerald-500"
                                        ></i>
                                        Référence
                                    </label>
                                    <div class="p-inputgroup">
                                        <span class="p-inputgroup-addon bg-100"
                                            >REF</span
                                        >
                                        <InputText
                                            :value="form.concour_id"
                                            disabled
                                            class="bg-50"
                                        />
                                    </div>
                                </div>

                                <!-- ⭐ AJOUT : Information sur le nombre de résultats existants -->
                                <div
                                    v-if="resultatCount > 0"
                                    class="col-12 mb-4"
                                >
                                    <div class="bg-blue-50 p-3 border-round-lg">
                                        <i
                                            class="pi pi-info-circle text-blue-500 mr-2"
                                        ></i>
                                        <span class="text-sm"
                                            >Ce sera le résultat n°{{
                                                resultatCount + 1
                                            }}
                                            pour ce concours.</span
                                        >
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div
                                    class="col-12 mt-4 flex justify-content-end gap-2"
                                >
                                    <Button
                                        type="button"
                                        label="Annuler"
                                        icon="pi pi-times"
                                        class="p-button-outlined p-button-secondary"
                                        @click="reinitialiserTout"
                                    />
                                    <Button
                                        type="submit"
                                        label="Créer résultat"
                                        icon="pi pi-check-circle"
                                        :loading="form.processing"
                                        class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                                    />
                                </div>
                            </form>
                        </div>
                    </transition>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animations */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.slide-enter-from,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateY(-20px);
}

.slide-enter-to,
.slide-leave-from {
    max-height: 500px;
    opacity: 1;
    transform: translateY(0);
}

/* Amélioration du focus */
:deep(.p-inputtext:focus),
:deep(.p-dropdown:focus),
:deep(.p-button:focus) {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important;
}

/* Style pour le stepper */
.bg-emerald-500 {
    background-color: #10b981;
}

/* Responsive */
@media (max-width: 768px) {
    .flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>
