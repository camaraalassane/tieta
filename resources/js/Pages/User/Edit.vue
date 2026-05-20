<script setup>
import { useForm } from "@inertiajs/vue3";
import { watchEffect, ref, computed } from "vue";

const props = defineProps({
    show: Boolean,
    title: String,
    user: Object,
    roles: Object,
});

// ⭐ Fonction pour les icônes de rôle
const getRoleIconClass = (code) => {
    return {
        "pi-star text-purple-500": code === "superadmin",
        "pi-shield theme-text-primary": code === "admin",
        "pi-user text-blue-500": code === "operator",
    };
};
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

const update = () => {
    form.put(route("user.update", props.user?.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            form.reset();
        },
        onError: () => null,
        onFinish: () => null,
    });
};

watchEffect(() => {
    if (props.show && props.user) {
        form.clearErrors();
        form.name = props.user.name || "";
        form.prenom = props.user.prenom || "";
        form.email = props.user.email || "";
        form.role = props.user?.roles?.[0]?.name || "";
        form.portee = props.user?.portee || "restreint"; // ⭐
        form.password = "";
        form.password_confirmation = "";
    }
});

const fullName = computed(
    () =>
        `${form.prenom || ""} ${form.name || ""}`.trim() ||
        "Nouvel utilisateur",
);

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
        :header="`Modifier ${fullName}`"
        :style="{ width: '500px' }"
        class="p-fluid"
        :closable="true"
        @hide="cancel"
    >
        <!-- ⭐ En-tête du dialogue - Dynamique -->
        <div
            class="flex items-center gap-4 mb-6 p-4 dialog-header-info rounded-lg"
        >
            <div class="dialog-avatar">
                {{
                    (
                        form.prenom?.charAt(0) ||
                        form.name?.charAt(0) ||
                        "U"
                    ).toUpperCase()
                }}
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ fullName }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    ID: {{ props.user?.id }} • Modifier les informations
                </p>
            </div>
        </div>

        <form @submit.prevent="update">
            <div class="flex flex-col gap-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nom -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="name"
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            <i class="pi pi-user mr-2 theme-text-primary"></i
                            >Nom
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
                    <!-- Prénom -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="prenom"
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            <i class="pi pi-user mr-2 theme-text-primary"></i
                            >Prénom
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
                    </label>
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full"
                        autocomplete="off"
                        placeholder="email@exemple.com"
                        :class="{ 'p-invalid': form.errors.email }"
                    />
                    <small
                        v-if="form.errors.email"
                        class="text-red-500 text-xs"
                        >{{ form.errors.email }}</small
                    >
                </div>

                <!-- Séparateur -->
                <div class="relative my-2">
                    <div class="absolute inset-0 flex items-center">
                        <div
                            class="w-full border-t border-gray-200 dark:border-gray-700"
                        ></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span
                            class="px-3 bg-white dark:bg-gray-800 text-gray-500"
                            >Changer le mot de passe</span
                        >
                    </div>
                </div>

                <!-- Mot de passe -->
                <div class="flex flex-col gap-2">
                    <label
                        for="password"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-lock mr-2 theme-text-primary"></i
                        >Nouveau mot de passe
                    </label>
                    <div class="relative">
                        <InputText
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full pr-10"
                            placeholder="Laisser vide pour ne pas changer"
                            :class="{ 'p-invalid': form.errors.password }"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors"
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

                <!-- Confirmation mot de passe -->
                <div class="flex flex-col gap-2">
                    <label
                        for="password_confirmation"
                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        <i class="pi pi-lock mr-2 theme-text-primary"></i
                        >Confirmer le mot de passe
                    </label>
                    <div class="relative">
                        <InputText
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :type="
                                showPasswordConfirmation ? 'text' : 'password'
                            "
                            class="w-full pr-10"
                            placeholder="Confirmer le nouveau mot de passe"
                            :class="{
                                'p-invalid': form.errors.password_confirmation,
                            }"
                        />
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-emerald-500 transition-colors"
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
                    </label>
                    <Select
                        v-model="form.role"
                        :options="props.roles"
                        optionValue="code"
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
                                <span>{{
                                    props.roles?.find(
                                        (r) => r.code === slotProps.value,
                                    )?.name
                                }}</span>
                            </div>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <i
                                    class="pi"
                                    :class="
                                        getRoleIconClass(slotProps.option.code)
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
                        Superadmin <span class="text-red-500">*</span>
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
                        <i class="pi pi-info-circle mr-1"></i>Général : accès à
                        tout. Restreint : sans User/Rôle.
                    </small>
                </div>

                <!-- ⭐ Actions -->
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
                        label="Mettre à jour"
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
.dialog-header-info {
    background: var(--color-primary-light);
}

.dark .dialog-header-info {
    background: rgba(16, 185, 129, 0.1);
}

/* ⭐ Avatar */
.dialog-avatar {
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
    font-weight: bold;
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

/* ⭐ Toggle password hover */
.toggle-password-btn:hover {
    color: var(--color-primary) !important;
}
</style>
