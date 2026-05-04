<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

// ⭐ Récupérer le service de l'utilisateur (pour gérant/admin)
const userService = computed(() => {
    const user = page.props.auth.user;
    if (!user) return null;

    // Pour les gérants et admins, récupérer le service
    if (user.roles?.includes("gerant") || user.roles?.includes("admin")) {
        return user.service || null;
    }
    return null;
});

// ⭐ Déterminer le nom principal (coloré en vert)
const appPrimaryName = computed(() => {
    const user = page.props.auth.user;
    if (!user) return "DTTIA";

    // Pour superadmin et operator : FAMa
    if (
        user.roles?.includes("superadmin") ||
        user.roles?.includes("operator")
    ) {
        return "FAMa";
    }

    // Pour gérant et admin : nom du service
    if (userService.value?.nom) {
        return userService.value.nom;
    }

    return "DTTIA";
});

// ⭐ Déterminer le sous-titre (Recrutement) - toujours en noir foncé
const appSecondaryName = computed(() => {
    return "Recrutement";
});

// ⭐ Déterminer le message de copyright
const copyrightText = computed(() => {
    const user = page.props.auth.user;
    if (!user) return "Plateforme de Gestion des Concours";

    // Pour superadmin et operator
    if (
        user.roles?.includes("superadmin") ||
        user.roles?.includes("operator")
    ) {
        return "Plateforme de Recrutement FAMa";
    }

    // Pour gérant et admin : nom du service
    if (userService.value?.nom) {
        return `${userService.value.nom} - Gestion des Concours`;
    }

    return "Plateforme de Gestion des Concours";
});
</script>

<template>
    <div class="layout-footer py-4 mt-8 border-top-1 border-200">
        <div
            class="flex flex-column md:flex-row align-items-center justify-content-between gap-3 w-full px-4"
        >
            <!-- Nom de l'application : nom coloré en vert, Recrutement en noir foncé -->
            <div class="flex align-items-center">
                <span class="text-2xl font-light tracking-wider">
                    <span class="text-emerald-600 font-bold">{{
                        appPrimaryName
                    }}</span>
                    <span class="text-gray-700 font-medium">{{
                        appSecondaryName
                    }}</span>
                </span>
            </div>

            <!-- Copyright et Slogan -->
            <div class="text-center md:text-right">
                <span class="text-surface-500 text-sm font-medium">
                    &copy; 2026 — {{ copyrightText }}
                    <span class="hidden md:inline text-emerald-600 ml-1"
                        >Tous droits réservés.</span
                    >
                </span>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.layout-footer {
    background: var(--surface-card);
    transition: margin-left 0.2s;
    display: flex;
    align-items: center;
    border-top: 1px solid var(--surface-border);
    position: relative;

    &::before {
        content: "";
        position: absolute;
        top: -1px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 2px;
        background: #10b981;
        border-radius: 2px;
    }
}

// Animation légère au survol
.text-emerald-600:hover {
    filter: brightness(1.1);
    transition: filter 0.3s;
}

@media (max-width: 768px) {
    .layout-footer {
        padding-bottom: 2rem;
    }
}
</style>
