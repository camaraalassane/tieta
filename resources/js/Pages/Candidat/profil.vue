<script setup>
import { useForm, Head } from "@inertiajs/vue3";
import { ref, onUnmounted, onMounted } from "vue";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import InputText from "primevue/inputtext";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Select from "primevue/select";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";

const props = defineProps({
    user: Object,
    isOwner: Boolean,
    hasActiveCandidature: Boolean,
    showBanner: Boolean,
});

const optionsSexe = ref(["Masculin", "Feminin"]);
const diplomesList = [
    "DEF",
    "BAC",
    "CAP",
    "BT",
    "DUT",
    "Licence",
    "Master",
    "Doctorat",
];
const toast = useToast();
const lastSaveTime = ref(null);

const form = useForm({
    nom: props.user?.name || "",
    email: props.user?.email || "",
    prenom: props.user?.prenom || "",
    telephone: props.user?.profil?.telephone || "",
    sexe: props.user?.profil?.sexe || props.user?.sexe || "",
    date_naissance: props.user?.profil?.date_naissance || "",
    lieu_naissance: props.user?.profil?.lieu_naissance || "",
    region: props.user?.profil?.region || "",
    nina: props.user?.profil?.nina || "",
    prenom_pere: props.user?.profil?.prenom_pere || "",
    prenom_mere: props.user?.profil?.prenom_mere || "",
    nom_mere: props.user?.profil?.nom_mere || "",
    photo_identite: null,
    carte_identite: null,
    permis: null,
    ...Object.fromEntries(diplomesList.map((d) => [d, null])),
});

// Gestionnaire pour Ctrl+S ou Cmd+S
const handleKeyboardSave = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === "s") {
        event.preventDefault();
        submit();
    }
};

onMounted(() => {
    document.addEventListener("keydown", handleKeyboardSave);
});

const photoPreviewUrl = ref(
    props.user?.profil?.photo_identite
        ? `/storage/${props.user.profil.photo_identite}`
        : null,
);
const photoInput = ref(null);

// Validation des fichiers
const validateFile = (file, field) => {
    const maxSize = field === "photo_identite" ? 2 * 1024 * 1024 : 1024 * 1024;
    const maxSizeText = field === "photo_identite" ? "2 Mo" : "1 Mo";

    if (file.size > maxSize) {
        toast.add({
            severity: "error",
            summary: "Fichier trop volumineux",
            detail: `Le fichier ne doit pas dépasser ${maxSizeText}`,
            life: 3000,
        });
        return false;
    }

    const fileExtension = file.name.split(".").pop().toLowerCase();

    if (field === "photo_identite") {
        if (!["jpg", "jpeg", "png"].includes(fileExtension)) {
            toast.add({
                severity: "error",
                summary: "Format non supporté",
                detail: "La photo d'identité doit être au format JPG ou PNG",
                life: 3000,
            });
            return false;
        }
    } else {
        if (fileExtension !== "pdf") {
            toast.add({
                severity: "error",
                summary: "Format non supporté",
                detail: "Le document doit être au format PDF",
                life: 3000,
            });
            return false;
        }
    }
    return true;
};

// ⭐ Gestionnaire de fichier - assigne le fichier et déclenche la soumission
const handleFileUpload = (event, field) => {
    if (!props.isOwner || props.hasActiveCandidature) return;
    const file = event.target.files[0];
    if (file) {
        if (!validateFile(file, field)) {
            event.target.value = "";
            return;
        }

        form[field] = file;

        if (field === "photo_identite") {
            const reader = new FileReader();
            reader.onload = (e) => {
                photoPreviewUrl.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        // ⭐ Déclencher la soumission automatique après avoir assigné le fichier
        submit();
    }
};

// ⭐ Soumission unique pour TOUS les champs (textes + fichiers)
const submit = () => {
    if (!props.isOwner) {
        return;
    }

    if (props.hasActiveCandidature) {
        toast.add({
            severity: "warn",
            summary: "Action impossible",
            detail: "Vous ne pouvez pas modifier votre profil car vous avez une candidature en traitement.",
            life: 5000,
        });
        return;
    }

    form.post(route("candidat-profil.update"), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            lastSaveTime.value = new Date();
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Profil mis à jour",
                life: 2000,
            });
        },
        onError: (errors) => {
            console.error("Submit error:", errors);
            toast.add({
                severity: "error",
                summary: "Erreur",
                detail: "Erreur lors de la mise à jour",
                life: 3000,
            });
        },
    });
};

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeyboardSave);
});

const formattedLastSaveTime = () => {
    if (!lastSaveTime.value) return "";
    return `Dernière sauvegarde : ${lastSaveTime.value.toLocaleTimeString()}`;
};
</script>

<template>
    <AppLayout>
        <Toast />
        <Head :title="isOwner ? 'Compléter mon profil' : 'Profil candidat'" />

        <div class="card p-2 md:p-2 border-0 shadow-none bg-transparent">
            <div v-if="isOwner && showBanner" class="alert-banner mb-4">
                <div class="alert-banner-inner">
                    <i class="pi pi-info-circle alert-icon"></i>
                    <div class="alert-marquee">
                        <div class="alert-marquee-content">
                            ⚠️ Tant qu'une candidature est en traitement, vous
                            ne pouvez plus modifier votre profil. Formats :
                            Photo (JPG/PNG max 500Ko) | Documents (PDF max 1Mo)
                            ⚠️
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="sakai-card p-6 md:p-10">
                <div
                    class="flex justify-between items-center mb-8 flex-wrap gap-4"
                >
                    <h1
                        class="text-3xl font-light text-surface-900 dark:text-surface-0"
                    >
                        {{
                            isOwner
                                ? "Compléter mon profil"
                                : "Profil candidat : " +
                                  form.nom +
                                  " " +
                                  form.prenom
                        }}
                    </h1>
                    <Tag
                        v-if="hasActiveCandidature && isOwner"
                        severity="warn"
                        value="Candidature en cours - Profil verrouillé"
                        class="text-sm"
                    />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Section Gauche -->
                    <div class="flex flex-col gap-6">
                        <h2
                            class="text-xl font-semibold border-b border-emerald-500/20 pb-2 text-emerald-600 flex items-center gap-2"
                        >
                            <i class="pi pi-user"></i> Informations de base
                        </h2>

                        <div
                            class="flex flex-col items-center sm:flex-row gap-6 mb-4"
                        >
                            <div class="relative group">
                                <div
                                    class="passport-photo bg-surface-100 dark:bg-surface-800 border-2 border-dashed border-emerald-500/30 rounded-xl flex items-center justify-center overflow-hidden"
                                    :class="{
                                        'opacity-60': hasActiveCandidature,
                                    }"
                                >
                                    <img
                                        v-if="photoPreviewUrl"
                                        :src="photoPreviewUrl"
                                        alt="Photo"
                                        class="object-cover w-full h-full object-top"
                                    />
                                    <i
                                        v-else
                                        class="pi pi-camera text-surface-400"
                                        style="font-size: 2.5rem"
                                    ></i>
                                </div>
                                <div
                                    v-if="form.processing"
                                    class="absolute inset-0 bg-black/50 flex items-center justify-center rounded-xl"
                                >
                                    <i
                                        class="pi pi-spin pi-spinner text-white text-2xl"
                                    ></i>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <span class="text-sm font-medium"
                                    >Photo d'identité</span
                                >
                                <input
                                    v-if="isOwner && !hasActiveCandidature"
                                    type="file"
                                    @change="
                                        handleFileUpload(
                                            $event,
                                            'photo_identite',
                                        )
                                    "
                                    ref="photoInput"
                                    class="hidden"
                                    accept="image/jpeg,image/png,image/jpg"
                                />
                                <Button
                                    v-if="isOwner && !hasActiveCandidature"
                                    type="button"
                                    icon="pi pi-refresh"
                                    label="Changer la photo"
                                    class="p-button-outlined p-button-sm"
                                    :disabled="form.processing"
                                    @click="photoInput.click()"
                                />
                                <Tag
                                    v-if="!isOwner"
                                    severity="secondary"
                                    value="Photo officielle"
                                    class="w-max"
                                />
                                <small class="text-surface-500"
                                    >JPG/PNG uniquement (Max 500Ko)</small
                                >
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm">Nom</label>
                            <InputText
                                v-model="form.nom"
                                :disabled="!isOwner || hasActiveCandidature"
                                fluid
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm">Prénom</label>
                            <InputText
                                v-model="form.prenom"
                                :disabled="!isOwner || hasActiveCandidature"
                                fluid
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm">Sexe</label>
                            <Select
                                v-model="form.sexe"
                                :options="optionsSexe"
                                placeholder="Sélectionnez"
                                :disabled="!isOwner || hasActiveCandidature"
                                fluid
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm">Email</label>
                            <InputText
                                v-model="form.email"
                                type="email"
                                disabled
                                fluid
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm"
                                >Téléphone</label
                            >
                            <InputText
                                v-model="form.telephone"
                                :disabled="!isOwner || hasActiveCandidature"
                                placeholder="+223..."
                                type="tel"
                                fluid
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="font-semibold text-sm"
                                    >Date naissance</label
                                >
                                <InputText
                                    v-model="form.date_naissance"
                                    type="date"
                                    :disabled="!isOwner || hasActiveCandidature"
                                    fluid
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="font-semibold text-sm"
                                    >Lieu naissance</label
                                >
                                <InputText
                                    v-model="form.lieu_naissance"
                                    :disabled="!isOwner || hasActiveCandidature"
                                    placeholder="Ex: Bamako"
                                    fluid
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm">Région</label>
                            <InputText
                                v-model="form.region"
                                :disabled="!isOwner || hasActiveCandidature"
                                placeholder="Votre région"
                                fluid
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm">NINA</label>
                            <InputText
                                v-model="form.nina"
                                :disabled="!isOwner || hasActiveCandidature"
                                placeholder="Numéro NINA"
                                fluid
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label class="font-semibold text-sm"
                                    >Prénom du père</label
                                >
                                <InputText
                                    v-model="form.prenom_pere"
                                    :disabled="!isOwner || hasActiveCandidature"
                                    placeholder="Prénom du père"
                                    fluid
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="font-semibold text-sm"
                                    >Prénom de la mère</label
                                >
                                <InputText
                                    v-model="form.prenom_mere"
                                    :disabled="!isOwner || hasActiveCandidature"
                                    placeholder="Prénom de la mère"
                                    fluid
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="font-semibold text-sm"
                                >Nom de la mère</label
                            >
                            <InputText
                                v-model="form.nom_mere"
                                :disabled="!isOwner || hasActiveCandidature"
                                placeholder="Nom de jeune fille de la mère"
                                fluid
                            />
                        </div>
                    </div>

                    <!-- Section Droite -->
                    <div class="flex flex-col gap-6">
                        <h2
                            class="text-xl font-semibold border-b border-emerald-500/20 pb-2 text-emerald-600 flex items-center gap-2"
                        >
                            <i class="pi pi-file"></i> Documents officiels
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <label class="font-semibold text-sm italic"
                                        >Carte d'Identité</label
                                    >
                                    <a
                                        v-if="props.user.profil?.carte_identite"
                                        :href="
                                            '/storage/' +
                                            props.user.profil.carte_identite
                                        "
                                        target="_blank"
                                        class="text-emerald-600 text-xs hover:underline"
                                    >
                                        <i
                                            :class="
                                                !isOwner
                                                    ? 'pi pi-eye'
                                                    : 'pi pi-download'
                                            "
                                            class="text-[10px]"
                                        ></i>
                                        {{ !isOwner ? "Voir" : "Télécharger" }}
                                    </a>
                                </div>
                                <input
                                    v-if="isOwner && !hasActiveCandidature"
                                    type="file"
                                    @change="
                                        handleFileUpload(
                                            $event,
                                            'carte_identite',
                                        )
                                    "
                                    class="custom-file-input"
                                    accept=".pdf"
                                    :disabled="form.processing"
                                />
                                <div
                                    v-else
                                    class="p-2 border-round surface-100 text-xs text-500 text-center border"
                                >
                                    PDF max 1Mo
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <label class="font-semibold text-sm italic"
                                        >Permis de conduire</label
                                    >
                                    <a
                                        v-if="props.user.profil?.permis"
                                        :href="
                                            '/storage/' +
                                            props.user.profil.permis
                                        "
                                        target="_blank"
                                        class="text-emerald-600 text-xs hover:underline"
                                    >
                                        <i
                                            :class="
                                                !isOwner
                                                    ? 'pi pi-eye'
                                                    : 'pi pi-download'
                                            "
                                            class="text-[10px]"
                                        ></i>
                                        {{ !isOwner ? "Voir" : "Télécharger" }}
                                    </a>
                                </div>
                                <input
                                    v-if="isOwner && !hasActiveCandidature"
                                    type="file"
                                    @change="handleFileUpload($event, 'permis')"
                                    class="custom-file-input"
                                    accept=".pdf"
                                    :disabled="form.processing"
                                />
                                <div
                                    v-else
                                    class="p-2 border-round surface-100 text-xs text-500 text-center border"
                                >
                                    PDF max 1Mo
                                </div>
                            </div>
                        </div>

                        <div
                            class="mt-4 p-4 bg-emerald-50/50 rounded-xl border border-emerald-100"
                        >
                            <h3
                                class="font-bold text-emerald-700 mb-4 uppercase text-xs"
                            >
                                Justificatifs des diplômes (PDF max 1Mo)
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    v-for="dip in diplomesList"
                                    :key="dip"
                                    class="flex flex-col gap-1 p-2 bg-white rounded-lg border shadow-sm"
                                >
                                    <div
                                        class="flex justify-between items-center px-1"
                                    >
                                        <label
                                            class="text-[10px] uppercase font-bold"
                                            >{{ dip }}</label
                                        >
                                        <a
                                            v-if="props.user.profil?.[dip]"
                                            :href="
                                                '/storage/' +
                                                props.user.profil[dip]
                                            "
                                            target="_blank"
                                            class="text-emerald-500 text-[9px] font-bold"
                                        >
                                            <i
                                                :class="
                                                    !isOwner
                                                        ? 'pi pi-external-link'
                                                        : 'pi pi-download'
                                                "
                                                class="text-[8px]"
                                            ></i>
                                            {{
                                                !isOwner
                                                    ? "Consulter"
                                                    : "Télécharger"
                                            }}
                                        </a>
                                    </div>
                                    <input
                                        v-if="isOwner && !hasActiveCandidature"
                                        type="file"
                                        @change="handleFileUpload($event, dip)"
                                        class="custom-file-input-small"
                                        accept=".pdf"
                                        :disabled="form.processing"
                                    />
                                    <div
                                        v-else-if="!props.user.profil?.[dip]"
                                        class="text-[9px] text-400 italic px-1"
                                    >
                                        Non fourni
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-between items-center mt-8 border-t pt-6"
                >
                    <div class="flex items-center gap-3">
                        <div
                            v-if="form.processing"
                            class="flex items-center gap-2 text-emerald-600"
                        >
                            <i class="pi pi-spin pi-spinner"></i>
                            <span class="text-sm">Enregistrement...</span>
                        </div>
                        <div
                            v-else-if="!hasActiveCandidature && isOwner"
                            class="flex items-center gap-2 text-surface-400"
                        >
                            <i class="pi pi-check-circle"></i>
                            <span class="text-sm"
                                >Sauvegarde automatique active</span
                            >
                            <span v-if="lastSaveTime" class="text-xs ml-2">{{
                                formattedLastSaveTime()
                            }}</span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <Button
                            v-if="isOwner && !hasActiveCandidature"
                            type="submit"
                            label="Enregistrer maintenant"
                            icon="pi pi-save"
                            :loading="form.processing"
                            class="p-button-emerald"
                        />
                        <div
                            v-if="!isOwner"
                            class="flex align-items-center gap-2 text-surface-500 italic bg-surface-100 p-3 border-round"
                        >
                            <i class="pi pi-lock"></i>
                            <span>Mode consultation</span>
                        </div>
                        <div
                            v-if="isOwner && hasActiveCandidature"
                            class="flex align-items-center gap-2 text-orange-600 bg-orange-50 p-3 border-round"
                        >
                            <i class="pi pi-lock"></i>
                            <span class="text-sm"
                                >⚠️ Modification impossible</span
                            >
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes slideDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.alert-banner {
    animation: slideDown 0.3s ease-out;
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border-left: 6px solid #f97316;
    border-radius: 12px;
    overflow: hidden;
}

.alert-banner-inner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
}

.alert-icon {
    flex-shrink: 0;
    font-size: 1.5rem;
    color: #f97316;
}

.alert-marquee {
    flex: 1;
    overflow: hidden;
    white-space: nowrap;
}

.alert-marquee-content {
    animation: scrollText 20s linear infinite;
    font-size: 0.9rem;
    font-weight: 500;
    color: #9a3412;
}

@keyframes scrollText {
    0% {
        transform: translateX(100%);
    }
    100% {
        transform: translateX(-100%);
    }
}

.alert-marquee:hover .alert-marquee-content {
    animation-play-state: paused;
}

.dark .alert-banner {
    background: linear-gradient(135deg, #2d1a0a 0%, #3b2614 100%);
}

.dark .alert-marquee-content {
    color: #fdba74;
}

.dark .alert-icon {
    color: #fdba74;
}

@media (max-width: 768px) {
    .alert-marquee {
        white-space: normal;
    }
    .alert-marquee-content {
        animation: none;
        text-align: center;
    }
}

.sakai-card {
    @apply bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-2xl shadow-sm;
}

.p-button-emerald {
    @apply bg-emerald-500 border-emerald-500 hover:bg-emerald-600 text-white transition-all shadow-md shadow-emerald-500/20 !important;
}

.passport-photo {
    width: 140px;
    height: 180px;
}

.custom-file-input {
    @apply w-full text-xs text-surface-500 
    file:mr-3 file:py-2 file:px-3 
    file:rounded-lg file:border-0 
    file:text-xs file:font-bold
    file:bg-emerald-50 file:text-emerald-700
    hover:file:bg-emerald-100
    dark:file:bg-emerald-900/30 dark:file:text-emerald-400 cursor-pointer;
}

.custom-file-input-small {
    @apply w-full text-[10px] text-surface-500 
    file:mr-2 file:py-1 file:px-2  
    file:rounded file:border-0 
    file:text-[10px] file:font-bold
    file:bg-surface-100 file:text-surface-700
    hover:file:bg-emerald-100 hover:file:text-emerald-700
    dark:file:bg-surface-700 dark:file:text-surface-300 cursor-pointer;
}

@media (max-width: 640px) {
    .passport-photo {
        width: 120px;
        height: 154px;
    }
}
</style>
