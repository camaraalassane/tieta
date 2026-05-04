<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Card from "primevue/card";
import Button from "primevue/button";

defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};
</script>

<template>
    <Head title="Vérification d'email" />

    <div
        class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 dark:from-gray-900 dark:via-gray-900 dark:to-emerald-950/20 flex items-center justify-center p-4"
    >
        <Card class="shadow-2xl border-none w-full max-w-md">
            <template #content>
                <div class="text-center p-4">
                    <!-- Icône -->
                    <div
                        class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 border-circle flex align-items-center justify-content-center mx-auto mb-6"
                    >
                        <i class="pi pi-envelope text-4xl text-emerald-500"></i>
                    </div>

                    <h1
                        class="text-2xl font-bold text-gray-900 dark:text-white mb-3"
                    >
                        Vérifiez votre email
                    </h1>

                    <p
                        class="text-gray-600 dark:text-gray-400 text-sm mb-6 leading-relaxed"
                    >
                        Merci de vous être inscrit ! Avant de commencer,
                        veuillez vérifier votre adresse email en cliquant sur le
                        lien que nous venons de vous envoyer. Si vous n'avez pas
                        reçu l'email, nous vous en enverrons un autre.
                    </p>

                    <!-- Message de succès -->
                    <div
                        v-if="status === 'verification-link-sent'"
                        class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border-round-lg border border-green-200 dark:border-green-800"
                    >
                        <p
                            class="text-green-700 dark:text-green-300 text-sm m-0 flex align-items-center justify-content-center gap-2"
                        >
                            <i class="pi pi-check-circle"></i>
                            Un nouveau lien de vérification a été envoyé !
                        </p>
                    </div>

                    <div class="flex flex-column gap-3">
                        <!-- Bouton renvoyer -->
                        <form @submit.prevent="submit">
                            <Button
                                type="submit"
                                label="Renvoyer le lien de vérification"
                                icon="pi pi-refresh"
                                :loading="form.processing"
                                :disabled="form.processing"
                                class="w-full bg-emerald-500 hover:bg-emerald-600 border-none py-3"
                            />
                        </form>

                        <!-- Séparateur -->
                        <div class="relative my-2">
                            <div class="absolute inset-0 flex items-center">
                                <div
                                    class="w-full border-t border-gray-200 dark:border-gray-700"
                                ></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span
                                    class="px-2 bg-white dark:bg-gray-800 text-gray-500"
                                    >ou</span
                                >
                            </div>
                        </div>

                        <!-- Déconnexion -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-sm text-gray-500 hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400 transition-colors"
                        >
                            <i class="pi pi-sign-out mr-2"></i>
                            Se déconnecter
                        </Link>
                    </div>
                </div>
            </template>
        </Card>
    </div>
</template>
