<script setup>
import { useForm, Head } from "@inertiajs/vue3";
import { ref } from "vue";
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
const form = useForm({
    nom: props.user?.name || "",
    email: props.user?.email || "",
    prenom: props.user?.prenom || "",
    telephone: props.user?.profil?.telephone || "",
    sexe: props.user?.profil?.sexe || props.user?.sexe || "",
    date_naissance: props.user?.profil?.date_naissance || "",
    lieu_naissance: props.user?.profil?.lieu_naissance || "",
    region: props.user?.profil?.region || "",
    photo_identite: null,
    carte_identite: null,
    permis: null,
    ...Object.fromEntries(diplomesList.map((d) => [d, null])),
});

const photoPreviewUrl = ref(
    props.user?.profil?.photo_identite
        ? `/storage/${props.user.profil.photo_identite}`
        : null,
);
const photoInput = ref(null);

const handleFileUpload = (event, field) => {
    if (!props.isOwner || props.hasActiveCandidature) return;
    const file = event.target.files[0];
    if (file) {
        form[field] = file;
        if (field === "photo_identite") {
            const reader = new FileReader();
            reader.onload = (e) => {
                photoPreviewUrl.value = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
};

const submit = () => {
    if (!props.isOwner) {
        console.error("Action interdite : vous n'êtes pas le propriétaire.");
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
            toast.add({
                severity: "success",
                summary: "Succès",
                detail: "Votre profil a été mis à jour avec succès",
                life: 3000,
            });
        },
        onError: () => {
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

<template>
    <AppLayout>
        <Toast />
        <Head :title="isOwner ? 'Compléter mon profil' : 'Profil candidat'" />

        <div class="card p-2 md:p-2 border-0 shadow-none bg-transparent">
            <!-- Bandeau d'alerte orange - s'affiche SEULEMENT pour le candidat (isOwner true) -->
            <div v-if="isOwner && showBanner" class="alert-banner mb-4">
                <div class="alert-banner-inner">
                    <i class="pi pi-info-circle alert-icon"></i>
                    <div class="alert-marquee">
                        <div class="alert-marquee-content">
                            ⚠️ Tant qu'une candidature est en traitement, vous
                            ne pouvez plus modifier votre profil. Cependant,
                            complétez correctement votre profil avant de
                            postuler à un concours. ⚠️
                        </div>
                        <div class="alert-marquee-content" aria-hidden="true">
                            ⚠️ Tant qu'une candidature est en traitement, vous
                            ne pouvez plus modifier votre profil. Cependant,
                            complétez correctement votre profil avant de
                            postuler à un concours. ⚠️
                        </div>
                    </div>
                </div>
            </div>

            <form
                @submit.prevent="
                    isOwner && !hasActiveCandidature ? submit() : null
                "
                class="sakai-card p-6 md:p-10"
            >
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
                    <!-- Section Gauche : Informations de base -->
                    <div class="flex flex-col gap-6">
                        <h2
                            class="text-xl font-semibold border-b border-emerald-500/20 pb-2 text-emerald-600 dark:text-emerald-400 flex items-center gap-2"
                        >
                            <i class="pi pi-user"></i> Informations de base
                        </h2>

                        <div
                            class="flex flex-col items-center sm:flex-row gap-6 mb-4"
                        >
                            <div class="relative group">
                                <div
                                    class="passport-photo bg-surface-100 dark:bg-surface-800 border-2 border-dashed border-emerald-500/30 rounded-xl flex items-center justify-center overflow-hidden transition-all"
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
                            </div>
                            <div class="flex flex-col gap-2">
                                <span
                                    class="text-sm font-medium text-surface-700 dark:text-surface-300"
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
                                    accept="image/*"
                                />
                                <Button
                                    v-if="isOwner && !hasActiveCandidature"
                                    type="button"
                                    icon="pi pi-refresh"
                                    label="Changer la photo"
                                    class="p-button-outlined p-button-sm"
                                    @click="photoInput.click()"
                                />

                                <Tag
                                    v-if="!isOwner"
                                    severity="secondary"
                                    value="Photo officielle"
                                    class="w-max"
                                />
                                <small class="text-surface-500"
                                    >Format Passeport (Max 2Mo)</small
                                >
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="nom" class="font-semibold text-sm"
                                >Nom</label
                            >
                            <InputText
                                id="nom"
                                v-model="form.nom"
                                :disabled="!isOwner || hasActiveCandidature"
                                fluid
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="prenom" class="font-semibold text-sm"
                                >Prénom</label
                            >
                            <InputText
                                id="prenom"
                                v-model="form.prenom"
                                :disabled="!isOwner || hasActiveCandidature"
                                fluid
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="sexe" class="font-semibold text-sm"
                                >Sexe</label
                            >
                            <Select
                                id="sexe"
                                v-model="form.sexe"
                                :options="optionsSexe"
                                placeholder="Sélectionnez le sexe"
                                :disabled="!isOwner || hasActiveCandidature"
                                fluid
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="email" class="font-semibold text-sm"
                                >Email</label
                            >
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="opacity-60"
                                disabled
                                fluid
                            />
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="telephone" class="font-semibold text-sm"
                                >Téléphone</label
                            >
                            <InputText
                                id="telephone"
                                v-model="form.telephone"
                                :disabled="!isOwner || hasActiveCandidature"
                                placeholder="+223..."
                                type="tel"
                                fluid
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-2">
                                <label
                                    for="date_naissance"
                                    class="font-semibold text-sm"
                                    >Date de naissance</label
                                >
                                <InputText
                                    id="date_naissance"
                                    v-model="form.date_naissance"
                                    type="date"
                                    :disabled="!isOwner || hasActiveCandidature"
                                    fluid
                                />
                            </div>
                            <div class="flex flex-col gap-2">
                                <label
                                    for="lieu_naissance"
                                    class="font-semibold text-sm"
                                    >Lieu de naissance</label
                                >
                                <InputText
                                    id="lieu_naissance"
                                    v-model="form.lieu_naissance"
                                    :disabled="!isOwner || hasActiveCandidature"
                                    placeholder="Ex: Bamako"
                                    fluid
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <label for="region" class="font-semibold text-sm"
                                >Région</label
                            >
                            <InputText
                                id="region"
                                v-model="form.region"
                                :disabled="!isOwner || hasActiveCandidature"
                                placeholder="Votre région"
                                fluid
                            />
                        </div>
                    </div>

                    <!-- Section Droite : Documents et Diplômes -->
                    <div class="flex flex-col gap-6">
                        <h2
                            class="text-xl font-semibold border-b border-emerald-500/20 pb-2 text-emerald-600 dark:text-emerald-400 flex items-center gap-2"
                        >
                            <i class="pi pi-file"></i> Documents et Diplômes
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
                                        class="text-emerald-600 text-xs hover:underline flex items-center gap-1"
                                    >
                                        <i
                                            :class="
                                                !isOwner
                                                    ? 'pi pi-eye'
                                                    : 'pi pi-download'
                                            "
                                            class="text-[10px]"
                                        ></i>
                                        {{
                                            !isOwner
                                                ? "Voir actuel"
                                                : "Télécharger"
                                        }}
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
                                    accept=".pdf,image/*"
                                />
                                <div
                                    v-else
                                    class="p-2 border-round surface-100 text-xs text-500 text-center border-1 border-300"
                                >
                                    Document archivé
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
                                        class="text-emerald-600 text-xs hover:underline flex items-center gap-1"
                                    >
                                        <i
                                            :class="
                                                !isOwner
                                                    ? 'pi pi-eye'
                                                    : 'pi pi-download'
                                            "
                                            class="text-[10px]"
                                        ></i>
                                        {{
                                            !isOwner
                                                ? "Voir actuel"
                                                : "Télécharger"
                                        }}
                                    </a>
                                </div>
                                <input
                                    v-if="isOwner && !hasActiveCandidature"
                                    type="file"
                                    @change="handleFileUpload($event, 'permis')"
                                    class="custom-file-input"
                                    accept=".pdf,image/*"
                                />
                                <div
                                    v-else
                                    class="p-2 border-round surface-100 text-xs text-500 text-center border-1 border-300"
                                >
                                    Document archivé
                                </div>
                            </div>
                        </div>

                        <!-- Liste des Diplômes -->
                        <div
                            class="mt-4 p-4 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100 dark:border-emerald-800"
                        >
                            <h3
                                class="font-bold text-emerald-700 dark:text-emerald-300 mb-4 uppercase text-xs tracking-widest flex items-center gap-2"
                            >
                                <i class="pi pi-upload"></i> Justificatifs des
                                diplômes
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    v-for="dip in diplomesList"
                                    :key="dip"
                                    class="flex flex-col gap-1 p-2 bg-white dark:bg-surface-800 rounded-lg border border-surface-200 shadow-sm"
                                >
                                    <div
                                        class="flex justify-between items-center px-1"
                                    >
                                        <label
                                            :for="dip"
                                            class="text-[10px] uppercase font-bold text-surface-600"
                                            >{{ dip }}</label
                                        >
                                        <a
                                            v-if="props.user.profil?.[dip]"
                                            :href="
                                                '/storage/' +
                                                props.user.profil[dip]
                                            "
                                            target="_blank"
                                            class="text-emerald-500 text-[9px] font-bold hover:text-emerald-700 flex items-center gap-1"
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
                                        :id="dip"
                                        @change="handleFileUpload($event, dip)"
                                        class="custom-file-input-small"
                                        accept=".pdf,image/*"
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

                <div class="flex justify-end mt-8 border-t pt-6">
                    <Button
                        v-if="isOwner && !hasActiveCandidature"
                        type="submit"
                        label="Enregistrer les modifications"
                        icon="pi pi-check"
                        :loading="form.processing"
                        class="p-button-emerald px-8"
                    />
                    <div
                        v-else-if="isOwner && hasActiveCandidature"
                        class="flex align-items-center gap-2 text-orange-600 bg-orange-50 p-3 border-round"
                    >
                        <i class="pi pi-lock"></i>
                        <span class="text-sm">
                            ⚠️ Modification impossible : vous avez une
                            candidature en traitement. Complétez votre profil
                            avant de postuler.
                        </span>
                    </div>
                    <div
                        v-else
                        class="flex align-items-center gap-2 text-surface-500 italic bg-surface-100 p-3 border-round"
                    >
                        <i class="pi pi-lock"></i>
                        <span>Mode consultation (Lecture seule)</span>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Animation d'entrée */
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
}

/* Style du bandeau */
.alert-banner {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    border-left: 6px solid #f97316;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
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

/* Conteneur du texte défilant */
.alert-marquee {
    flex: 1;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
}

.alert-marquee-content {
    display: inline-block;
    animation: scrollText 20s linear infinite;
    font-size: 0.9rem;
    font-weight: 500;
    color: #9a3412;
    padding-left: 100%;
}

/* Animation de défilement continue */
@keyframes scrollText {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-100%);
    }
}

/* Pause au survol */
.alert-marquee:hover .alert-marquee-content {
    animation-play-state: paused;
}

/* Mode sombre */
.dark .alert-banner {
    background: linear-gradient(135deg, #2d1a0a 0%, #3b2614 100%);
}

.dark .alert-marquee-content {
    color: #fdba74;
}

.dark .alert-icon {
    color: #fdba74;
}

/* Version responsive : sur mobile, le texte ne défile pas */
@media (max-width: 768px) {
    .alert-marquee {
        white-space: normal;
    }

    .alert-marquee-content {
        animation: none;
        padding-left: 0;
        white-space: normal;
        text-align: center;
    }

    .alert-banner-inner {
        flex-wrap: wrap;
        justify-content: center;
    }
}

.sakai-card {
    @apply bg-white dark:bg-surface-900 border border-surface-200 dark:border-surface-700 rounded-2xl shadow-sm;
}

.p-button-emerald {
    @apply bg-emerald-500 border-emerald-500 hover:bg-emerald-600 text-white transition-all shadow-md shadow-emerald-500/20 !important;
}

/* FIX FORMAT PASSEPORT (Ratio 3.5 / 4.5) */
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
