<script setup></script>

<template>
    <div class="layout-footer py-4 mt-8 border-top-1 border-200">
        <div class="flex flex-column md:flex-row align-items-center justify-content-between gap-3 w-full px-4">
            <!-- Nom de l'application avec style harmonisé -->
            <div class="flex align-items-center">
                <span class="text-2xl font-light tracking-wider text-900">
                    Cɛ<span class="text-emerald-500 font-bold">TA</span>
                </span>
            </div>

            <!-- Copyright et Slogan -->
            <div class="text-center md:text-right">
                <span class="text-surface-500 text-sm font-medium">
                    &copy; 2026 — Système de Gestion des Concours. 
                    <span class="hidden md:inline text-emerald-600 ml-1">Tous droits réservés.</span>
                </span>
            </div>
        </div>
    </div>
</template>
<style lang="scss" scoped>
.layout-footer {
    background: var(--surface-card); /* Harmonisé avec vos sakai-card */
    transition: margin-left 0.2s;
    display: flex;
    align-items: center;

    // Petite barre de couleur émeraude au-dessus pour le rappel visuel
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
        background: #10b981; // Rappel émeraude
        border-radius: 2px;
    }
}

// Animation légère au survol du logo
.text-900:hover .text-emerald-500 {
    filter: brightness(1.1);
    transition: filter 0.3s;
}

@media (max-width: 768px) {
    .layout-footer {
        padding-bottom: 2rem; // Plus d'espace sur mobile pour le confort
    }
}
</style>

