<script setup>
import { useForm, Head } from "@inertiajs/vue3";
import { computed, ref, watch, nextTick } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import axios from "axios";

import Select from "primevue/select";
import Button from "primevue/button";
import Message from "primevue/message";
import FileUpload from "primevue/fileupload";
import Divider from "primevue/divider";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    concours: Array,
    user: Object,
    preselectedConcourId: [Number, String],
});

const toast = useToast();
const loadingFiles = ref({});
const showHourInfo = ref(true);

// ⭐ Références pour les FileUpload (pour forcer le reset)
const fileUploadRefs = ref({});

const form = useForm("PostulerPersist", {
    concour_id: props.preselectedConcourId
        ? Number(props.preselectedConcourId)
        : null, // ⭐ Conversion en Number
    specialite_id: null,
    nationalite: "Malienne",
    demande_lettre: "Demande standard",
    certificat_nationalite: null,
    certificat_name: null,
    demande_manuscrite: null,
    demande_name: null,
    pieces: {},
    pieces_names: {},
});

const selectedConcours = computed(() => {
    return props.concours.find((c) => c.id === form.concour_id) || null;
});

watch(
    () => form.concour_id,
    (newVal, oldVal) => {
        if (newVal !== oldVal && oldVal !== null) {
            form.specialite_id = null;
            form.certificat_nationalite = null;
            form.certificat_name = null;
            form.demande_manuscrite = null;
            form.demande_name = null;
            form.pieces = {};
            form.pieces_names = {};
            loadingFiles.value = {};
        }
    },
);

const hasSpecialites = computed(() => {
    return (
        selectedConcours.value?.has_specialites === true &&
        selectedConcours.value?.specialites?.length > 0
    );
});

const isUploading = computed(() =>
    Object.values(loadingFiles.value).some((v) => v === true),
);

// ⭐ Fonction d'upload CORRIGÉE
const onUpload = async (event, field, isDynamic = false) => {
    const file = event.files[0];
    if (!file) return;

    // Vérification taille : 1 Mo max
    if (file.size > 1024 * 1024) {
        toast.add({
            severity: "error",
            summary: "Fichier trop volumineux",
            detail: "Le fichier ne doit pas dépasser 1 Mo.",
            life: 5000,
        });
        // ⭐ Réinitialiser le FileUpload pour permettre un nouvel essai
        resetFileUpload(field);
        return;
    }

    const formData = new FormData();
    formData.append("file", file);
    loadingFiles.value[field] = true;

    try {
        const response = await axios.post(route("upload.temp"), formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        const { path, name } = response.data;

        if (isDynamic) {
            form.pieces[field] = path;
            form.pieces_names[field] = name;
        } else {
            form[field] = path;
            form[field + "_name"] = name;
        }

        if (form.errors[field]) delete form.errors[field];

        toast.add({
            severity: "success",
            summary: "Fichier prêt",
            detail: `${name} téléchargé avec succès.`,
            life: 2000,
        });
    } catch (e) {
        console.error("Erreur upload:", e);

        // Réinitialiser le champ
        if (isDynamic) {
            delete form.pieces[field];
            delete form.pieces_names[field];
        } else {
            form[field] = null;
            form[field + "_name"] = null;
        }

        let errorMessage = "Échec du téléchargement. Veuillez réessayer.";
        if (e.response?.status === 413) {
            errorMessage = "Fichier trop volumineux (max 1 Mo).";
        } else if (e.response?.status === 422) {
            errorMessage =
                e.response.data?.message || "Type de fichier non autorisé.";
        }

        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: errorMessage,
            life: 5000,
        });

        // ⭐ Réinitialiser le FileUpload pour permettre un nouvel essai
        resetFileUpload(field);
    } finally {
        loadingFiles.value[field] = false;
    }
};

// ⭐ NOUVELLE FONCTION : Réinitialiser un FileUpload
const resetFileUpload = (field) => {
    nextTick(() => {
        const ref = fileUploadRefs.value[field];
        if (ref) {
            // Réinitialiser l'input file
            ref.clear();
            // Forcer la mise à jour du composant
            if (ref.$el) {
                const input = ref.$el.querySelector('input[type="file"]');
                if (input) {
                    input.value = "";
                }
            }
        }
    });
};

// ⭐ Supprimer un fichier
const removeFile = (field, isDynamic = false) => {
    if (isDynamic) {
        delete form.pieces[field];
        delete form.pieces_names[field];
    } else {
        form[field] = null;
        form[field + "_name"] = null;
    }

    // ⭐ Réinitialiser le FileUpload
    resetFileUpload(field);

    toast.add({
        severity: "info",
        summary: "Fichier retiré",
        detail: "Vous pouvez sélectionner un autre fichier.",
        life: 2000,
    });
};

const submit = () => {
    if (!form.concour_id) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez sélectionner un concours.",
            life: 3000,
        });
        return;
    }

    if (hasSpecialites.value && !form.specialite_id) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Veuillez sélectionner une spécialité.",
            life: 3000,
        });
        return;
    }

    if (!form.certificat_nationalite) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Le certificat de nationalité est obligatoire.",
            life: 3000,
        });
        return;
    }

    if (!form.demande_manuscrite) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "La demande manuscrite est obligatoire.",
            life: 3000,
        });
        return;
    }

    if (selectedConcours.value?.pieces?.length) {
        for (const piece of selectedConcours.value.pieces) {
            if (piece.is_required && !form.pieces[piece.slug]) {
                toast.add({
                    severity: "error",
                    summary: "Pièce manquante",
                    detail: `Le document "${piece.nom_document}" est obligatoire.`,
                    life: 3000,
                });
                return;
            }
        }
    }

    form.post(route("candidat-postuler.store"), {
        preserveScroll: true,
        onSuccess: (page) => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail:
                    page.props.flash?.success ||
                    "Candidature enregistrée avec succès.",
                life: 5000,
            });
            form.reset();
            form.pieces = {};
            form.pieces_names = {};
            loadingFiles.value = {};
        },
        onError: (errors) => {
            if (errors.error) {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: errors.error,
                    life: 8000,
                });
            }
            const otherErrors = Object.entries(errors)
                .filter(([key]) => key !== "error")
                .flatMap(([, msgs]) => msgs);
            otherErrors.forEach((msg) => {
                toast.add({
                    severity: "error",
                    summary: "Erreur",
                    detail: msg,
                    life: 5000,
                });
            });
        },
    });
};

const currentTime = new Date();
const currentHour = currentTime.getHours();
const isAllowedHour = currentHour >= 8 && currentHour < 16;
</script>

<template>
    <AppLayout>
        <Toast />
        <Head title="Déposer ma candidature" />

        <div class="card shadow-2 border-round p-3 p-md-4">
            <div
                class="text-900 font-bold text-xl sm:text-2xl md:text-3xl mb-5 flex align-items-center gap-2 sm:gap-3"
            >
                <i
                    class="pi pi-send text-primary text-xl sm:text-2xl md:text-3xl"
                ></i>
                <span class="break-word">Postuler à un concours</span>
            </div>

            <Message
                v-if="showHourInfo"
                severity="info"
                class="mb-4"
                :closable="true"
                @close="showHourInfo = false"
            >
                <div class="flex flex-column gap-2">
                    <span class="font-bold text-sm"
                        >📅 Informations importantes :</span
                    >
                    <ul
                        class="text-xs sm:text-sm m-0 pl-3"
                        style="list-style-type: disc"
                    >
                        <li>
                            Dépôt des candidatures :
                            <strong>24H/24H</strong> .
                        </li>
                        <li
                            v-if="!isAllowedHour"
                            class="text-orange-500 font-bold"
                        >
                            ⚠️ Actuellement {{ currentHour }}h. Vous pouvez
                            postuler
                        </li>
                        <li>
                            Tous les champs marqués
                            <span class="text-red-500">*</span> sont
                            obligatoires.
                        </li>
                        <li>
                            Taille maximale par fichier : <strong>1 Mo</strong>.
                        </li>
                    </ul>
                </div>
            </Message>

            <Message
                v-if="form.errors.error"
                severity="error"
                class="mb-4"
                :closable="true"
                @close="form.errors.error = null"
            >
                {{ form.errors.error }}
            </Message>

            <form @submit.prevent="submit">
                <!-- Section 1: Choix du concours -->
                <section class="mb-6">
                    <div class="flex align-items-center mb-4">
                        <span
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold flex-shrink-0"
                            >1</span
                        >
                        <span class="text-lg md:text-xl font-semibold"
                            >Sélection du Concours</span
                        >
                    </div>
                    <div class="p-fluid">
                        <div class="field">
                            <label
                                for="concours"
                                class="font-medium text-700 text-sm md:text-base"
                            >
                                Concours <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="concours"
                                v-model="form.concour_id"
                                :options="concours"
                                optionLabel="intitule"
                                optionValue="id"
                                placeholder="Rechercher un concours..."
                                filter
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.concour_id }"
                                panelClass="responsive-select-panel"
                            >
                                <template #option="slotProps">
                                    <div class="select-option-item">
                                        <span class="select-option-title">{{
                                            slotProps.option.intitule
                                        }}</span>
                                        <small
                                            class="select-option-sub"
                                            v-if="
                                                slotProps.option.diplome_requis
                                            "
                                        >
                                            {{
                                                slotProps.option.diplome_requis
                                            }}
                                            | Âge:
                                            {{ slotProps.option.age }} ans
                                        </small>
                                    </div>
                                </template>
                            </Select>
                            <small
                                class="p-error"
                                v-if="form.errors.concour_id"
                                >{{ form.errors.concour_id }}</small
                            >

                            <!-- Info concours sélectionné - RESPONSIVE MOBILE -->
                            <div
                                v-if="selectedConcours"
                                class="mt-3 p-3 md:p-4 surface-50 border-round text-sm"
                            >
                                <div class="concours-info-container">
                                    <!-- Titre -->
                                    <div class="concours-info-row">
                                        <div class="concours-info-icon">
                                            <i
                                                class="pi pi-info-circle text-primary"
                                            ></i>
                                        </div>
                                        <div class="concours-info-content">
                                            <span class="concours-info-label"
                                                >Concours</span
                                            >
                                            <strong
                                                class="concours-info-value"
                                                >{{
                                                    selectedConcours.intitule
                                                }}</strong
                                            >
                                        </div>
                                    </div>

                                    <!-- Âge limite -->
                                    <div
                                        v-if="selectedConcours.age"
                                        class="concours-info-row"
                                    >
                                        <div class="concours-info-icon">
                                            <i class="pi pi-calendar"></i>
                                        </div>
                                        <div class="concours-info-content">
                                            <span class="concours-info-label"
                                                >Âge limite</span
                                            >
                                            <strong class="concours-info-value"
                                                >{{
                                                    selectedConcours.age
                                                }}
                                                ans</strong
                                            >
                                        </div>
                                    </div>

                                    <!-- Diplôme requis -->
                                    <div
                                        v-if="
                                            selectedConcours.diplome_requis &&
                                            selectedConcours.diplome_requis !==
                                                'Aucun'
                                        "
                                        class="concours-info-row"
                                    >
                                        <div class="concours-info-icon">
                                            <i class="pi pi-book"></i>
                                        </div>
                                        <div class="concours-info-content">
                                            <span class="concours-info-label"
                                                >Diplôme requis</span
                                            >
                                            <strong
                                                class="concours-info-value"
                                                >{{
                                                    selectedConcours.diplome_requis
                                                }}</strong
                                            >
                                        </div>
                                    </div>

                                    <!-- Date limite -->
                                    <div
                                        v-if="selectedConcours.date_cloture"
                                        class="concours-info-row"
                                    >
                                        <div class="concours-info-icon">
                                            <i class="pi pi-clock"></i>
                                        </div>
                                        <div class="concours-info-content">
                                            <span class="concours-info-label"
                                                >Date limite</span
                                            >
                                            <strong class="concours-info-value">
                                                {{
                                                    new Date(
                                                        selectedConcours.date_cloture,
                                                    ).toLocaleDateString(
                                                        "fr-FR",
                                                    )
                                                }}
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Spécialité -->
                        <div class="field mt-3" v-if="hasSpecialites">
                            <label
                                for="specialite"
                                class="font-medium text-700 text-sm md:text-base"
                            >
                                Spécialité <span class="text-red-500">*</span>
                            </label>
                            <Select
                                id="specialite"
                                v-model="form.specialite_id"
                                :options="selectedConcours.specialites"
                                optionLabel="nom"
                                optionValue="id"
                                placeholder="Choisissez une spécialité"
                                class="w-full"
                                :class="{
                                    'p-invalid': form.errors.specialite_id,
                                }"
                                panelClass="responsive-select-panel"
                            >
                                <template #option="slotProps">
                                    <div class="select-option-item">
                                        <span class="select-option-title">{{
                                            slotProps.option.nom
                                        }}</span>
                                        <small
                                            class="select-option-sub"
                                            v-if="
                                                slotProps.option
                                                    .places_disponibles
                                            "
                                        >
                                            {{
                                                slotProps.option
                                                    .places_disponibles
                                            }}
                                            place(s)
                                        </small>
                                    </div>
                                </template>
                            </Select>
                            <small
                                class="p-error"
                                v-if="form.errors.specialite_id"
                                >{{ form.errors.specialite_id }}</small
                            >
                        </div>
                    </div>
                </section>

                <Divider v-if="form.concour_id" />

                <!-- Section 2: Pièces Communes -->
                <section v-if="form.concour_id" class="mt-6 animate-fadein">
                    <div class="flex align-items-center mb-4">
                        <span
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold"
                            >2</span
                        >
                        <span class="text-lg md:text-xl font-semibold"
                            >Pièces Communes Obligatoires</span
                        >
                    </div>

                    <div class="grid">
                        <!-- ⭐ Certificat de Nationalité - Label visible -->
                        <div class="field col-12 md:col-6">
                            <label class="font-medium text-700">
                                Certificat de Nationalité
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="upload-wrapper">
                                <div class="flex align-items-center gap-2">
                                    <FileUpload
                                        v-if="!form.certificat_nationalite"
                                        :ref="
                                            (el) =>
                                                (fileUploadRefs[
                                                    'certificat_nationalite'
                                                ] = el)
                                        "
                                        mode="basic"
                                        :auto="true"
                                        :customUpload="true"
                                        @select="
                                            onUpload(
                                                $event,
                                                'certificat_nationalite',
                                            )
                                        "
                                        accept=".pdf,image/*"
                                        :maxFileSize="1024000"
                                        :disabled="
                                            loadingFiles[
                                                'certificat_nationalite'
                                            ]
                                        "
                                        chooseLabel="📄 Choisir (1 Mo max)"
                                        class="custom-upload"
                                    />
                                    <template v-else>
                                        <FileUpload
                                            :ref="
                                                (el) =>
                                                    (fileUploadRefs[
                                                        'certificat_nationalite'
                                                    ] = el)
                                            "
                                            mode="basic"
                                            :auto="true"
                                            :customUpload="true"
                                            @select="
                                                onUpload(
                                                    $event,
                                                    'certificat_nationalite',
                                                )
                                            "
                                            accept=".pdf,image/*"
                                            :maxFileSize="1024000"
                                            :disabled="
                                                loadingFiles[
                                                    'certificat_nationalite'
                                                ]
                                            "
                                            chooseLabel="📄 Changer"
                                            class="custom-upload upload-success"
                                        />
                                        <Button
                                            icon="pi pi-times"
                                            severity="danger"
                                            text
                                            rounded
                                            size="small"
                                            @click="
                                                removeFile(
                                                    'certificat_nationalite',
                                                )
                                            "
                                            v-tooltip.top="'Retirer'"
                                            :disabled="
                                                loadingFiles[
                                                    'certificat_nationalite'
                                                ]
                                            "
                                        />
                                    </template>
                                </div>

                                <small
                                    class="file-info"
                                    v-if="form.certificat_name"
                                >
                                    <i class="pi pi-file-pdf"></i>
                                    {{ form.certificat_name }}
                                </small>

                                <small
                                    v-if="
                                        loadingFiles['certificat_nationalite']
                                    "
                                    class="text-primary text-xs mt-1"
                                >
                                    <i class="pi pi-spinner pi-spin mr-1"></i>
                                    Téléchargement...
                                </small>
                            </div>
                        </div>

                        <!-- ⭐ Demande Manuscrite - Label visible -->
                        <div class="field col-12 md:col-6">
                            <label class="font-medium text-700">
                                Demande Manuscrite
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="upload-wrapper">
                                <div class="flex align-items-center gap-2">
                                    <FileUpload
                                        v-if="!form.demande_manuscrite"
                                        :ref="
                                            (el) =>
                                                (fileUploadRefs[
                                                    'demande_manuscrite'
                                                ] = el)
                                        "
                                        mode="basic"
                                        :auto="true"
                                        :customUpload="true"
                                        @select="
                                            onUpload(
                                                $event,
                                                'demande_manuscrite',
                                            )
                                        "
                                        accept=".pdf,image/*"
                                        :maxFileSize="1024000"
                                        :disabled="
                                            loadingFiles['demande_manuscrite']
                                        "
                                        chooseLabel="📄 Choisir (1 Mo max)"
                                        class="custom-upload"
                                    />
                                    <template v-else>
                                        <FileUpload
                                            :ref="
                                                (el) =>
                                                    (fileUploadRefs[
                                                        'demande_manuscrite'
                                                    ] = el)
                                            "
                                            mode="basic"
                                            :auto="true"
                                            :customUpload="true"
                                            @select="
                                                onUpload(
                                                    $event,
                                                    'demande_manuscrite',
                                                )
                                            "
                                            accept=".pdf,image/*"
                                            :maxFileSize="1024000"
                                            :disabled="
                                                loadingFiles[
                                                    'demande_manuscrite'
                                                ]
                                            "
                                            chooseLabel="📄 Changer"
                                            class="custom-upload upload-success"
                                        />
                                        <Button
                                            icon="pi pi-times"
                                            severity="danger"
                                            text
                                            rounded
                                            size="small"
                                            @click="
                                                removeFile('demande_manuscrite')
                                            "
                                            v-tooltip.top="'Retirer'"
                                            :disabled="
                                                loadingFiles[
                                                    'demande_manuscrite'
                                                ]
                                            "
                                        />
                                    </template>
                                </div>

                                <small
                                    class="file-info"
                                    v-if="form.demande_name"
                                >
                                    <i class="pi pi-file-pdf"></i>
                                    {{ form.demande_name }}
                                </small>

                                <small
                                    v-if="loadingFiles['demande_manuscrite']"
                                    class="text-primary text-xs mt-1"
                                >
                                    <i class="pi pi-spinner pi-spin mr-1"></i>
                                    Téléchargement...
                                </small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Section 3: Pièces Spécifiques -->
                <section
                    v-if="form.concour_id && selectedConcours?.pieces?.length"
                    class="mt-6 animate-fadein"
                >
                    <div class="flex align-items-center mb-4">
                        <span
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold"
                            >3</span
                        >
                        <span class="text-lg md:text-xl font-semibold"
                            >Pièces Spécifiques</span
                        >
                    </div>
                    <div class="grid">
                        <div
                            v-for="piece in selectedConcours.pieces"
                            :key="piece.id"
                            class="field col-12 md:col-6"
                        >
                            <label class="font-medium text-700">
                                {{ piece.nom_document }}
                                <span
                                    v-if="piece.is_required"
                                    class="text-red-500"
                                    >*</span
                                >
                            </label>
                            <div class="upload-wrapper">
                                <div class="flex align-items-center gap-2">
                                    <!-- Cas 1 : Aucun fichier choisi pour cette pièce spécifique -->
                                    <FileUpload
                                        v-if="!form.pieces[piece.slug]"
                                        :ref="
                                            (el) =>
                                                (fileUploadRefs[piece.slug] =
                                                    el)
                                        "
                                        mode="basic"
                                        :auto="true"
                                        :customUpload="true"
                                        @select="
                                            onUpload($event, piece.slug, true)
                                        "
                                        accept=".pdf,image/*"
                                        :maxFileSize="1024000"
                                        :disabled="loadingFiles[piece.slug]"
                                        chooseLabel="📄 Choisir (1 Mo max)"
                                        class="custom-upload"
                                    />

                                    <!-- Cas 2 : Fichier déjà chargé -->
                                    <template v-else>
                                        <FileUpload
                                            :ref="
                                                (el) =>
                                                    (fileUploadRefs[
                                                        piece.slug
                                                    ] = el)
                                            "
                                            mode="basic"
                                            :auto="true"
                                            :customUpload="true"
                                            @select="
                                                onUpload(
                                                    $event,
                                                    piece.slug,
                                                    true,
                                                )
                                            "
                                            accept=".pdf,image/*"
                                            :maxFileSize="1024000"
                                            :disabled="loadingFiles[piece.slug]"
                                            chooseLabel="📄 Changer"
                                            class="custom-upload upload-success"
                                        />
                                        <Button
                                            icon="pi pi-times"
                                            severity="danger"
                                            text
                                            rounded
                                            size="small"
                                            @click="
                                                removeFile(piece.slug, true)
                                            "
                                            v-tooltip.top="'Retirer'"
                                            :disabled="loadingFiles[piece.slug]"
                                        />
                                    </template>
                                </div>

                                <!-- Affichage du nom de la pièce jointe -->
                                <small
                                    class="file-info"
                                    v-if="form.pieces_names[piece.slug]"
                                >
                                    <i class="pi pi-file-pdf"></i>
                                    {{ form.pieces_names[piece.slug] }}
                                </small>

                                <!-- État de chargement -->
                                <small
                                    v-if="loadingFiles[piece.slug]"
                                    class="text-primary text-xs mt-1"
                                >
                                    <i class="pi pi-spinner pi-spin mr-1"></i>
                                    Téléchargement...
                                </small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Bouton Soumettre -->
                <div
                    v-if="form.concour_id"
                    class="mt-8 flex justify-content-center sm:justify-content-end p-4 surface-50 border-round"
                >
                    <Button
                        type="submit"
                        label="Soumettre ma candidature"
                        icon="pi pi-check"
                        :disabled="
                            isUploading ||
                            (hasSpecialites && !form.specialite_id)
                        "
                        :loading="form.processing"
                        class="p-button-primary w-full sm:w-auto px-6"
                    />
                </div>

                <div
                    v-else
                    class="text-center p-8 border-round surface-50 border-dashed border-2 border-300"
                >
                    <i class="pi pi-info-circle text-4xl text-400 mb-3"></i>
                    <p class="text-600">
                        Sélectionnez un concours pour afficher les pièces
                        justificatives requises.
                    </p>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* ============================================ */
/* ⭐ ANIMATIONS */
/* ============================================ */
.animate-fadein {
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ============================================ */
/* ⭐ UPLOAD */
/* ============================================ */
.custom-upload :deep(.p-fileupload-choose) {
    min-width: 150px;
    max-width: 200px;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    white-space: nowrap;
}

.upload-success :deep(.p-fileupload-choose) {
    background-color: #22c55e !important;
    border-color: #22c55e !important;
}

.upload-wrapper {
    width: 100%;
}

.file-info {
    display: block;
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    color: #166534;
    background-color: #f0fdf4;
    border-radius: 0.375rem;
    word-break: break-word;
}

:deep(.p-fileupload-content) {
    display: none !important;
}

:deep(.p-fileupload-filename) {
    display: none !important;
}

/* ============================================ */
/* ⭐ FORMULAIRES */
/* ============================================ */
.p-error {
    color: #e24c4c;
    font-size: 0.75rem;
}

.field {
    margin-bottom: 1rem;
}

.break-word {
    word-wrap: break-word;
}

/* ============================================ */
/* ⭐ GRID RESPONSIVE */
/* ============================================ */
.grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 768px) {
    .grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

/* ============================================ */
/* ⭐ SELECT RESPONSIVE COMPLET */
/* ============================================ */

/* Select lui-même - responsive */
:deep(.p-select) {
    width: 100% !important;
}

:deep(.p-select .p-select-label) {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    line-height: 1.4 !important;
    padding: 0.75rem !important;
    font-size: 0.875rem !important;
}

@media (max-width: 640px) {
    :deep(.p-select .p-select-label) {
        font-size: 0.8rem !important;
        padding: 0.65rem 0.75rem !important;
    }

    :deep(.p-select .p-select-dropdown) {
        width: 2.5rem !important;
    }
}

/* Panel du select */
:deep(.p-select-panel) {
    max-width: 100vw !important;
}

@media (max-width: 640px) {
    :deep(.p-select-panel) {
        width: 92vw !important;
        left: 4vw !important;
        max-height: 65vh !important;
    }
}

@media (min-width: 641px) {
    :deep(.p-select-panel) {
        max-width: 500px !important;
        min-width: 280px !important;
    }
}

/* Items du select */
:deep(.p-select-panel .p-select-item) {
    white-space: normal !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    padding: 0.75rem 1rem !important;
    line-height: 1.4 !important;
}

@media (max-width: 640px) {
    :deep(.p-select-panel .p-select-item) {
        padding: 0.875rem 1rem !important;
        font-size: 0.8rem !important;
    }
}

/* Option vide */
:deep(.p-select-panel .p-select-empty-message) {
    padding: 1.5rem !important;
    text-align: center !important;
}

/* ============================================ */
/* ⭐ SELECT OPTION ITEMS */
/* ============================================ */
.select-option-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.5rem 0;
    width: 100%;
    min-width: 0;
}

.select-option-title {
    font-weight: 500;
    font-size: 0.875rem;
    word-break: break-word;
    white-space: normal;
    line-height: 1.4;
}

.select-option-sub {
    color: var(--text-color-secondary);
    font-size: 0.75rem;
    word-break: break-word;
    white-space: normal;
}

@media (max-width: 640px) {
    .select-option-title {
        font-size: 0.8rem;
    }

    .select-option-sub {
        font-size: 0.7rem;
    }
}

/* ============================================ */
/* ⭐ CONCOURS INFO - RESPONSIVE MOBILE */
/* ============================================ */
.concours-info-container {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.concours-info-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.concours-info-row:last-child {
    border-bottom: none;
}

.concours-info-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: var(--color-primary-light, #d1fae5);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--color-primary, #10b981);
    font-size: 0.875rem;
}

.concours-info-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.concours-info-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-color-secondary, #6b7280);
    font-weight: 600;
}

.concours-info-value {
    font-size: 0.875rem;
    color: var(--text-color, #1f2937);
    word-break: break-word;
    overflow-wrap: break-word;
    line-height: 1.4;
}

@media (min-width: 768px) {
    .concours-info-container {
        gap: 0.25rem;
    }

    .concours-info-row {
        padding: 0.5rem 0;
    }

    .concours-info-label {
        font-size: 0.7rem;
    }

    .concours-info-value {
        font-size: 0.9rem;
    }
}

@media (max-width: 640px) {
    .concours-info-row {
        padding: 0.875rem 0;
    }

    .concours-info-icon {
        width: 2.25rem;
        height: 2.25rem;
    }

    .concours-info-label {
        font-size: 0.7rem;
    }

    .concours-info-value {
        font-size: 0.85rem;
    }
}

/* ============================================ */
/* ⭐ DARK MODE */
/* ============================================ */
.dark .concours-info-row {
    border-bottom-color: rgba(255, 255, 255, 0.06);
}

.dark .concours-info-icon {
    background: rgba(16, 185, 129, 0.15);
}

/* ============================================ */
/*  */
/* ============================================ */
.word-break {
    word-break: break-word;
}

.mt-0\.5 {
    margin-top: 0.125rem;
}
</style>
