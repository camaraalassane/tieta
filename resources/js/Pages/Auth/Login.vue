<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Connexion - CɛTA" />

    <div
        class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-950/20 flex items-center justify-center p-4"
    >
        <!-- Container principal avec animation -->
        <div
            class="w-full max-w-6xl flex flex-col lg:flex-row items-stretch gap-8 animate-fadeIn"
        >
            <!-- Section gauche - Image et branding (visible sur desktop) -->
            <div
                class="hidden lg:flex lg:w-1/2 relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-8 text-white shadow-2xl"
            >
                <div
                    class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -translate-y-32 translate-x-32"
                ></div>
                <div
                    class="absolute bottom-0 left-0 w-96 h-96 bg-emerald-400/20 rounded-full translate-y-48 -translate-x-48"
                ></div>

                <div class="relative z-10 flex flex-col h-full">
                    <!-- Logo et branding -->
                    <div class="flex items-center gap-3 mb-12">
                        <div
                            class="bg-white/20 backdrop-blur-sm p-3 rounded-2xl"
                        >
                            <i class="pi pi-check-circle text-3xl"></i>
                        </div>
                        <span class="text-2xl font-black tracking-tighter"
                            >Recrutement DTTIA</span
                        >
                    </div>

                    <!-- Image agrandie avec cadre élégant -->
                    <div
                        class="flex-1 flex flex-col items-center justify-center"
                    >
                        <div class="relative group">
                            <!-- Effet de halo -->
                            <div
                                class="absolute inset-0 bg-emerald-300 rounded-full blur-3xl opacity-30 group-hover:opacity-50 transition-opacity"
                            ></div>

                            <!-- Cadre décoratif -->
                            <div
                                class="relative p-1 rounded-3xl bg-gradient-to-br from-emerald-300 to-emerald-600 shadow-2xl"
                            >
                                <div
                                    class="bg-white dark:bg-gray-800 rounded-2xl p-2"
                                >
                                    <img
                                        src="/Images/DTTIA.jpeg"
                                        alt="DTTIA"
                                        class="w-64 h-64 object-cover rounded-2xl shadow-2xl transform group-hover:scale-105 transition-transform duration-500"
                                    />
                                </div>
                            </div>

                            <!-- Badge flottant -->
                            <div
                                class="absolute -bottom-4 -right-4 bg-white dark:bg-gray-800 text-emerald-600 px-6 py-3 rounded-2xl shadow-xl font-bold text-lg"
                            >
                                Se Connecter
                            </div>
                        </div>

                        <!-- Texte descriptif -->
                        <div class="text-center mt-12">
                            <h2 class="text-3xl font-bold mb-4">
                                Content de vous revoir
                            </h2>
                            <p class="text-emerald-100 text-lg max-w-md">
                                Accédez à votre espace personnel pour suivre vos
                                candidatures
                            </p>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="grid grid-cols-2 gap-4 mt-auto pt-12">
                        <div
                            class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center"
                        >
                            <div class="text-3xl font-bold">500+</div>
                            <div class="text-sm text-emerald-100">
                                Candidats
                            </div>
                        </div>
                        <div
                            class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center"
                        >
                            <div class="text-3xl font-bold">15+</div>
                            <div class="text-sm text-emerald-100">Concours</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section droite - Formulaire de connexion -->
            <div class="w-full lg:w-1/2 flex items-center justify-center">
                <div class="w-full max-w-md animate-slideUp">
                    <!-- Version mobile du logo et image (visible seulement sur mobile) -->
                    <div class="lg:hidden text-center mb-8">
                        <div class="flex justify-center mb-4">
                            <div class="relative inline-block">
                                <div
                                    class="absolute inset-0 bg-emerald-500 rounded-full blur-xl opacity-30"
                                ></div>
                                <img
                                    src="/Images/DTTIA.jpeg"
                                    alt="DTTIA"
                                    class="h-24 w-auto relative rounded-2xl shadow-xl border-4 border-white dark:border-gray-800"
                                />
                            </div>
                        </div>
                        <h1
                            class="text-2xl font-bold text-gray-900 dark:text-white mb-2"
                        >
                            Bienvenue !
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400">
                            Connectez-vous à votre compte
                        </p>
                    </div>

                    <!-- Carte du formulaire -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl p-8 md:p-10 border border-gray-100 dark:border-gray-700"
                    >
                        <div class="text-center mb-8">
                            <h2
                                class="text-2xl font-bold text-gray-900 dark:text-white mb-2"
                            >
                                Se Connecter
                            </h2>
                            <p class="text-gray-500 dark:text-gray-400">
                                Accédez à votre espace personnel
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Email -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    <i
                                        class="pi pi-envelope mr-2 text-emerald-500"
                                    ></i
                                    >Email
                                </label>
                                <InputText
                                    v-model="form.email"
                                    type="email"
                                    placeholder="votre@email.com"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.email }"
                                />
                                <small
                                    v-if="form.errors.email"
                                    class="text-red-500 text-sm mt-1 block"
                                >
                                    {{ form.errors.email }}
                                </small>
                            </div>

                            <!-- Mot de passe -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                >
                                    <i
                                        class="pi pi-lock mr-2 text-emerald-500"
                                    ></i
                                    >Mot de passe
                                </label>
                                <div class="relative">
                                    <Password
                                        v-model="form.password"
                                        :toggleMask="true"
                                        placeholder="••••••••"
                                        class="w-full"
                                        :feedback="false"
                                        :inputClass="{
                                            'p-invalid': form.errors.password,
                                        }"
                                    />
                                </div>
                                <small
                                    v-if="form.errors.password"
                                    class="text-red-500 text-sm mt-1 block"
                                >
                                    {{ form.errors.password }}
                                </small>
                            </div>

                            <!-- Options -->
                            <div
                                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                            >
                                <div class="flex items-center">
                                    <Checkbox
                                        v-model="form.remember"
                                        :binary="true"
                                        inputId="remember"
                                    />
                                    <label
                                        for="remember"
                                        class="ml-2 text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Se souvenir de moi
                                    </label>
                                </div>
                                <Link
                                    :href="route('password.request')"
                                    class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium transition-colors"
                                >
                                    Mot de passe oublié ?
                                </Link>
                            </div>

                            <!-- Bouton de connexion -->
                            <Button
                                type="submit"
                                :loading="form.processing"
                                class="w-full bg-emerald-500 hover:bg-emerald-600 border-none py-3 text-base font-semibold"
                            >
                                <i class="pi pi-sign-in mr-2"></i>
                                Se connecter
                            </Button>

                            <!-- Séparateur -->
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div
                                        class="w-full border-t border-gray-200 dark:border-gray-700"
                                    ></div>
                                </div>
                                <div
                                    class="relative flex justify-center text-sm"
                                >
                                    <span
                                        class="px-2 bg-white dark:bg-gray-800 text-gray-500"
                                        >nouveau ?</span
                                    >
                                </div>
                            </div>

                            <!-- Lien vers inscription -->
                            <div class="text-center">
                                <p class="text-gray-600 dark:text-gray-400">
                                    Vous n'avez pas de compte ?
                                    <Link
                                        :href="route('register')"
                                        class="text-emerald-600 hover:text-emerald-700 font-medium ml-1"
                                    >
                                        Créer un compte
                                    </Link>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.6s ease-out;
}

.animate-slideUp {
    animation: slideUp 0.6s ease-out;
}

/* Styles PrimeVue personnalisés */
:deep(.p-inputtext) {
    @apply w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all;
}

:deep(.p-password-input) {
    @apply w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all;
}

:deep(.p-password) {
    @apply w-full;
}

:deep(.p-button) {
    @apply rounded-xl;
}

:deep(.p-checkbox-box) {
    @apply border-2 border-gray-300 dark:border-gray-600 rounded-md;
}

:deep(.p-checkbox-box.p-highlight) {
    @apply border-emerald-500 bg-emerald-500;
}

/* Style pour les icônes des yeux */
:deep(.p-password .p-password-icon) {
    @apply text-gray-400 hover:text-emerald-500 transition-colors;
    margin-right: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    :deep(.p-inputtext) {
        @apply px-3 py-2;
    }

    .bg-white {
        @apply px-4 py-6;
    }
}
</style>
