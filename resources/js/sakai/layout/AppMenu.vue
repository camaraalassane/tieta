<script setup>
import { ref, computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppMenuItem from "./AppMenuItem.vue";
import { useLayout } from "@/sakai/layout/composables/layout";

const page = usePage();
const { layoutState } = useLayout();

// ⭐ Stocker le rôle initial de l'utilisateur au chargement
const userRoles = ref([]);
const userServiceId = ref(null);
const isLoading = ref(true);

// État pour la recherche dans le menu
const searchQuery = ref("");
const isSearchVisible = ref(false);

// ⭐ Capturer les données utilisateur une fois au démarrage
onMounted(() => {
    const user = page.props.auth.user;
    if (user) {
        userRoles.value = user.roles || [];
        userServiceId.value =
            user.service?.id ||
            user.service_id ||
            user.service_gerant?.id ||
            null;
    }
    isLoading.value = false;
});

// ⭐ Utiliser les valeurs stockées pour les vérifications de rôle
const isSuperAdmin = computed(() => userRoles.value.includes("superadmin"));
const isAdmin = computed(
    () =>
        userRoles.value.includes("admin") &&
        !userRoles.value.includes("superadmin"),
);
const isGerant = computed(() => userRoles.value.includes("gerant"));
const isOperator = computed(() => userRoles.value.includes("operator"));

// ⭐ Récupérer l'ID du service depuis les données stockées
const serviceId = computed(() => userServiceId.value);

// ⭐ Menu principal - utilise les rôles stockés
const model = computed(() => {
    // Attendre le chargement initial
    if (isLoading.value) return [];

    const currentUser = page.props.auth.user;

    // ⭐ Menu pour SUPERADMIN
    if (isSuperAdmin.value) {
        return [
            {
                label: "PILOTAGE",
                icon: "pi-chart-line",
                items: [
                    {
                        label: "Tableau de bord",
                        icon: "pi-th-large",
                        to: "/dashboard",
                        description: "Vue d'ensemble",
                    },
                ],
            },
            {
                label: "ADMINISTRATION",
                icon: "pi-cog",
                items: [
                    {
                        label: "Utilisateurs",
                        icon: "pi-users",
                        to: "/user",
                        description: "Gérer les comptes",
                    },
                    {
                        label: "Rôles & Permissions",
                        icon: "pi-shield",
                        to: "/role",
                        description: "Configurer les accès",
                    },
                ],
            },
            {
                label: "SERVICES",
                icon: "pi-building",
                items: [
                    {
                        label: "Gestion des services",
                        icon: "pi-building",
                        to: "/admin/services",
                        description: "Créer et gérer les services",
                    },
                ],
            },
            {
                label: "CONCOURS",
                icon: "pi-calendar",
                items: [
                    {
                        label: "Nouveau concours",
                        icon: "pi-plus-circle",
                        to: "/concours",
                        description: "Créer un concours",
                    },
                    {
                        label: "Affectations",
                        icon: "pi-user-plus",
                        to: "/concours-admins",
                        description: "Assigner les admins",
                    },
                    {
                        label: "Gestion des concours",
                        icon: "pi-search",
                        to: "/concours-consulter",
                        description: "Consulter et modifier",
                    },
                    {
                        label: "Communiqués",
                        icon: "pi-megaphone",
                        to: "/communiques",
                        description: "Gérer les communiqués officiels",
                    },
                ],
            },
            {
                label: "COMMUNICATION",
                icon: "pi-comments",
                items: [
                    {
                        label: "Messagerie",
                        icon: "pi-envelope",
                        to: "/cour-messagerie",
                        badge:
                            currentUser?.unread_messages_count > 0
                                ? currentUser.unread_messages_count
                                : null,
                        badgeClass: "danger",
                        description: "Messages internes",
                    },
                    {
                        label: "Diffusion de messages",
                        icon: "pi-send",
                        to: "/broadcast",
                        description: "Envoyer un message à tous les candidats",
                    },
                ],
            },
            {
                label: "DÉLIBÉRATIONS",
                icon: "pi-file-pdf",
                items: [
                    {
                        label: "Créer un résultat",
                        icon: "pi-cloud-upload",
                        to: "/concour-creerResultat",
                        description: "Publier les résultats",
                    },
                    {
                        label: "Gestion des résultats",
                        icon: "pi-copy",
                        to: "/concour-gererResultat",
                        description: "Historique",
                    },
                    {
                        label: "Historique candidatures",
                        icon: "pi-history",
                        to: "/concours-historique",
                        description: "Consulter l'historique complet",
                    },
                ],
            },
        ];
    }

    // ⭐ Menu pour GÉRANT
    if (isGerant.value) {
        const hasServiceId = serviceId.value !== null;
        const serviceShowUrl = hasServiceId
            ? `/services/${serviceId.value}`
            : "#";
        const personnelUrl = hasServiceId
            ? `/services/${serviceId.value}/personnel`
            : "#";

        return [
            {
                label: "PILOTAGE",
                icon: "pi-chart-line",
                items: [
                    {
                        label: "Tableau de bord",
                        icon: "pi-th-large",
                        to: "/dashboard",
                        description: "Vue d'ensemble",
                    },
                ],
            },
            {
                label: "MON SERVICE",
                icon: "pi-building",
                items: [
                    {
                        label: "Mon service",
                        icon: "pi-building",
                        to: serviceShowUrl,
                        description: "Consulter mon service",
                        disabled: !hasServiceId,
                    },
                    {
                        label: "Personnel",
                        icon: "pi-users",
                        to: personnelUrl,
                        description: "Gérer le personnel",
                        disabled: !hasServiceId,
                    },
                ],
            },
            {
                label: "CONCOURS",
                icon: "pi-calendar",
                items: [
                    {
                        label: "Nouveau concours",
                        icon: "pi-plus-circle",
                        to: "/concours",
                        description: "Créer un concours",
                    },
                    {
                        label: "Affectations",
                        icon: "pi-user-plus",
                        to: "/concours-admins",
                        description:
                            "Assigner les administrateurs aux concours",
                    },
                    {
                        label: "Gestion des concours",
                        icon: "pi-search",
                        to: "/concours-consulter",
                        description: "Consulter et modifier",
                    },
                    {
                        label: "Communiqués",
                        icon: "pi-megaphone",
                        to: "/communiques",
                        description: "Gérer les communiqués officiels",
                    },
                ],
            },
            {
                label: "COMMUNICATION",
                icon: "pi-comments",
                items: [
                    {
                        label: "Messagerie",
                        icon: "pi-envelope",
                        to: "/cour-messagerie",
                        badge:
                            currentUser?.unread_messages_count > 0
                                ? currentUser.unread_messages_count
                                : null,
                        badgeClass: "danger",
                        description: "Messages internes",
                    },
                    {
                        label: "Diffusion de messages",
                        icon: "pi-send",
                        to: "/broadcast",
                        description: "Envoyer un message à tous les candidats",
                    },
                ],
            },
            {
                label: "DÉLIBÉRATIONS",
                icon: "pi-file-pdf",
                items: [
                    {
                        label: "Créer un résultat",
                        icon: "pi-cloud-upload",
                        to: "/concour-creerResultat",
                        description: "Publier les résultats",
                    },
                    {
                        label: "Gestion des résultats",
                        icon: "pi-copy",
                        to: "/concour-gererResultat",
                        description: "Historique",
                    },
                    {
                        label: "Historique candidatures",
                        icon: "pi-history",
                        to: "/concours-historique",
                        description: "Consulter l'historique complet",
                    },
                ],
            },
        ];
    }

    // ⭐ Menu pour ADMIN
    if (isAdmin.value) {
        return [
            {
                label: "PILOTAGE",
                icon: "pi-chart-line",
                items: [
                    {
                        label: "Tableau de bord",
                        icon: "pi-th-large",
                        to: "/dashboard",
                        description: "Vue d'ensemble",
                    },
                ],
            },
            {
                label: "CONCOURS",
                icon: "pi-calendar",
                items: [
                    {
                        label: "Nouveau concours",
                        icon: "pi-plus-circle",
                        to: "/concours",
                        description: "Créer un concours",
                    },
                    {
                        label: "Affectations",
                        icon: "pi-user-plus",
                        to: "/concours-admins",
                        description: "Assigner les admins",
                    },
                    {
                        label: "Gestion des concours",
                        icon: "pi-search",
                        to: "/concours-consulter",
                        description: "Consulter et modifier",
                    },
                    {
                        label: "Communiqués",
                        icon: "pi-megaphone",
                        to: "/communiques",
                        description: "Gérer les communiqués officiels",
                    },
                ],
            },
            {
                label: "COMMUNICATION",
                icon: "pi-comments",
                items: [
                    {
                        label: "Messagerie",
                        icon: "pi-envelope",
                        to: "/cour-messagerie",
                        badge:
                            currentUser?.unread_messages_count > 0
                                ? currentUser.unread_messages_count
                                : null,
                        badgeClass: "danger",
                        description: "Messages internes",
                    },
                    {
                        label: "Diffusion de messages",
                        icon: "pi-send",
                        to: "/broadcast",
                        description: "Envoyer un message à tous les candidats",
                    },
                ],
            },
            {
                label: "DÉLIBÉRATIONS",
                icon: "pi-file-pdf",
                items: [
                    {
                        label: "Créer un résultat",
                        icon: "pi-cloud-upload",
                        to: "/concour-creerResultat",
                        description: "Publier les résultats",
                    },
                    {
                        label: "Gestion des résultats",
                        icon: "pi-copy",
                        to: "/concour-gererResultat",
                        description: "Historique",
                    },
                    {
                        label: "Historique candidatures",
                        icon: "pi-history",
                        to: "/concours-historique",
                        description: "Consulter l'historique complet",
                    },
                ],
            },
        ];
    }

    // ⭐ Menu pour OPERATOR (candidat)
    return [
        {
            label: "ACCUEIL",
            icon: "pi-home",
            items: [
                {
                    label: "Tableau de bord",
                    icon: "pi-home",
                    to: "/dashboard",
                    description: "Vue personnalisée",
                },
            ],
        },
        {
            label: "MON COMPTE",
            icon: "pi-user",
            items: [
                {
                    label: "Mon Profil",
                    icon: "pi-user-edit",
                    to: "/candidat-profil",
                    description: "Informations personnelles",
                },
            ],
        },
        {
            label: "MESSAGERIE",
            icon: "pi-envelope",
            badge:
                currentUser?.unread_messages_count > 0
                    ? currentUser.unread_messages_count
                    : null,
            badgeClass: "danger",
            items: [
                {
                    label: "Boîte de réception",
                    icon: "pi-inbox",
                    to: "/candidat-messagerie",
                    badge:
                        currentUser?.unread_messages_count > 0
                            ? currentUser.unread_messages_count
                            : null,
                    badgeClass: "danger",
                    description: "Vos messages",
                },
            ],
        },
        {
            label: "CANDIDATURES",
            icon: "pi-file",
            items: [
                {
                    label: "Postuler",
                    icon: "pi-send",
                    to: "/candidat-postuler",
                    badge: currentUser?.available_concours || null,
                    description: "Concours ouverts",
                },
                {
                    label: "Mes Candidatures",
                    icon: "pi-folder-open",
                    to: "/candidat-dossier",
                    description: "Suivi des dossiers",
                },
            ],
        },
        {
            label: "RÉSULTATS",
            icon: "pi-verified",
            items: [
                {
                    label: "Mes Résultats",
                    icon: "pi-verified",
                    to: "/candidat-resultat",
                    description: "Consulter vos résultats",
                },
            ],
        },
    ];
});

// Menu filtré par recherche
const filteredModel = computed(() => {
    if (!searchQuery.value) return model.value;

    const query = searchQuery.value.toLowerCase();

    return model.value
        .map((section) => ({
            ...section,
            items: section.items.filter(
                (item) =>
                    item.label.toLowerCase().includes(query) ||
                    (item.description &&
                        item.description.toLowerCase().includes(query)),
            ),
        }))
        .filter((section) => section.items.length > 0);
});
</script>

<template>
    <div class="layout-menu-container">
        <!-- Profil utilisateur compact -->
        <div class="user-profile-compact">
            <div class="user-avatar-small">
                {{ page.props.auth.user?.name?.charAt(0) || "U" }}
            </div>
            <div class="user-info-compact">
                <div class="user-name-small">
                    {{ page.props.auth.user?.name || "Utilisateur" }}
                </div>
                <div class="user-email-small">
                    {{ page.props.auth.user?.email?.split("@")[0] || "" }}
                </div>
            </div>
        </div>

        <!-- Barre de recherche compacte -->
        <div class="search-compact" :class="{ expanded: isSearchVisible }">
            <button
                class="search-toggle-small"
                @click="isSearchVisible = !isSearchVisible"
            >
                <i
                    class="pi"
                    :class="isSearchVisible ? 'pi-times' : 'pi-search'"
                ></i>
            </button>
            <input
                v-if="isSearchVisible"
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher..."
                class="search-input-small"
                autofocus
            />
        </div>

        <!-- Menu principal -->
        <ul class="layout-menu-compact">
            <template
                v-for="(section, sectionIndex) in filteredModel"
                :key="section.label"
            >
                <li class="menu-section-compact">
                    <div class="menu-section-header-compact">
                        <i
                            :class="['pi', section.icon, 'section-icon-small']"
                        ></i>
                        <span class="section-label-small">{{
                            section.label
                        }}</span>
                        <span
                            v-if="section.badge"
                            class="section-badge-small"
                            >{{ section.badge }}</span
                        >
                    </div>
                    <ul class="menu-section-items-compact">
                        <app-menu-item
                            v-for="(item, itemIndex) in section.items"
                            :key="item.label"
                            :item="item"
                            :index="itemIndex"
                            :section-index="sectionIndex"
                        />
                    </ul>
                </li>
                <li
                    v-if="sectionIndex < filteredModel.length - 1"
                    class="menu-separator-thin"
                ></li>
            </template>
            <li v-if="filteredModel.length === 0" class="no-results-compact">
                <i class="pi pi-search"></i>
                <p>Aucun résultat</p>
            </li>
        </ul>
    </div>
</template>

<style lang="scss" scoped>
.layout-menu-container {
    height: 100%;
    display: flex;
    flex-direction: column;
    background: var(--surface-card);
    border-right: 1px solid var(--surface-border);
    padding: 8px 6px;
}

.user-profile-compact {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 8px;
    margin: 0 2px 8px 2px;
    background: rgba(16, 185, 129, 0.05);
    border-radius: 8px;

    .user-avatar-small {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }

    .user-info-compact {
        flex: 1;
        min-width: 0;

        .user-name-small {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-color);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email-small {
            font-size: 10px;
            color: var(--text-color-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }
}

.search-compact {
    margin: 0 2px 8px 2px;
    position: relative;
    display: flex;
    align-items: center;
    background: var(--surface-ground);
    border-radius: 6px;

    &.expanded {
        background: var(--surface-card);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .search-toggle-small {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: none;
        color: var(--text-color-secondary);
        font-size: 12px;
        cursor: pointer;

        &:hover {
            color: #10b981;
        }
    }

    .search-input-small {
        flex: 1;
        height: 28px;
        padding-right: 8px;
        background: none;
        border: none;
        outline: none;
        color: var(--text-color);
        font-size: 12px;

        &::placeholder {
            color: var(--text-color-secondary);
            font-size: 11px;
        }
    }
}

.layout-menu-compact {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;

    &::-webkit-scrollbar {
        width: 2px;
    }

    &::-webkit-scrollbar-thumb {
        background: rgba(16, 185, 129, 0.3);
        border-radius: 2px;
    }
}

.menu-section-compact {
    margin-bottom: 4px;

    .menu-section-header-compact {
        display: flex;
        align-items: center;
        padding: 4px 8px;
        color: var(--text-color-secondary);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.3px;
        text-transform: uppercase;

        .section-icon-small {
            font-size: 10px;
            margin-right: 4px;
            color: #10b981;
        }

        .section-label-small {
            flex: 1;
        }

        .section-badge-small {
            padding: 1px 4px;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border-radius: 8px;
            font-size: 8px;
            font-weight: 600;
        }
    }
}

.menu-section-items-compact {
    list-style: none;
    padding: 0;
    margin: 0;
}

.menu-separator-thin {
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        var(--surface-border),
        transparent
    );
    margin: 6px 12px;
}

.no-results-compact {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 8px;
    color: var(--text-color-secondary);

    i {
        font-size: 20px;
        margin-bottom: 4px;
        opacity: 0.5;
    }

    p {
        font-size: 10px;
    }
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
