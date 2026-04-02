<script setup>
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import AppMenuItem from "./AppMenuItem.vue";
import { useLayout } from "@/sakai/layout/composables/layout";

const page = usePage();
const userData = computed(() => page.props.auth.user);
const { layoutState } = useLayout();

// État pour la recherche dans le menu
const searchQuery = ref("");
const isSearchVisible = ref(false);

const model = computed(() => {
    const user = userData.value;
    const isAdmin =
        user?.is_superadmin === true || user?.roles?.includes("admin");

    if (isAdmin) {
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
                badge: user?.pending_requests || null,
                items: [
                    {
                        label: "Utilisateurs",
                        icon: "pi-users",
                        to: "/user",
                        badge: user?.new_users || null,
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
                        label: "Messagerie",
                        icon: "pi-envelope",
                        to: "/cour-messagerie",
                        badge:
                            user?.unread_messages_count > 0
                                ? user.unread_messages_count
                                : null,
                        badgeClass: "danger",
                        description: "Messages internes",
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
    } else {
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
                label: "MESSAGERIE",
                icon: "pi-envelope",
                badge:
                    user?.unread_messages_count > 0
                        ? user.unread_messages_count
                        : null,
                badgeClass: "danger",
                items: [
                    {
                        label: "Boîte de réception",
                        icon: "pi-inbox",
                        to: "/candidat-messagerie",
                        badge:
                            user?.unread_messages_count > 0
                                ? user.unread_messages_count
                                : null,
                        badgeClass: "danger",
                        description: "Vos messages",
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
                label: "CANDIDATURES",
                icon: "pi-file",
                items: [
                    {
                        label: "Postuler",
                        icon: "pi-send",
                        to: "/candidat-postuler",
                        badge: user?.available_concours || null,
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
    }
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
        <!-- Profil utilisateur compact - réduit -->
        <div class="user-profile-compact">
            <div class="user-avatar-small">
                {{ userData?.name?.charAt(0) || "U" }}
            </div>
            <div class="user-info-compact">
                <div class="user-name-small">
                    {{ userData?.name || "Utilisateur" }}
                </div>
                <div class="user-email-small">
                    {{ userData?.email?.split("@")[0] || "" }}
                </div>
            </div>
        </div>

        <!-- Barre de recherche compacte - réduite -->
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

        <!-- Menu principal - compacté -->
        <ul class="layout-menu-compact">
            <template
                v-for="(section, sectionIndex) in filteredModel"
                :key="section.label"
            >
                <!-- Section header compact - réduit -->
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

                <!-- Séparateur réduit -->
                <li
                    v-if="sectionIndex < filteredModel.length - 1"
                    class="menu-separator-thin"
                ></li>
            </template>

            <!-- Message si aucun résultat -->
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

/* Profil utilisateur compact - réduit */
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

/* Recherche compacte - réduite */
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

/* Menu compact - réduit */
.layout-menu-compact {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
    overflow-y: auto;

    /* Scrollbar fine */
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
