<script setup>
import { useForm } from "@inertiajs/vue3";
import { watchEffect, ref, computed } from "vue";

const props = defineProps({
    show: Boolean,
    title: String,
    roles: Object,
});

const emit = defineEmits(["close"]);

// État pour afficher/masquer les mots de passe
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: "",
    prenom: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
});

const create = () => {
    form.post(route("user.store"), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            form.reset();
        },
        onError: () => null,
    });
};

watchEffect(() => {
    if (props.show) {
        form.clearErrors();
        form.reset();
    }
});

// Force du mot de passe
const passwordStrength = computed(() => {
    if (!form.password) return null;
    
    let strength = 0;
    if (form.password.length >= 8) strength++;
    if (/[A-Z]/.test(form.password)) strength++;
    if (/[0-9]/.test(form.password)) strength++;
    if (/[^A-Za-z0-9]/.test(form.password)) strength++;
    
    return strength;
});

const passwordStrengthClass = computed(() => {
    const strength = passwordStrength.value;
    if (!strength) return '';
    if (strength <= 1) return 'bg-red-500';
    if (strength <= 2) return 'bg-yellow-500';
    if (strength <= 3) return 'bg-blue-500';
    return 'bg-emerald-500';
});

const passwordStrengthText = computed(() => {
    const strength = passwordStrength.value;
    if (!strength) return '';
    if (strength <= 1) return 'Faible';
    if (strength <= 2) return 'Moyen';
    if (strength <= 3) return 'Bon';
    return 'Fort';
});

// Validation de l'email
const isEmailValid = computed(() => {
    if (!form.email) return null;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(form.email);
});

const cancel = () => {
    form.reset();
    form.clearErrors();
    emit("close");
};
</script>

<template>
    <Dialog
        v-model:visible="props.show"
        modal
        :header="'Ajouter un utilisateur'"
        :style="{ width: '550px' }"
        class="p-fluid"
        :closable="true"
        @hide="cancel"
    >
        <!-- En-tête avec illustration -->
        <div class="flex items-center gap-4 mb-6 p-4 bg-gradient-to-r from-emerald-50 to-blue-50 dark:from-emerald-900/20 dark:to-blue-900/20 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                <i class="pi pi-user-plus"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Nouvel utilisateur
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Remplissez les informations pour créer un compte
                </p>
            </div>
        </div>

        <form @submit.prevent="create">
            <div class="flex flex-col gap-5">
                <!-- Ligne Nom et Prénom -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label for="name" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="pi pi-user mr-2 text-emerald-500"></i>
                            Nom <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            id="name"
                            v-model="form.name"
                            class="w-full"
                            autocomplete="off"
                            placeholder="Nom"
                            :class="{ 'p-invalid': form.errors.name }"
                        />
                        <small v-if="form.errors.name" class="text-red-500 text-xs">
                            {{ form.errors.name }}
                        </small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="prenom" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="pi pi-user mr-2 text-emerald-500"></i>
                            Prénom <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            id="prenom"
                            v-model="form.prenom"
                            class="w-full"
                            autocomplete="off"
                            placeholder="Prénom"
                            :class="{ 'p-invalid': form.errors.prenom }"
                        />
                        <small v-if="form.errors.prenom" class="text-red-500 text-xs">
                            {{ form.errors.prenom }}
                        </small>
                    </div>
                </div>

                <!-- Email avec validation -->
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <i class="pi pi-envelope mr-2 text-emerald-500"></i>
                        Email <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <InputText
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="w-full"
                            autocomplete="off"
                            placeholder="email@exemple.com"
                            :class="{ 
                                'p-invalid': form.errors.email,
                                'border-emerald-500': isEmailValid === true
                            }"
                        />
                        <i 
                            v-if="isEmailValid === true"
                            class="pi pi-check-circle absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500"
                        ></i>
                    </div>
                    <small v-if="form.errors.email" class="text-red-500 text-xs">
                        {{ form.errors.email }}
                    </small>
                    <small v-else-if="form.email && !isEmailValid" class="text-yellow-500 text-xs">
                        Format d'email invalide
                    </small>
                </div>

                <!-- Section Mot de passe -->
                <div class="relative my-2">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-white dark:bg-gray-800 text-gray-500 flex items-center gap-2">
                            <i class="pi pi-lock text-emerald-500"></i>
                            Sécurité
                        </span>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <i class="pi pi-lock mr-2 text-emerald-500"></i>
                        Mot de passe <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <InputText
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full pr-10"
                            placeholder="Mot de passe"
                            :class="{ 'p-invalid': form.errors.password }"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors"
                            @click="showPassword = !showPassword"
                        >
                            <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                        </button>
                    </div>
                    
                    <!-- Indicateur de force -->
                    <div v-if="form.password" class="mt-1">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div 
                                    class="h-full transition-all duration-300"
                                    :class="passwordStrengthClass"
                                    :style="{ width: (passwordStrength * 25) + '%' }"
                                ></div>
                            </div>
                            <span class="text-xs font-medium" :class="passwordStrengthClass.replace('bg-', 'text-')">
                                {{ passwordStrengthText }}
                            </span>
                        </div>
                        <ul class="mt-2 text-xs text-gray-500 space-y-1">
                            <li :class="{ 'text-emerald-500': form.password.length >= 8 }">
                                <i :class="form.password.length >= 8 ? 'pi pi-check-circle' : 'pi pi-circle'" class="mr-1"></i>
                                Au moins 8 caractères
                            </li>
                            <li :class="{ 'text-emerald-500': /[A-Z]/.test(form.password) }">
                                <i :class="/[A-Z]/.test(form.password) ? 'pi pi-check-circle' : 'pi pi-circle'" class="mr-1"></i>
                                Une majuscule
                            </li>
                            <li :class="{ 'text-emerald-500': /[0-9]/.test(form.password) }">
                                <i :class="/[0-9]/.test(form.password) ? 'pi pi-check-circle' : 'pi pi-circle'" class="mr-1"></i>
                                Un chiffre
                            </li>
                        </ul>
                    </div>
                    
                    <small v-if="form.errors.password" class="text-red-500 text-xs">
                        {{ form.errors.password }}
                    </small>
                </div>

                <!-- Confirmation mot de passe -->
                <div class="flex flex-col gap-2">
                    <label for="password_confirmation" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <i class="pi pi-lock mr-2 text-emerald-500"></i>
                        Confirmation <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <InputText
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="showPasswordConfirmation ? 'text' : 'password'"
                            class="w-full pr-10"
                            placeholder="Confirmer le mot de passe"
                            :class="{ 'p-invalid': form.errors.password_confirmation }"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors"
                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                        >
                            <i :class="showPasswordConfirmation ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                        </button>
                    </div>
                    
                    <!-- Indicateur de correspondance -->
                    <div v-if="form.password && form.password_confirmation" class="mt-1">
                        <div class="flex items-center gap-2">
                            <i 
                                class="pi text-xs"
                                :class="form.password === form.password_confirmation ? 'pi-check-circle text-emerald-500' : 'pi-times-circle text-red-500'"
                            ></i>
                            <span class="text-xs" :class="form.password === form.password_confirmation ? 'text-emerald-500' : 'text-red-500'">
                                {{ form.password === form.password_confirmation ? 'Mots de passe identiques' : 'Les mots de passe ne correspondent pas' }}
                            </span>
                        </div>
                    </div>
                    
                    <small v-if="form.errors.password_confirmation" class="text-red-500 text-xs">
                        {{ form.errors.password_confirmation }}
                    </small>
                </div>

                <!-- Rôle -->
                <div class="flex flex-col gap-2">
                    <label for="role" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        <i class="pi pi-shield mr-2 text-emerald-500"></i>
                        Rôle <span class="text-red-500">*</span>
                    </label>
                    <Select
                        v-model="form.role"
                        :options="props.roles"
                        optionValue="name"
                        optionLabel="name"
                        placeholder="Sélectionner un rôle"
                        class="w-full"
                        :class="{ 'p-invalid': form.errors.role }"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-2">
                                <i 
                                    class="pi"
                                    :class="{
                                        'pi-star text-purple-500': slotProps.value === 'superadmin',
                                        'pi-shield text-emerald-500': slotProps.value === 'admin',
                                        'pi-user text-blue-500': slotProps.value === 'operator'
                                    }"
                                ></i>
                                <span>{{ slotProps.value }}</span>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <i 
                                    class="pi"
                                    :class="{
                                        'pi-star text-purple-500': slotProps.option.name === 'superadmin',
                                        'pi-shield text-emerald-500': slotProps.option.name === 'admin',
                                        'pi-user text-blue-500': slotProps.option.name === 'operator'
                                    }"
                                ></i>
                                <span>{{ slotProps.option.name }}</span>
                            </div>
                        </template>
                    </Select>
                    <small v-if="form.errors.role" class="text-red-500 text-xs">
                        {{ form.errors.role }}
                    </small>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        type="button"
                        label="Annuler"
                        icon="pi pi-times"
                        class="p-button-outlined p-button-secondary"
                        @click="cancel"
                        :disabled="form.processing"
                    />
                    <Button
                        type="submit"
                        label="Créer"
                        icon="pi pi-check"
                        class="bg-emerald-500 hover:bg-emerald-600 border-none text-white"
                        :loading="form.processing"
                    />
                </div>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
:deep(.p-dialog .p-dialog-header) {
    @apply pb-2;
}

:deep(.p-dialog .p-dialog-content) {
    @apply pt-2;
}

:deep(.p-inputtext) {
    @apply border-gray-200 dark:border-gray-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all;
}

:deep(.p-select) {
    @apply border-gray-200 dark:border-gray-700;
}

:deep(.p-select:not(.p-disabled):hover) {
    @apply border-emerald-500;
}

:deep(.p-select.p-focus) {
    @apply border-emerald-500 ring-2 ring-emerald-200;
}

/* Animation pour l'indicateur de force */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mt-1 {
    animation: fadeIn 0.3s ease-out;
}
</style>