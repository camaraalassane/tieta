<script setup>
import { ref, computed } from "vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Card from 'primevue/card';
import Stepper from 'primevue/stepper';
import Step from 'primevue/step';
import Panel from 'primevue/panel';
import Badge from 'primevue/badge';
import Divider from 'primevue/divider';

const props = defineProps({
  concours: Array
});

const toast = useToast();
const selectedConcours = ref(null);
const resultatExiste = ref(false);
const montrerFormulaire = ref(false);
const currentStep = ref(1);

const statusOptions = [
  {
    label: "Brouillon - Accès restreint",
    value: "en preparation",
    icon: "pi pi-lock",
    color: "warning"
  },
  { 
    label: "Publié - Visible par tous", 
    value: "publié", 
    icon: "pi pi-globe",
    color: "success"
  }
];

const form = useForm({
  concour_id: null,
  intitule: "",
  statut: "en preparation"
});

// Concours sélectionné formaté
const selectedConcourInfo = computed(() => {
  if (!selectedConcours.value) return null;
  return {
    nom: selectedConcours.value.nom,
    id: selectedConcours.value.id,
    date: selectedConcours.value.date_limite,
    candidats: selectedConcours.value.nb_candidats || 0
  };
});

const verifierResultatExistant = async () => {
  if (selectedConcours.value) {
    try {
      const response = await axios.get(
        `/api/check-resultat/${selectedConcours.value.id}`
      );
      if (response.data.exists) {
        resultatExiste.value = true;
        montrerFormulaire.value = false;
        currentStep.value = 2;
      } else {
        initialiserNouveauFormulaire();
      }
    } catch (error) {
      toast.add({
        severity: "error",
        summary: "Erreur",
        detail: "Vérification impossible",
        life: 3000
      });
    }
  }
};

const initialiserNouveauFormulaire = () => {
  resultatExiste.value = false;
  montrerFormulaire.value = true;
  currentStep.value = 3;
  form.concour_id = selectedConcours.value.id;
  form.intitule = `Résultats définitifs - ${selectedConcours.value.nom}`;
};

const reinitialiserTout = () => {
  selectedConcours.value = null;
  resultatExiste.value = false;
  montrerFormulaire.value = false;
  currentStep.value = 1;
  form.reset();
};

const soumettreResultat = () => {
  form.post(route("concour-creerResultat.store"), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: "Succès",
        detail: "Le résultat a été généré avec succès",
        life: 3000
      });
      reinitialiserTout();
    },
    onError: (errors) => {
      toast.add({
        severity: "error",
        summary: "Erreur",
        detail: "Veuillez corriger les erreurs du formulaire",
        life: 5000
      });
    }
  });
};
</script>

<template>
  <AppLayout>
    <div class="p-fluid px-4 md:px-6 lg:px-8">
      <!-- En-tête -->
      <div class="grid mb-6">
        <div class="col-12">
          <Card class="shadow-lg border-none overflow-hidden bg-gradient-to-r from-emerald-50 to-white dark:from-emerald-900/20 dark:to-gray-900">
            <template #content>
              <div class="flex flex-column lg:flex-row align-items-center justify-content-between gap-4">
                <div class="flex align-items-center gap-4">
                  <div class="w-16 h-16 bg-emerald-500 border-round-xl flex align-items-center justify-center">
                    <i class="pi pi-file-export text-white text-3xl"></i>
                  </div>
                  <div>
                    <h1 class="text-3xl font-bold text-900 m-0 mb-2">Création des résultats officiels</h1>
                    <p class="text-600 m-0 flex align-items-center gap-2">
                      <i class="pi pi-check-circle text-emerald-500"></i>
                      Gérez l'édition et la diffusion des résultats session 2026
                    </p>
                  </div>
                </div>
                
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Stepper de progression -->
      <div class="grid mb-4">
        <div class="col-12">
          <Card class="shadow-sm">
            <template #content>
              <div class="flex justify-content-between align-items-center px-4 py-2">
                <div class="flex align-items-center gap-4">
                  <div class="flex align-items-center" :class="{ 'opacity-50': currentStep < 1 }">
                    <span class="w-8 h-8 border-circle flex align-items-center justify-content-center font-bold mr-2"
                          :class="currentStep >= 1 ? 'bg-emerald-500 text-white' : 'bg-200 text-600'">1</span>
                    <span class="font-medium" :class="currentStep >= 1 ? 'text-900' : 'text-500'">Choix concours</span>
                  </div>
                  <i class="pi pi-arrow-right text-400"></i>
                  <div class="flex align-items-center" :class="{ 'opacity-50': currentStep < 2 }">
                    <span class="w-8 h-8 border-circle flex align-items-center justify-content-center font-bold mr-2"
                          :class="currentStep >= 2 ? 'bg-emerald-500 text-white' : 'bg-200 text-600'">2</span>
                    <span class="font-medium" :class="currentStep >= 2 ? 'text-900' : 'text-500'">Vérification</span>
                  </div>
                  <i class="pi pi-arrow-right text-400"></i>
                  <div class="flex align-items-center" :class="{ 'opacity-50': currentStep < 3 }">
                    <span class="w-8 h-8 border-circle flex align-items-center justify-content-center font-bold mr-2"
                          :class="currentStep >= 3 ? 'bg-emerald-500 text-white' : 'bg-200 text-600'">3</span>
                    <span class="font-medium" :class="currentStep >= 3 ? 'text-900' : 'text-500'">Configuration</span>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Carte principale -->
      <Card class="shadow-md">
        <template #content>
          <!-- Étape 1 : Sélection -->
          <div class="p-4">
            <div class="flex align-items-center gap-3 mb-4">
              <div class="w-12 h-12 bg-emerald-100 border-round-lg flex align-items-center justify-center">
                <i class="pi pi-search text-emerald-500 text-2xl"></i>
              </div>
              <div>
                <h2 class="text-xl font-semibold m-0">Étape 1 : Choix du concours</h2>
                <p class="text-500 text-sm m-0">Sélectionnez le concours pour lequel vous souhaitez publier les résultats</p>
              </div>
            </div>

            <div class="field">
              <label class="font-medium text-600 block mb-2">
                <i class="pi pi-briefcase mr-2 text-emerald-500"></i>
                Concours concerné
              </label>
              <Dropdown
                v-model="selectedConcours"
                :options="props.concours"
                optionLabel="nom"
                placeholder="Commencez à taper le nom du concours..."
                @change="verifierResultatExistant"
                filter
                class="w-full"
              >
                <template #option="slotProps">
                  <div class="flex align-items-center py-2">
                    <i class="pi pi-briefcase mr-3 text-emerald-500"></i>
                    <div class="flex-1">
                      <div class="font-medium">{{ slotProps.option.nom }}</div>
                      <small class="text-400">{{ slotProps.option.organisateur }}</small>
                    </div>
                  </div>
                </template>
              </Dropdown>
            </div>

            <!-- Info concours sélectionné -->
            <transition name="fade">
              <div v-if="selectedConcourInfo" class="mt-4 p-3 bg-emerald-50 border-round-lg">
                <div class="flex align-items-center gap-2">
                  <i class="pi pi-info-circle text-emerald-500"></i>
                  <span class="text-sm text-600">Concours sélectionné :</span>
                  <span class="font-semibold">{{ selectedConcourInfo.nom }}</span>
                </div>
              </div>
            </transition>
          </div>

          <!-- Étape 2 : Alerte résultat existant -->
          <transition name="slide">
            <div v-if="resultatExiste && !montrerFormulaire" class="p-4 border-top-1 surface-border">
              <div class="bg-orange-50 border-left-4 border-orange-500 p-4 border-round-lg">
                <div class="flex flex-column md:flex-row align-items-center justify-content-between gap-4">
                  <div class="flex align-items-start gap-3">
                    <div class="w-10 h-10 bg-orange-100 border-round-lg flex align-items-center justify-center flex-shrink-0">
                      <i class="pi pi-exclamation-triangle text-orange-500 text-2xl"></i>
                    </div>
                    <div>
                      <h3 class="text-lg font-semibold text-orange-800 m-0">Résultat déjà existant</h3>
                      <p class="text-orange-600 text-sm mt-2 mb-0 max-w-30rem">
                        Un résultat est déjà publié pour ce concours. Vous pouvez créer un nouveau résultat ou annuler.
                      </p>
                    </div>
                  </div>
                  <div class="flex gap-2 w-full md:w-auto">
                    <Button
                      label="Nouveau résultat"
                      icon="pi pi-plus-circle"
                      class="p-button-warning flex-1 md:flex-initial"
                      @click="initialiserNouveauFormulaire"
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
            <div v-if="montrerFormulaire" class="p-4 border-top-1 surface-border">
              <div class="flex align-items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-emerald-100 border-round-lg flex align-items-center justify-center">
                  <i class="pi pi-cog text-emerald-500 text-2xl"></i>
                </div>
                <div>
                  <h2 class="text-xl font-semibold m-0">Étape 3 : Configuration finale</h2>
                  <p class="text-500 text-sm m-0">Paramétrez les informations du résultat à publier</p>
                </div>
              </div>

              <form @submit.prevent="soumettreResultat" class="grid">
                <!-- Intitulé -->
                <div class="col-12 mb-4">
                  <label class="font-medium text-600 block mb-2">
                    <i class="pi pi-tag mr-2 text-emerald-500"></i>
                    Intitulé officiel du résultat
                  </label>
                  <InputText
                    v-model="form.intitule"
                    class="w-full"
                    :class="{'p-invalid': form.errors.intitule}"
                    placeholder="Ex: Résultats définitifs - Concours INFAS 2026"
                  />
                  <small v-if="form.errors.intitule" class="p-error block mt-1">
                    {{ form.errors.intitule }}
                  </small>
                </div>

                <!-- Visibilité -->
                <div class="col-12 md:col-8 mb-4">
                  <label class="font-medium text-600 block mb-2">
                    <i class="pi pi-eye mr-2 text-emerald-500"></i>
                    Niveau de visibilité
                  </label>
                  <div class="flex gap-2">
                    <Button
                      v-for="option in statusOptions"
                      :key="option.value"
                      :label="option.label.split(' - ')[0]"
                      :icon="option.icon"
                      class="flex-1"
                      :class="{
                        'p-button-success': option.value === 'publié' && form.statut === 'publié',
                        'p-button-warning': option.value === 'en preparation' && form.statut === 'en preparation',
                        'p-button-outlined': form.statut !== option.value
                      }"
                      @click="form.statut = option.value"
                    />
                  </div>
                </div>

                <!-- Référence -->
                <div class="col-12 md:col-4 mb-4">
                  <label class="font-medium text-600 block mb-2">
                    <i class="pi pi-hashtag mr-2 text-emerald-500"></i>
                    Référence
                  </label>
                  <div class="p-inputgroup">
                    <span class="p-inputgroup-addon bg-100">REF</span>
                    <InputText
                      :value="form.concour_id"
                      disabled
                      class="bg-50"
                    />
                  </div>
                </div>

                <!-- Actions -->
                <div class="col-12 mt-4 flex justify-content-end gap-2">
                  <Button
                    type="button"
                    label="Annuler"
                    icon="pi pi-times"
                    class="p-button-outlined p-button-secondary"
                    @click="reinitialiserTout"
                  />
                  <Button
                    type="submit"
                    label="Publier les résultats"
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