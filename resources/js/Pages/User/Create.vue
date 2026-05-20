<script setup>
import { useForm } from "@inertiajs/vue3";
import { watchEffect, ref, computed } from "vue";

const props = defineProps({
    show: Boolean,
    title: String,
    roles: Object,
});

const emit = defineEmits(["close"]);

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: "",
    prenom: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
    portee: "restreint", // ⭐
});

// ⭐ Options pour la portée
const porteeOptions = ref([
    { label: "Général (Accès total)", value: "général" },
    { label: "Restreint (Sans User/Rôle)", value: "restreint" },
]);

// ⭐ Afficher le select portee SEULEMENT si superadmin
const showPortee = computed(() => form.role === "superadmin");
// ⭐ Fonction pour les icônes de rôle
const getRoleIconClass = (name) => {
    return {
        "pi-star text-purple-500": name === "superadmin",
        "pi-shield theme-text-primary": name === "admin",
        "pi-user text-blue-500": name === "operator",
    };
};

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
    if (!strength) return "";
    if (strength <= 1) return "bg-red-500";
    if (strength <= 2) return "bg-yellow-500";
    if (strength <= 3) return "bg-blue-500";
    return "bg-emerald-500";
});

const passwordStrengthText = computed(() => {
    const strength = passwordStrength.value;
    if (!strength) return "";
    if (strength <= 1) return "Faible";
    if (strength <= 2) return "Moyen";
    if (strength <= 3) return "Bon";
    return "Fort";
});

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
        <!-- ⭐ En-tête du dialogue - Dynamique -->
        <div
            class="flex items-center gap-4 mb-6 p-4 dialog-header-create rounded-lg border"
        >
            <div class="dialog-avatar-create">
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
                <!-- Nom et Prénom -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label
                            for="name"
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            <i class="pi pi-user mr-2 theme-text-primary"></i
                            >Nom
                            <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            id="name"
                            v-model="form.name"
                            class="w-full"
                            autocomplete="off"
                            placeholder="Nom"
                            :class="{ 'p-invalid': form.errors.name }"
                        />
                        <small
                            v-if="form.errors.name"
                            class="text-red-500 text-xs"
                            >{{ form.errors.name }}</small
                        >
                    </div>
                    <div class="flex flex-col gap-2">
                        <label
                            for="prenom"
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            <i class="pi pi-user mr-2 theme-text-primary"></i
                            >Prénom
                            <span class="text-red-500">*</span>
                        </label>
                        <InputText
                            id="prenom"
                            v-model="form.prenom"
                            class="w-full"
                            autocomplete="off"
                            placeholder="Prénom"
                            :class="{ 'p-invalid': form.errors.prenom }"
                        />
                        <small
                            v-if="form.errors.prenom"
                            class="text-red-500 text-xs"
                            >{{ form.errors.prenom }}</small
                        >
                    </div>
                </div>

                <!-- Email -->
                <div class="flex flex-col gap-2">
                    <label
                        for="email"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-envelope mr-2 theme-text-primary"></i
                        >Email
                        <span class="text-red-500">*</span>
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
                                'border-emerald-500': isEmailValid === true,
                            }"
                        />
                        <i
                            v-if="isEmailValid === true"
                            class="pi pi-check-circle absolute right-3 top-1/2 -translate-y-1/2 theme-text-primary"
                        ></i>
                    </div>
                    <small
                        v-if="form.errors.email"
                        class="text-red-500 text-xs"
                        >{{ form.errors.email }}</small
                    >
                </div>

                <!-- Séparateur Sécurité -->
                <div class="relative my-2">
                    <div class="absolute inset-0 flex items-center">
                        <div
                            class="w-full border-t border-gray-200 dark:border-gray-700"
                        ></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span
                            class="px-3 bg-white dark:bg-gray-800 text-gray-500 flex items-center gap-2"
                        >
                            <i class="pi pi-lock theme-text-primary"></i
                            >Sécurité
                        </span>
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="flex flex-col gap-2">
                    <label
                        for="password"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-lock mr-2 theme-text-primary"></i>Mot de
                        passe
                        <span class="text-red-500">*</span>
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
                            class="toggle-password-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition-colors"
                            @click="showPassword = !showPassword"
                        >
                            <i
                                :class="
                                    showPassword
                                        ? 'pi pi-eye-slash'
                                        : 'pi pi-eye'
                                "
                            ></i>
                        </button>
                    </div>
                    <div v-if="form.password" class="mt-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden"
                            >
                                <div
                                    class="h-full transition-all duration-300"
                                    :class="passwordStrengthClass"
                                    :style="{
                                        width: passwordStrength * 25 + '%',
                                    }"
                                ></div>
                            </div>
                            <span
                                class="text-xs font-medium"
                                :class="
                                    passwordStrengthClass.replace(
                                        'bg-',
                                        'text-',
                                    )
                                "
                            >
                                {{ passwordStrengthText }}
                            </span>
                        </div>
                    </div>
                    <small
                        v-if="form.errors.password"
                        class="text-red-500 text-xs"
                        >{{ form.errors.password }}</small
                    >
                </div>

                <!-- Confirmation -->
                <div class="flex flex-col gap-2">
                    <label
                        for="password_confirmation"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-lock mr-2 theme-text-primary"></i
                        >Confirmation
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <InputText
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="
                                showPasswordConfirmation ? 'text' : 'password'
                            "
                            class="w-full pr-10"
                            placeholder="Confirmer le mot de passe"
                            :class="{
                                'p-invalid': form.errors.password_confirmation,
                            }"
                        />
                        <button
                            type="button"
                            class="toggle-password-btn absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition-colors"
                            @click="
                                showPasswordConfirmation =
                                    !showPasswordConfirmation
                            "
                        >
                            <i
                                :class="
                                    showPasswordConfirmation
                                        ? 'pi pi-eye-slash'
                                        : 'pi pi-eye'
                                "
                            ></i>
                        </button>
                    </div>
                    <small
                        v-if="form.errors.password_confirmation"
                        class="text-red-500 text-xs"
                    >
                        {{ form.errors.password_confirmation }}
                    </small>
                </div>

                <!-- ⭐ Rôle -->
                <div class="flex flex-col gap-2">
                    <label
                        for="role"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-shield mr-2 theme-text-primary"></i>Rôle
                        <span class="text-red-500">*</span>
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
                            <div
                                v-if="slotProps.value"
                                class="flex items-center gap-2"
                            >
                                <i
                                    class="pi"
                                    :class="getRoleIconClass(slotProps.value)"
                                ></i>
                                <span>{{ slotProps.value }}</span>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <i
                                    class="pi"
                                    :class="
                                        getRoleIconClass(slotProps.option.name)
                                    "
                                ></i>
                                <span>{{ slotProps.option.name }}</span>
                            </div>
                        </template>
                    </Select>
                    <small
                        v-if="form.errors.role"
                        class="text-red-500 text-xs"
                        >{{ form.errors.role }}</small
                    >
                </div>

                <!-- ⭐ Portée - Visible SEULEMENT si superadmin -->
                <div v-if="showPortee" class="flex flex-col gap-2">
                    <label
                        for="portee"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-flag mr-2 text-purple-500"></i>Type de
                        Superadmin
                        <span class="text-red-500">*</span>
                    </label>
                    <Select
                        v-model="form.portee"
                        :options="porteeOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Sélectionner la portée"
                        class="w-full"
                    />
                    <small class="text-gray-400 text-xs">
                        <i class="pi pi-info-circle mr-1"></i>
                        Général : accès à tout. Restreint : sans User/Rôle.
                    </small>
                </div>

                <!-- ⭐ Boutons -->
                <div
                    class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
                >
                    <Button
                        type="button"
                        label="Annuler"
                        icon="pi pi-times"
                        class="btn-outlined-primary"
                        @click="cancel"
                        :disabled="form.processing"
                    />
                    <Button
                        type="submit"
                        label="Créer"
                        icon="pi pi-check"
                        class="btn-primary"
                        :loading="form.processing"
                    />
                </div>
            </div>
        </form>
    </Dialog>
</template>

<style scoped>
/* ⭐ Icône thème */
.theme-text-primary {
    color: var(--color-primary) !important;
}

/* ⭐ En-tête du dialogue */
.dialog-header-create {
    background: linear-gradient(to right, var(--color-primary-light), #eff6ff);
    border-color: var(--color-primary-light);
}

.dark .dialog-header-create {
    background: linear-gradient(
        to right,
        rgba(16, 185, 129, 0.1),
        rgba(59, 130, 246, 0.1)
    );
    border-color: rgba(16, 185, 129, 0.3);
}

/* ⭐ Avatar */
.dialog-avatar-create {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(
        135deg,
        var(--color-primary),
        var(--color-primary-dark)
    );
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* ⭐ Boutons */
.btn-primary {
    background: var(--color-primary) !important;
    border: none !important;
    color: white !important;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: var(--color-primary-dark) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary:disabled {
    background: #9ca3af !important;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-outlined-primary {
    color: var(--color-primary) !important;
    border-color: var(--color-primary) !important;
    background: transparent !important;
    transition: all 0.2s ease;
}

.btn-outlined-primary:hover {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Toggle password */
.toggle-password-btn:hover {
    color: var(--color-primary) !important;
}

/* ⭐ Focus */
:deep(.p-inputtext:focus),
:deep(.p-select:focus) {
    border-color: var(--color-primary) !important;
    box-shadow: 0 0 0 2px var(--color-primary-light) !important;
}

/* ⭐ Select highlight */
:deep(.p-select-panel .p-select-item.p-highlight) {
    background: var(--color-primary-light) !important;
    color: var(--color-primary-dark) !important;
}

/* ⭐ Email valid icon */
.text-emerald-500 {
    color: var(--color-primary) !important;
}

.border-emerald-500 {
    border-color: var(--color-primary) !important;
}
</style>
