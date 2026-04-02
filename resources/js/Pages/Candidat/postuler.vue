<script setup>
import { useForm, Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
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
});

const toast = useToast();
const loadingFiles = ref({});
const showHourInfo = ref(true);

const form = useForm("PostulerPersist", {
    concour_id: null,
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

const isUploading = computed(() =>
    Object.values(loadingFiles.value).some((v) => v === true),
);

const onUpload = async (event, field, isDynamic = false) => {
    const file = event.files[0];
    const formData = new FormData();
    formData.append("file", file);
    loadingFiles.value[field] = true;

    try {
        const response = await axios.post(route("upload.temp"), formData);
        const { path, name } = response.data;

        if (isDynamic) {
            form.pieces[field] = path;
            form.pieces_names[field] = name;
        } else {
            form[field] = path;
            form[field + "_name"] = name;
        }
        toast.add({
            severity: "success",
            summary: "Fichier prêt",
            detail: name,
            life: 2000,
        });
    } catch (e) {
        toast.add({
            severity: "error",
            summary: "Erreur",
            detail: "Échec de l'upload temporaire",
        });
    } finally {
        loadingFiles.value[field] = false;
    }
};

const submit = () => {
    form.post(route("candidat-postuler.store"), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Candidature enregistrée avec succès.",
            });
            form.reset();
        },
    });
};

// Obtenir l'heure actuelle formatée
const currentTime = new Date();
const currentHour = currentTime.getHours();
const isAllowedHour = currentHour >= 8 && currentHour < 16;
const nextAllowedTime = new Date();
nextAllowedTime.setDate(nextAllowedTime.getDate() + 1);
nextAllowedTime.setHours(8, 0, 0);
</script>

<template>
    <AppLayout>
        <Toast />
        <Head title="Déposer ma candidature" />

        <div class="card shadow-2 border-round">
            <div
                class="text-900 font-bold text-3xl mb-5 flex align-items-center gap-3"
            >
                <i class="pi pi-send text-primary text-3xl"></i>
                Postuler à un concours
            </div>

            <!-- Message d'information sur les horaires de dépôt -->
            <Message
                v-if="showHourInfo"
                severity="info"
                class="mb-4"
                :closable="true"
                @close="showHourInfo = false"
            >
                <div class="flex flex-column">
                    <span class="font-bold mb-1"
                        >📅 Informations importantes :</span
                    >
                    <span
                        >• Les candidatures ne peuvent être déposées qu'entre
                        <strong>08h00 et 16h00</strong>.</span
                    >
                    <span v-if="!isAllowedHour" class="text-orange-500 mt-1">
                        ⚠️ Actuellement, il est {{ currentHour }}h. Les dépôts
                        sont possibles demain à partir de 08h00.
                    </span>
                    <span
                        >• Pour postuler, Vous devez remplir les criteres
                        <strong>d'age limite indiqué</strong>, au 31 décembre de
                        l'année en cours.</span
                    >
                </div>
            </Message>

            <Message
                v-if="form.errors.error"
                severity="error"
                class="mb-4"
                :closable="true"
            >
                {{ form.errors.error }}
            </Message>

            <form @submit.prevent="submit">
                <section class="mb-6">
                    <div class="flex align-items-center mb-4">
                        <span
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold"
                            >1</span
                        >
                        <span class="text-xl font-semibold"
                            >Sélection du Concours</span
                        >
                    </div>
                    <div class="p-fluid">
                        <div class="field">
                            <label for="concours" class="font-medium text-700"
                                >Concours disponible
                                <span class="text-red-500">*</span></label
                            >
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

                            <!-- Message explicatif sur le calcul de l'âge -->
                            <div
                                v-if="selectedConcours"
                                class="mt-2 p-2 surface-50 border-round text-sm"
                            >
                                <i
                                    class="pi pi-info-circle text-primary mr-1"
                                ></i>
                                <span
                                    v-if="
                                        selectedConcours.date_cloture &&
                                        new Date(
                                            selectedConcours.date_cloture,
                                        ).getDate() === 28
                                    "
                                >
                                    ℹ️ Pour ce concours, l'âge est calculé au 31
                                    décembre de l'année en cours.
                                </span>
                                <span
                                    v-if="
                                        selectedConcours.age_min ||
                                        selectedConcours.age_max
                                    "
                                >
                                    ℹ️ Âge requis :
                                    <span v-if="selectedConcours.age_min"
                                        >minimum
                                        {{ selectedConcours.age_min }} ans</span
                                    >
                                    <span
                                        v-if="
                                            selectedConcours.age_max &&
                                            selectedConcours.age_min
                                        "
                                    >
                                        -
                                    </span>
                                    <span v-if="selectedConcours.age_max"
                                        >maximum
                                        {{ selectedConcours.age_max }} ans</span
                                    >
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <Divider v-if="form.concour_id" />

                <section v-if="form.concour_id" class="mt-6 animate-fadein">
                    <div class="flex align-items-center mb-4">
                        <span
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold"
                            >2</span
                        >
                        <span class="text-xl font-semibold"
                            >Pièces Communes</span
                        >
                    </div>

                    <div class="grid p-fluid">
                        <div class="field col-12 md:col-6">
                            <label class="font-medium text-700"
                                >Certificat de Nationalité
                                <span class="text-red-500">*</span></label
                            >
                            <FileUpload
                                mode="basic"
                                @select="
                                    onUpload($event, 'certificat_nationalite')
                                "
                                accept=".pdf,image/*"
                                :maxFileSize="5000000"
                                :disabled="
                                    loadingFiles['certificat_nationalite']
                                "
                                :chooseLabel="
                                    form.certificat_nationalite
                                        ? 'Document conservé'
                                        : 'Choisir le document'
                                "
                                :class="
                                    form.certificat_nationalite
                                        ? 'p-button-success p-button-outlined'
                                        : 'p-button-outlined'
                                "
                            />
                            <small
                                class="p-success block mt-2 font-medium"
                                v-if="form.certificat_name"
                                ><i class="pi pi-check"></i>
                                {{ form.certificat_name }}</small
                            >
                        </div>

                        <div class="field col-12 md:col-6">
                            <label class="font-medium text-700"
                                >Demande Manuscrite
                                <span class="text-red-500">*</span></label
                            >
                            <FileUpload
                                mode="basic"
                                @select="onUpload($event, 'demande_manuscrite')"
                                accept=".pdf,image/*"
                                :maxFileSize="5000000"
                                :disabled="loadingFiles['demande_manuscrite']"
                                :chooseLabel="
                                    form.demande_manuscrite
                                        ? 'Document conservé'
                                        : 'Choisir le document'
                                "
                                :class="
                                    form.demande_manuscrite
                                        ? 'p-button-success p-button-outlined'
                                        : 'p-button-outlined'
                                "
                            />
                            <small
                                class="p-success block mt-2 font-medium"
                                v-if="form.demande_name"
                                ><i class="pi pi-check"></i>
                                {{ form.demande_name }}</small
                            >
                        </div>
                    </div>
                </section>

                <section
                    v-if="form.concour_id && selectedConcours?.pieces?.length"
                    class="mt-6 animate-fadein"
                >
                    <div class="flex align-items-center mb-4">
                        <span
                            class="bg-primary text-white border-circle w-2rem h-2rem flex align-items-center justify-content-center mr-3 font-bold"
                            >3</span
                        >
                        <span class="text-xl font-semibold"
                            >Pièces Spécifiques</span
                        >
                    </div>
                    <div class="grid p-fluid">
                        <div
                            v-for="piece in selectedConcours.pieces"
                            :key="piece.id"
                            class="field col-12 md:col-6"
                        >
                            <label class="font-medium text-700"
                                >{{ piece.nom_document }}
                                <span
                                    v-if="piece.is_required"
                                    class="text-red-500"
                                    >*</span
                                ></label
                            >
                            <FileUpload
                                mode="basic"
                                @select="onUpload($event, piece.slug, true)"
                                :disabled="loadingFiles[piece.slug]"
                                :chooseLabel="
                                    form.pieces[piece.slug]
                                        ? 'Document prêt'
                                        : 'Téléverser'
                                "
                                :class="
                                    form.pieces[piece.slug]
                                        ? 'p-button-success p-button-outlined'
                                        : 'p-button-info p-button-outlined'
                                "
                            />
                            <small
                                class="p-success block mt-2 font-medium"
                                v-if="form.pieces_names[piece.slug]"
                                ><i class="pi pi-paperclip"></i>
                                {{ form.pieces_names[piece.slug] }}</small
                            >
                        </div>
                    </div>
                </section>

                <div
                    v-if="form.concour_id"
                    class="mt-8 flex justify-content-end p-4 surface-50 border-round"
                >
                    <Button
                        type="submit"
                        label="Soumettre ma candidature"
                        icon="pi pi-check"
                        :disabled="isUploading"
                        :loading="form.processing"
                        class="p-button-primary px-6"
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
:deep(.p-fileupload-choose) {
    width: 100%;
}
.p-error {
    color: #e24c4c;
    font-size: 0.875rem;
}
.p-success {
    color: #22c55e;
    font-size: 0.875rem;
}
</style>
