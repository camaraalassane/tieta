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
});

const toast = useToast();
const loadingFiles = ref({});
const showHourInfo = ref(true);

// ⭐ Références pour les FileUpload (pour forcer le reset)
const fileUploadRefs = ref({});

const form = useForm("PostulerPersist", {
    concour_id: null,
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
                            <strong>08h00 à 16h00</strong> uniquement.
                        </li>
                        <li
                            v-if="!isAllowedHour"
                            class="text-orange-500 font-bold"
                        >
                            ⚠️ Actuellement {{ currentHour }}h. Revenez à partir
                            de 08h00.
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
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold"
                            >1</span
                        >
                        <span class="text-lg md:text-xl font-semibold"
                            >Sélection du Concours</span
                        >
                    </div>
                    <div class="p-fluid">
                        <div class="field">
                            <label for="concours" class="font-medium text-700">
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
                            />
                            <small
                                class="p-error"
                                v-if="form.errors.concour_id"
                                >{{ form.errors.concour_id }}</small
                            >

                            <div
                                v-if="selectedConcours"
                                class="mt-3 p-3 surface-50 border-round text-sm"
                            >
                                <div class="flex flex-column gap-2">
                                    <div>
                                        <i
                                            class="pi pi-info-circle text-primary mr-1"
                                        ></i
                                        ><strong>{{
                                            selectedConcours.intitule
                                        }}</strong>
                                    </div>
                                    <div v-if="selectedConcours.age">
                                        <i class="pi pi-calendar mr-1"></i> Âge
                                        limite :
                                        <strong
                                            >{{
                                                selectedConcours.age
                                            }}
                                            ans</strong
                                        >
                                    </div>
                                    <div
                                        v-if="
                                            selectedConcours.diplome_requis &&
                                            selectedConcours.diplome_requis !==
                                                'Aucun'
                                        "
                                    >
                                        <i class="pi pi-book mr-1"></i> Diplôme
                                        requis :
                                        <strong>{{
                                            selectedConcours.diplome_requis
                                        }}</strong>
                                    </div>
                                    <div v-if="selectedConcours.date_cloture">
                                        <i class="pi pi-clock mr-1"></i> Date
                                        limite :
                                        <strong>{{
                                            new Date(
                                                selectedConcours.date_cloture,
                                            ).toLocaleDateString("fr-FR")
                                        }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="field mt-3" v-if="hasSpecialites">
                            <label
                                for="specialite"
                                class="font-medium text-700"
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
                            />
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
                        <!-- Certificat de Nationalité -->
                        <div class="field col-12 md:col-6">
                            <label class="font-medium text-700">
                                Certificat de Nationalité
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="upload-wrapper">
                                <div class="flex align-items-center gap-2">
                                    <!-- ⭐ Utiliser v-if/v-else pour forcer le re-render -->
                                    <FileUpload
                                        v-if="!form.certificat_nationalite"
                                        :ref="
                                            (el) =>
                                                (fileUploadRefs[
                                                    'certificat_nationalite'
                                                ] = el)
                                        "
                                        mode="basic"
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

                        <!-- Demande Manuscrite -->
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
                                    <FileUpload
                                        v-if="!form.pieces[piece.slug]"
                                        :ref="
                                            (el) =>
                                                (fileUploadRefs[piece.slug] =
                                                    el)
                                        "
                                        mode="basic"
                                        @select="
                                            onUpload($event, piece.slug, true)
                                        "
                                        accept=".pdf,image/*"
                                        :maxFileSize="1024000"
                                        :disabled="loadingFiles[piece.slug]"
                                        chooseLabel="📄 Choisir (1 Mo max)"
                                        class="custom-upload"
                                    />
                                    <template v-else>
                                        <FileUpload
                                            :ref="
                                                (el) =>
                                                    (fileUploadRefs[
                                                        piece.slug
                                                    ] = el)
                                            "
                                            mode="basic"
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
                                <small
                                    class="file-info"
                                    v-if="form.pieces_names[piece.slug]"
                                >
                                    <i class="pi pi-file-pdf"></i>
                                    {{ form.pieces_names[piece.slug] }}
                                </small>
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
</style>
