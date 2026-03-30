<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { useLayout } from "@/sakai/layout/composables/layout";

const props = defineProps({
    item: { type: Object, required: true },
    index: { type: Number, required: true },
    sectionIndex: { type: Number, default: 0 },
});

const { setActiveMenuItem } = useLayout(); // ⭐ On ne prend que setActiveMenuItem, pas onMenuToggle
const page = usePage();
const isExpanded = ref(false);

const isActive = computed(() => {
    if (!props.item.to) return false;
    const currentUrl = page.url.split("?")[0];
    return currentUrl === props.item.to;
});

const can = (permissions) => {
    const user = page.props.auth?.user;
    if (!user) return false;
    if (user.is_superadmin) return true;

    const userPermissions = user.permissions || [];
    const searchPermissions = Array.isArray(permissions)
        ? permissions
        : [permissions];
    return searchPermissions.some((p) => userPermissions.includes(p));
};

const handleClick = (event) => {
    if (props.item.disabled) {
        event.preventDefault();
        return;
    }

    // ⭐ Uniquement pour les items avec sous-menus
    if (props.item.items) {
        isExpanded.value = !isExpanded.value;
    }

    // ⭐ SUPPRIMÉ : onMenuToggle() - Ne ferme PAS le menu

    if (props.item.command) {
        props.item.command({ originalEvent: event, item: props.item });
    }

    // ⭐ Marque l'item comme actif
    setActiveMenuItem(`${props.sectionIndex}-${props.index}`);
};

const badgeClass = computed(() => {
    const classes = {
        success:
            "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400",
        warning:
            "bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400",
        danger: "bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 animate-pulse",
        info: "bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400",
    };
    return classes[props.item.badgeClass] || "bg-emerald-500 text-white";
});
</script>

<template>
    <li
        class="menu-item-compact"
        :class="{ active: isActive, expanded: isExpanded }"
    >
        <!-- Lien simple -->
        <Link
            v-if="item.to && !item.items"
            :href="item.to"
            class="menu-link-compact"
            @click="handleClick"
        >
            <i :class="['pi', item.icon, 'menu-icon-small']"></i>
            <span class="menu-label-small">{{ item.label }}</span>

            <span v-if="item.badge" :class="['menu-badge-small', badgeClass]">
                {{ item.badge }}
            </span>

            <span v-if="item.description" class="menu-tooltip-small">
                {{ item.description }}
            </span>
        </Link>

        <!-- Parent avec sous-menus -->
        <div
            v-else-if="item.items"
            class="menu-parent-compact"
            @click="handleClick"
        >
            <i :class="['pi', item.icon, 'menu-icon-small']"></i>
            <span class="menu-label-small">{{ item.label }}</span>

            <span v-if="item.badge" :class="['menu-badge-small', badgeClass]">
                {{ item.badge }}
            </span>

            <i
                class="pi pi-chevron-down menu-arrow-small"
                :class="{ rotated: isExpanded }"
            ></i>
        </div>

        <!-- Sous-menus -->
        <Transition name="submenu-compact">
            <ul v-if="item.items && isExpanded" class="submenu-items-compact">
                <AppMenuItem
                    v-for="(child, childIndex) in item.items"
                    :key="childIndex"
                    :item="child"
                    :index="childIndex"
                    :section-index="sectionIndex"
                    v-show="!child.can || can([child.can])"
                />
            </ul>
        </Transition>
    </li>
</template>

<style lang="scss" scoped>
.menu-item-compact {
    list-style: none;
    margin: 1px 0;
}

.menu-link-compact,
.menu-parent-compact {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    margin: 0 2px;
    border-radius: 6px;
    color: var(--text-color);
    text-decoration: none;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;

    &:hover {
        background: rgba(16, 185, 129, 0.05);

        .menu-icon-small {
            color: #10b981;
        }

        .menu-tooltip-small {
            opacity: 1;
            transform: translateX(-4px);
        }
    }
}

.menu-icon-small {
    font-size: 14px;
    margin-right: 8px;
    color: var(--text-color-secondary);
    min-width: 18px;
    text-align: center;
    transition: color 0.15s;
}

.menu-label-small {
    flex: 1;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.menu-badge-small {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    font-size: 10px;
    font-weight: 600;
    border-radius: 12px;
    margin-left: 4px;
}

.menu-arrow-small {
    font-size: 10px;
    margin-left: 4px;
    color: var(--text-color-secondary);
    transition: transform 0.2s;

    &.rotated {
        transform: rotate(180deg);
        color: #10b981;
    }
}

.menu-tooltip-small {
    position: absolute;
    right: 100%;
    top: 50%;
    transform: translateY(-50%) translateX(0);
    background: var(--surface-card);
    color: var(--text-color);
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 10px;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--surface-border);
    opacity: 0;
    transition: all 0.2s ease;
    pointer-events: none;
    z-index: 1000;
}

/* Élément actif */
.menu-item-compact.active {
    > .menu-link-compact,
    > .menu-parent-compact {
        background: rgba(16, 185, 129, 0.1);

        .menu-icon-small,
        .menu-label-small {
            color: #10b981;
            font-weight: 600;
        }

        &::before {
            content: "";
            position: absolute;
            left: 0;
            top: 2px;
            bottom: 2px;
            width: 2px;
            background: #10b981;
            border-radius: 0 2px 2px 0;
        }
    }
}

/* Sous-menus compacts */
.submenu-items-compact {
    list-style: none;
    padding: 2px 0 2px 24px;
    margin: 0;

    :deep(.menu-link-compact),
    :deep(.menu-parent-compact) {
        padding: 4px 8px;
        font-size: 12px;

        .menu-icon-small {
            font-size: 12px;
            margin-right: 6px;
        }
    }
}

/* Animation */
.submenu-compact-enter-active,
.submenu-compact-leave-active {
    transition: all 0.2s ease;
    overflow: hidden;
}

.submenu-compact-enter-from,
.submenu-compact-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateX(-5px);
}

.submenu-compact-enter-to,
.submenu-compact-leave-from {
    max-height: 300px;
    opacity: 1;
    transform: translateX(0);
}

/* Dark mode */
.dark {
    .menu-item-compact.active {
        > .menu-link-compact,
        > .menu-parent-compact {
            background: rgba(16, 185, 129, 0.15);
        }
    }
}
</style>
