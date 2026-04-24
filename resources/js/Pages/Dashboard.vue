<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Chart from "primevue/chart";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import Button from "primevue/button";
import Message from "primevue/message";

const props = defineProps({
    // Rôle de l'utilisateur
    user_role: { type: String, default: "superadmin" },

    // Données communes
    count_concours_actifs: { type: Number, default: 0 },
    count_concours_total: { type: Number, default: 0 },
    users_non_admin: { type: Number, default: 0 },
    count_admins: { type: Number, default: 0 },
    count_gerants: { type: Number, default: 0 },
    count_superadmins: { type: Number, default: 0 },
    count_operators: { type: Number, default: 0 },
    concours_urgents: { type: Array, default: () => [] },
    chart_data: {
        type: Object,
        default: () => ({ labels: [], datasets: [] }),
    },
    candidatures_chart: {
        type: Object,
        default: () => ({ labels: [], datasets: [] }),
    },
    stats_by_service: { type: Array, default: () => [] },

    // Données pour GERANT
    service: {
        type: Object,
        default: () => ({
            id: null,
            nom: null,
            description: null,
            personnel_count: 0,
            admins_count: 0,
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            concours_actifs: 0,
            concours_total: 0,
            candidatures_total: 0,
            taux_reussite: 0,
            candidatures_par_statut: { en_attente: 0, admis: 0, rejete: 0 },
        }),
    },

    // Données pour ADMIN
    is_admin: { type: Boolean, default: false },
    no_assigned_concours: { type: Boolean, default: false },
    count_candidatures_total: { type: Number, default: 0 },

    // Messages d'erreur
    error: { type: String, default: null },

    // Props pour SUPERADMIN
    services_list: { type: Array, default: () => [] },
    total_services: { type: Number, default: 0 },
    active_services: { type: Number, default: 0 },
    total_candidatures_global: { type: Number, default: 0 },
    chart_data_by_service: { type: Array, default: () => [] },
});

// État pour le service sélectionné
const selectedServiceId = ref(null);

// Données formatées pour le graphique principal
const formattedChartData = computed(() => {
    return {
        labels: props.chart_data?.labels || [],
        datasets: props.chart_data?.datasets || [],
    };
});

// Données du graphique filtrées selon le service sélectionné
const currentChartData = computed(() => {
    if (!selectedServiceId.value) {
        return formattedChartData.value;
    }

    const serviceData = props.chart_data_by_service?.find(
        (s) => s.service_id === selectedServiceId.value,
    );

    if (serviceData && serviceData.chart_data) {
        return serviceData.chart_data;
    }

    return formattedChartData.value;
});

// Titre du graphique
const chartTitle = computed(() => {
    if (!selectedServiceId.value) {
        return "Candidatures par Concours (Tous services)";
    }

    const service = props.services_list?.find(
        (s) => s.id === selectedServiceId.value,
    );
    return service ? `${service.nom}` : "Candidatures par Concours";
});

// Données formatées pour le graphique des candidatures
const formattedCandidaturesChart = computed(() => {
    return {
        labels: props.candidatures_chart?.labels || [
            "En attente",
            "Admis",
            "Rejetés",
        ],
        datasets: props.candidatures_chart?.datasets || [
            {
                label: "Candidatures",
                backgroundColor: ["#f59e0b", "#10b981", "#ef4444"],
                data: [
                    props.stats?.candidatures_par_statut?.en_attente || 0,
                    props.stats?.candidatures_par_statut?.admis || 0,
                    props.stats?.candidatures_par_statut?.rejete || 0,
                ],
            },
        ],
    };
});

// Configuration du graphique
const chartOptions = ref({
    maintainAspectRatio: false,
    aspectRatio: 0.8,
    responsive: true,
    plugins: {
        legend: {
            display: true,
            position: "top",
            labels: {
                color: "#495057",
                font: { weight: 500, size: 11 },
            },
        },
        tooltip: {
            enabled: true,
            backgroundColor: "#1e293b",
            titleColor: "#f1f5f9",
            bodyColor: "#cbd5e1",
            borderColor: "#10b981",
            borderWidth: 1,
        },
    },
    scales: {
        x: {
            ticks: {
                color: "#64748b",
                font: { weight: 500, size: 10 },
                maxRotation: 45,
                minRotation: 45,
            },
            grid: { display: false },
        },
        y: {
            beginAtZero: true,
            ticks: {
                color: "#64748b",
                stepSize: 1,
                precision: 0,
                font: { size: 10 },
            },
            grid: {
                color: "#e2e8f0",
                drawBorder: false,
            },
        },
    },
    animation: {
        duration: 1000,
        easing: "easeInOutQuart",
    },
});

// Configuration du graphique en camembert
const pieChartOptions = ref({
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                color: "#495057",
                font: { weight: 500, size: 11 },
            },
        },
        tooltip: {
            backgroundColor: "#1e293b",
            titleColor: "#f1f5f9",
            bodyColor: "#cbd5e1",
        },
    },
});

// Statistiques calculées
const totalStats = computed(() => ({
    totalUsers:
        (props.users_non_admin || 0) +
        (props.count_admins || 0) +
        (props.count_superadmins || 0) +
        (props.count_gerants || 0),
    totalConcours: props.count_concours_actifs || 0,
    urgentCount: props.concours_urgents?.length || 0,
}));

// Formatage des dates
const formatDate = (dateString) => {
    if (!dateString) return "Date inconnue";
    const date = new Date(dateString);
    const today = new Date();
    const diffDays = Math.ceil((date - today) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return "Aujourd'hui";
    if (diffDays === 1) return "Demain";
    if (diffDays < 0) return "Expiré";
    return `Dans ${diffDays} jours`;
};

// Couleurs dynamiques selon l'urgence
const getUrgencyClass = (dateString) => {
    if (!dateString) return "border-gray-500 bg-gray-50 dark:bg-gray-900/20";
    const date = new Date(dateString);
    const today = new Date();
    const diffDays = Math.ceil((date - today) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return "border-gray-500 bg-gray-50 dark:bg-gray-900/20";
    if (diffDays <= 3) return "border-red-500 bg-red-50 dark:bg-red-900/10";
    if (diffDays <= 7)
        return "border-orange-500 bg-orange-50 dark:bg-orange-900/10";
    return "border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10";
};

// Couleurs pour les cartes de service
const serviceColors = [
    {
        bg: "bg-emerald-50 dark:bg-emerald-900/20",
        border: "border-emerald-300 dark:border-emerald-700",
        text: "text-emerald-700 dark:text-emerald-300",
        icon: "text-emerald-500",
    },
    {
        bg: "bg-blue-50 dark:bg-blue-900/20",
        border: "border-blue-300 dark:border-blue-700",
        text: "text-blue-700 dark:text-blue-300",
        icon: "text-blue-500",
    },
    {
        bg: "bg-purple-50 dark:bg-purple-900/20",
        border: "border-purple-300 dark:border-purple-700",
        text: "text-purple-700 dark:text-purple-300",
        icon: "text-purple-500",
    },
    {
        bg: "bg-orange-50 dark:bg-orange-900/20",
        border: "border-orange-300 dark:border-orange-700",
        text: "text-orange-700 dark:text-orange-300",
        icon: "text-orange-500",
    },
    {
        bg: "bg-pink-50 dark:bg-pink-900/20",
        border: "border-pink-300 dark:border-pink-700",
        text: "text-pink-700 dark:text-pink-300",
        icon: "text-pink-500",
    },
    {
        bg: "bg-cyan-50 dark:bg-cyan-900/20",
        border: "border-cyan-300 dark:border-cyan-700",
        text: "text-cyan-700 dark:text-cyan-300",
        icon: "text-cyan-500",
    },
    {
        bg: "bg-indigo-50 dark:bg-indigo-900/20",
        border: "border-indigo-300 dark:border-indigo-700",
        text: "text-indigo-700 dark:text-indigo-300",
        icon: "text-indigo-500",
    },
    {
        bg: "bg-amber-50 dark:bg-amber-900/20",
        border: "border-amber-300 dark:border-amber-700",
        text: "text-amber-700 dark:text-amber-300",
        icon: "text-amber-500",
    },
];

const getServiceColor = (index) => serviceColors[index % serviceColors.length];

const selectService = (serviceId) => {
    selectedServiceId.value =
        selectedServiceId.value === serviceId ? null : serviceId;
};

const isServiceSelected = (serviceId) => selectedServiceId.value === serviceId;

const refreshData = () => {
    router.reload({
        only: [
            "user_role",
            "count_concours_actifs",
            "count_concours_total",
            "users_non_admin",
            "count_admins",
            "count_gerants",
            "count_superadmins",
            "concours_urgents",
            "chart_data",
            "candidatures_chart",
            "service",
            "stats",
            "services_list",
            "total_services",
            "chart_data_by_service",
        ],
    });
};

const viewConcours = (id) => {
    router.get(route("concours.show", id));
};
</script>

<template>
    <app-layout>
        <!-- En-tête du dashboard - RESPONSIVE -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1 class="header-title">
                    <i class="pi pi-chart-line text-emerald-500"></i>
                    Tableau de bord
                    <span
                        v-if="user_role === 'gerant' && service?.nom"
                        class="role-badge gerant"
                    >
                        {{ service.nom }}
                    </span>
                    <span
                        v-if="user_role === 'admin' && service?.nom"
                        class="role-badge admin"
                    >
                        {{ service.nom }}
                    </span>
                </h1>
                <p class="header-subtitle">
                    <span v-if="user_role === 'superadmin'"
                        >Aperçu général de l'activité de la plateforme</span
                    >
                    <span v-else-if="user_role === 'gerant'"
                        >Gestion de votre service et de ses concours</span
                    >
                    <span v-else-if="user_role === 'admin'"
                        >Gestion des concours de votre service</span
                    >
                </p>
            </div>

            <div class="header-actions">
                <Button
                    icon="pi pi-refresh"
                    class="refresh-btn"
                    @click="refreshData"
                    v-tooltip.top="'Rafraîchir les données'"
                />
                <span class="time-badge">
                    {{ new Date().toLocaleTimeString() }}
                </span>
            </div>
        </div>

        <!-- Message d'erreur -->
        <div v-if="error" class="error-message">
            <Message severity="error" :closable="true">
                <div class="error-content">
                    <i class="pi pi-exclamation-circle error-icon"></i>
                    <div>
                        <h3>Erreur</h3>
                        <p>{{ error }}</p>
                    </div>
                </div>
            </Message>
        </div>

        <!-- ⭐ DASHBOARD SUPERADMIN -->
        <template v-if="user_role === 'superadmin'">
            <!-- Bannière statistiques générales -->
            <div class="stats-banner">
                <div class="banner-content">
                    <div class="banner-left">
                        <div class="banner-icon">
                            <i class="pi pi-chart-bar"></i>
                        </div>
                        <div>
                            <span class="banner-label">Vue d'ensemble</span>
                            <div class="banner-numbers">
                                {{ total_services }} Services •
                                {{ count_concours_total }} Concours •
                                {{ total_candidatures_global }} Candidatures
                            </div>
                        </div>
                    </div>
                    <div class="banner-right">
                        <span class="banner-badge active"
                            >{{ active_services }} actifs</span
                        >
                        <span class="banner-badge urgent"
                            >{{ totalStats.urgentCount }} urgences</span
                        >
                    </div>
                </div>
            </div>

            <!-- Cartes de statistiques - 5 cartes (2 lignes sur mobile) -->
            <div class="stats-grid">
                <!-- Services -->
                <div class="stat-card teal">
                    <div class="stat-card-content">
                        <div>
                            <span class="stat-label">Total Services</span>
                            <div class="stat-value">{{ total_services }}</div>
                            <div class="stat-sub">
                                {{ active_services }} actifs
                            </div>
                        </div>
                        <div class="stat-icon teal">
                            <i class="pi pi-building"></i>
                        </div>
                    </div>
                </div>

                <!-- Concours Actifs -->
                <div class="stat-card emerald">
                    <div class="stat-card-content">
                        <div>
                            <span class="stat-label">Concours Actifs</span>
                            <div class="stat-value">
                                {{ count_concours_actifs }}
                            </div>
                        </div>
                        <div class="stat-icon emerald">
                            <i class="pi pi-trophy"></i>
                        </div>
                    </div>
                </div>

                <!-- Candidats -->
                <div class="stat-card blue">
                    <div class="stat-card-content">
                        <div>
                            <span class="stat-label">Candidats</span>
                            <div class="stat-value">{{ users_non_admin }}</div>
                        </div>
                        <div class="stat-icon blue">
                            <i class="pi pi-users"></i>
                        </div>
                    </div>
                </div>

                <!-- Administrateurs (rôle admin uniquement) -->
                <div class="stat-card purple">
                    <div class="stat-card-content">
                        <div>
                            <span class="stat-label">Administrateurs</span>
                            <div class="stat-value">{{ count_admins }}</div>
                        </div>
                        <div class="stat-icon purple">
                            <i class="pi pi-shield"></i>
                        </div>
                    </div>
                </div>

                <!-- Gérants -->
                <div class="stat-card orange">
                    <div class="stat-card-content">
                        <div>
                            <span class="stat-label">Gérants</span>
                            <div class="stat-value">
                                {{ count_gerants || 0 }}
                            </div>
                        </div>
                        <div class="stat-icon orange">
                            <i class="pi pi-user-edit"></i>
                        </div>
                    </div>
                </div>

                <!-- Superadmins -->
                <div class="stat-card pink">
                    <div class="stat-card-content">
                        <div>
                            <span class="stat-label">Superadmins</span>
                            <div class="stat-value">
                                {{ count_superadmins || 0 }}
                            </div>
                        </div>
                        <div class="stat-icon pink">
                            <i class="pi pi-crown"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des services en cartes colorées -->
            <div class="services-section">
                <div class="services-header">
                    <h5 class="services-title">
                        <i class="pi pi-building text-teal-500"></i>
                        Services disponibles
                    </h5>
                    <Button
                        v-if="selectedServiceId"
                        label="Réinitialiser"
                        icon="pi pi-times"
                        size="small"
                        outlined
                        severity="secondary"
                        class="reset-filter-btn"
                        @click="selectedServiceId = null"
                    />
                </div>

                <div class="services-grid">
                    <div
                        v-for="(service, index) in services_list"
                        :key="service.id"
                        :class="[
                            'service-card',
                            getServiceColor(index).bg,
                            getServiceColor(index).border,
                            isServiceSelected(service.id) ? 'selected' : '',
                        ]"
                        @click="selectService(service.id)"
                    >
                        <div class="service-card-header">
                            <div
                                :class="[
                                    'service-icon',
                                    isServiceSelected(service.id)
                                        ? 'selected-icon'
                                        : '',
                                    getServiceColor(index).icon,
                                ]"
                            >
                                <i class="pi pi-building"></i>
                            </div>
                            <span
                                v-if="service.is_active"
                                class="status-badge active"
                                >Actif</span
                            >
                            <span v-else class="status-badge inactive"
                                >Inactif</span
                            >
                        </div>
                        <h6
                            class="service-name"
                            :class="getServiceColor(index).text"
                        >
                            {{ service.nom }}
                        </h6>
                        <div class="service-stats">
                            <div class="service-stat">
                                <i class="pi pi-briefcase"></i>
                                <span>{{ service.concours_count }}</span>
                            </div>
                            <div class="service-stat">
                                <i class="pi pi-users"></i>
                                <span>{{ service.personnel_count }}</span>
                            </div>
                            <div class="service-stat">
                                <i class="pi pi-file"></i>
                                <span>{{ service.candidatures_count }}</span>
                            </div>
                        </div>
                        <div v-if="service.gerant" class="service-gerant">
                            <i class="pi pi-user-edit"></i>
                            <span>{{ service.gerant.name }}</span>
                        </div>
                    </div>

                    <div v-if="services_list.length === 0" class="no-services">
                        <i class="pi pi-building"></i>
                        <p>Aucun service disponible</p>
                    </div>
                </div>
            </div>

            <!-- Graphique et Alertes -->
            <div class="chart-alerts-grid">
                <!-- Graphique des candidatures -->
                <div class="chart-section">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h5 class="chart-title">
                                <i class="pi pi-chart-bar text-emerald-500"></i>
                                {{ chartTitle }}
                            </h5>
                            <span v-if="selectedServiceId" class="filter-badge"
                                >Filtre actif</span
                            >
                        </div>

                        <div
                            v-if="currentChartData.labels?.length > 0"
                            class="chart-container"
                        >
                            <Chart
                                type="bar"
                                :data="currentChartData"
                                :options="chartOptions"
                            />
                        </div>
                        <div v-else class="chart-empty">
                            <i class="pi pi-chart-bar"></i>
                            <p>Aucune donnée disponible</p>
                            <p v-if="selectedServiceId" class="empty-sub">
                                Ce service n'a pas encore de candidatures
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Alertes urgentes -->
                <div class="alerts-section">
                    <div class="alerts-card">
                        <div class="alerts-header">
                            <h5 class="alerts-title">
                                <i
                                    class="pi pi-exclamation-triangle text-red-500"
                                ></i>
                                Dates limites proches
                            </h5>
                            <span class="alerts-count">{{
                                concours_urgents.length
                            }}</span>
                        </div>

                        <div class="alerts-list">
                            <div
                                v-for="c in concours_urgents"
                                :key="c.id"
                                :class="[
                                    'alert-item',
                                    getUrgencyClass(c.date_limite),
                                ]"
                                @click="viewConcours(c.id)"
                            >
                                <div class="alert-icon">
                                    <i class="pi pi-clock"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">
                                        {{ c.intitule }}
                                    </div>
                                    <div class="alert-meta">
                                        <span
                                            :class="[
                                                'alert-date',
                                                getUrgencyClass(
                                                    c.date_limite,
                                                ).includes('red')
                                                    ? 'text-red-600'
                                                    : getUrgencyClass(
                                                            c.date_limite,
                                                        ).includes('orange')
                                                      ? 'text-orange-600'
                                                      : 'text-yellow-600',
                                            ]"
                                        >
                                            {{ formatDate(c.date_limite) }}
                                        </span>
                                        <span class="alert-full-date">{{
                                            new Date(
                                                c.date_limite,
                                            ).toLocaleDateString()
                                        }}</span>
                                    </div>
                                    <div v-if="c.service" class="alert-service">
                                        {{ c.service.nom }}
                                    </div>
                                </div>
                                <Button
                                    icon="pi pi-arrow-right"
                                    class="alert-btn"
                                    text
                                    rounded
                                    size="small"
                                />
                            </div>

                            <div
                                v-if="concours_urgents.length === 0"
                                class="no-alerts"
                            >
                                <i
                                    class="pi pi-check-circle text-emerald-400"
                                ></i>
                                <p>Aucune urgence</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ⭐ DASHBOARD GERANT -->
        <template v-else-if="user_role === 'gerant'">
            <!-- Message si aucun service -->
            <div v-if="!service?.id" class="error-message">
                <Message severity="warn" :closable="false">
                    <div class="error-content">
                        <i class="pi pi-exclamation-triangle error-icon"></i>
                        <div>
                            <h3>Aucun service associé</h3>
                            <p>
                                Vous n'êtes associé à aucun service. Veuillez
                                contacter un super administrateur.
                            </p>
                        </div>
                    </div>
                </Message>
            </div>

            <!-- ⭐ Statistiques du service (corrigé) -->
            <div v-else class="stats-content">
                <!-- En-tête du service -->
                <div class="service-banner">
                    <div class="service-banner-content">
                        <div class="service-banner-left">
                            <div class="service-banner-icon">
                                <i class="pi pi-building"></i>
                            </div>
                            <div>
                                <h2>{{ service.nom }}</h2>
                                <p>
                                    {{
                                        service.description ||
                                        "Service de gestion des concours"
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="service-banner-right">
                            <div class="service-stat-item">
                                <span class="service-stat-value">{{
                                    service.personnel_count || 0
                                }}</span>
                                <span class="service-stat-label"
                                    >Personnel</span
                                >
                            </div>
                            <div class="service-stat-item">
                                <span class="service-stat-value">{{
                                    service.admins_count || 0
                                }}</span>
                                <span class="service-stat-label">Admins</span>
                            </div>
                            <div class="service-stat-item">
                                <span class="service-stat-value">{{
                                    stats.concours_actifs || 0
                                }}</span>
                                <span class="service-stat-label">Actifs</span>
                            </div>
                            <div class="service-stat-item">
                                <span class="service-stat-value"
                                    >{{ stats.taux_reussite || 0 }}%</span
                                >
                                <span class="service-stat-label">Réussite</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique et Répartition -->
                <div class="gerant-grid">
                    <div class="chart-section">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="pi pi-chart-bar text-emerald-500"></i>
                                Candidatures par concours
                            </h5>
                            <div
                                v-if="formattedChartData.labels.length > 0"
                                class="chart-container"
                            >
                                <Chart
                                    type="bar"
                                    :data="formattedChartData"
                                    :options="chartOptions"
                                />
                            </div>
                            <div v-else class="chart-empty">
                                <i class="pi pi-chart-line"></i>
                                <p>Aucune candidature pour le moment</p>
                            </div>
                        </div>
                    </div>

                    <div class="chart-section">
                        <div class="chart-card">
                            <h5 class="chart-title">
                                <i class="pi pi-chart-pie text-emerald-500"></i>
                                Statut des candidatures
                            </h5>
                            <div class="pie-container">
                                <Chart
                                    type="pie"
                                    :data="formattedCandidaturesChart"
                                    :options="pieChartOptions"
                                />
                            </div>
                            <div class="pie-legend">
                                <div class="legend-item">
                                    <span
                                        class="legend-dot"
                                        style="background: #f59e0b"
                                    ></span>
                                    <span
                                        >En attente:
                                        {{
                                            stats.candidatures_par_statut
                                                ?.en_attente || 0
                                        }}</span
                                    >
                                </div>
                                <div class="legend-item">
                                    <span
                                        class="legend-dot"
                                        style="background: #10b981"
                                    ></span>
                                    <span
                                        >Admis:
                                        {{
                                            stats.candidatures_par_statut
                                                ?.admis || 0
                                        }}</span
                                    >
                                </div>
                                <div class="legend-item">
                                    <span
                                        class="legend-dot"
                                        style="background: #ef4444"
                                    ></span>
                                    <span
                                        >Rejetés:
                                        {{
                                            stats.candidatures_par_statut
                                                ?.rejete || 0
                                        }}</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertes urgentes -->
                <div class="alerts-section">
                    <div class="alerts-card">
                        <div class="alerts-header">
                            <h5 class="alerts-title">
                                <i
                                    class="pi pi-exclamation-triangle text-red-500"
                                ></i>
                                Dates limites proches
                            </h5>
                            <span class="alerts-count">{{
                                concours_urgents.length
                            }}</span>
                        </div>
                        <div class="alerts-list">
                            <div
                                v-for="c in concours_urgents"
                                :key="c.id"
                                :class="[
                                    'alert-item',
                                    getUrgencyClass(c.date_limite),
                                ]"
                                @click="viewConcours(c.id)"
                            >
                                <div class="alert-icon">
                                    <i class="pi pi-clock"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">
                                        {{ c.intitule }}
                                    </div>
                                    <div class="alert-meta">
                                        <span
                                            :class="[
                                                'alert-date',
                                                getUrgencyClass(
                                                    c.date_limite,
                                                ).includes('red')
                                                    ? 'text-red-600'
                                                    : getUrgencyClass(
                                                            c.date_limite,
                                                        ).includes('orange')
                                                      ? 'text-orange-600'
                                                      : 'text-yellow-600',
                                            ]"
                                        >
                                            {{ formatDate(c.date_limite) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="concours_urgents.length === 0"
                                class="no-alerts"
                            >
                                <i
                                    class="pi pi-check-circle text-emerald-400"
                                ></i>
                                <p>Aucune urgence</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ⭐ DASHBOARD ADMIN -->
        <template v-else-if="user_role === 'admin'">
            <!-- Message pour admin sans concours -->
            <div v-if="no_assigned_concours" class="error-message">
                <Message severity="warn" :closable="false">
                    <div class="error-content">
                        <i class="pi pi-exclamation-triangle error-icon"></i>
                        <div>
                            <h3>Aucun concours assigné</h3>
                            <p>
                                Vous n'êtes assigné à aucun concours dans le
                                service {{ service?.nom }}.
                            </p>
                        </div>
                    </div>
                </Message>
            </div>

            <!-- ⭐ Statistiques Admin (corrigé) -->
            <div v-else class="stats-content">
                <!-- Cartes de stats -->
                <div class="stats-grid">
                    <div class="stat-card emerald">
                        <div class="stat-card-content">
                            <div>
                                <span class="stat-label">Concours Actifs</span>
                                <div class="stat-value">
                                    {{ count_concours_actifs }}
                                </div>
                            </div>
                            <div class="stat-icon emerald">
                                <i class="pi pi-trophy"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card blue">
                        <div class="stat-card-content">
                            <div>
                                <span class="stat-label">Total Concours</span>
                                <div class="stat-value">
                                    {{ count_concours_total || 0 }}
                                </div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="pi pi-bookmark"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card purple">
                        <div class="stat-card-content">
                            <div>
                                <span class="stat-label">Candidatures</span>
                                <div class="stat-value">
                                    {{ count_candidatures_total || 0 }}
                                </div>
                            </div>
                            <div class="stat-icon purple">
                                <i class="pi pi-users"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card orange">
                        <div class="stat-card-content">
                            <div>
                                <span class="stat-label">Urgences</span>
                                <div class="stat-value">
                                    {{ concours_urgents.length }}
                                </div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="pi pi-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique -->
                <div class="chart-section">
                    <div class="chart-card">
                        <h5 class="chart-title">
                            <i class="pi pi-chart-bar text-emerald-500"></i>
                            Candidatures par concours
                        </h5>
                        <div
                            v-if="formattedChartData.labels.length > 0"
                            class="chart-container"
                        >
                            <Chart
                                type="bar"
                                :data="formattedChartData"
                                :options="chartOptions"
                            />
                        </div>
                        <div v-else class="chart-empty">
                            <i class="pi pi-chart-line"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    </div>
                </div>

                <!-- Alertes -->
                <div class="alerts-section">
                    <div class="alerts-card">
                        <h5 class="alerts-title">
                            <i
                                class="pi pi-exclamation-triangle text-red-500"
                            ></i>
                            Dates limites proches
                        </h5>
                        <div class="alerts-list">
                            <div
                                v-for="c in concours_urgents.slice(0, 5)"
                                :key="c.id"
                                :class="[
                                    'alert-item',
                                    getUrgencyClass(c.date_limite),
                                ]"
                                @click="viewConcours(c.id)"
                            >
                                <div class="alert-icon">
                                    <i class="pi pi-clock"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">
                                        {{ c.intitule }}
                                    </div>
                                    <div class="alert-meta">
                                        <span class="alert-date text-red-600">{{
                                            formatDate(c.date_limite)
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="concours_urgents.length === 0"
                                class="no-alerts"
                            >
                                <i
                                    class="pi pi-check-circle text-emerald-400"
                                ></i>
                                <p>Aucune urgence</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </app-layout>
</template>

<style scoped>
/* ============================================ */
/* STYLES GLOBAUX - FULL RESPONSIVE */
/* ============================================ */
* {
    box-sizing: border-box;
}

/* ============================================ */
/* HEADER */
/* ============================================ */
.dashboard-header {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    border: 1px solid #f3f4f6;
    gap: 0.75rem;
}

.dark .dashboard-header {
    background: #1f2937;
    border-color: #374151;
}

@media (min-width: 640px) {
    .dashboard-header {
        flex-direction: row;
        align-items: center;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }
}

.header-left {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.header-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0;
}

.dark .header-title {
    color: white;
}

@media (min-width: 640px) {
    .header-title {
        font-size: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .header-title {
        font-size: 1.875rem;
    }
}

.role-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.75rem;
    border-radius: 9999px;
}

.role-badge.gerant {
    background: #d1fae5;
    color: #065f46;
}

.role-badge.admin {
    background: #dbeafe;
    color: #1e40af;
}

.header-subtitle {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

@media (min-width: 640px) {
    .header-subtitle {
        font-size: 0.875rem;
    }
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
}

@media (min-width: 640px) {
    .header-actions {
        width: auto;
    }
}

.refresh-btn {
    border-color: #10b981 !important;
    color: #059669 !important;
}

.refresh-btn:hover {
    background: #ecfdf5 !important;
}

.time-badge {
    font-size: 0.7rem;
    color: #9ca3af;
    background: #f9fafb;
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    margin-left: auto;
}

.dark .time-badge {
    background: #374151;
}

@media (min-width: 640px) {
    .time-badge {
        margin-left: 0;
    }
}

/* ============================================ */
/* ERROR MESSAGE */
/* ============================================ */
.error-message {
    margin-bottom: 1rem;
}

.error-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.error-icon {
    font-size: 1.5rem;
    color: #ef4444;
}

.error-content h3 {
    font-weight: 600;
    margin: 0;
    font-size: 0.875rem;
}

.error-content p {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0.25rem 0 0 0;
}

/* ============================================ */
/* STATS BANNER */
/* ============================================ */
.stats-banner {
    background: linear-gradient(to right, #10b981, #14b8a6);
    border-radius: 0.75rem;
    padding: 1rem;
    color: white;
    margin-bottom: 1rem;
}

@media (min-width: 640px) {
    .stats-banner {
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
    }
}

.banner-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .banner-content {
        flex-direction: row;
        align-items: center;
    }
}

.banner-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.banner-icon {
    width: 3rem;
    height: 3rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.banner-icon i {
    font-size: 1.5rem;
}

@media (min-width: 640px) {
    .banner-icon {
        width: 3.5rem;
        height: 3.5rem;
    }
    .banner-icon i {
        font-size: 1.75rem;
    }
}

.banner-label {
    font-size: 0.75rem;
    color: #a7f3d0;
}

@media (min-width: 640px) {
    .banner-label {
        font-size: 0.875rem;
    }
}

.banner-numbers {
    font-size: 0.875rem;
    font-weight: 700;
    margin-top: 0.125rem;
}

@media (min-width: 640px) {
    .banner-numbers {
        font-size: 1.25rem;
    }
}

@media (min-width: 1024px) {
    .banner-numbers {
        font-size: 1.5rem;
    }
}

.banner-right {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.banner-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
}

.banner-badge.active {
    background: rgba(255, 255, 255, 0.2);
}

.banner-badge.urgent {
    background: rgba(239, 68, 68, 0.3);
}

/* ============================================ */
/* STATS GRID - 6 cartes */
/* ============================================ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    margin-bottom: 1rem;
}

@media (min-width: 480px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(6, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
}

.stat-card {
    background: white;
    padding: 0.75rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}

.dark .stat-card {
    background: #1f2937;
    border-color: #374151;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-label {
    font-size: 0.6rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

@media (min-width: 640px) {
    .stat-label {
        font-size: 0.7rem;
    }
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    margin-top: 0.25rem;
}

@media (min-width: 640px) {
    .stat-value {
        font-size: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .stat-value {
        font-size: 1.875rem;
    }
}

.stat-card.teal .stat-value {
    color: #0d9488;
}
.stat-card.emerald .stat-value {
    color: #059669;
}
.stat-card.blue .stat-value {
    color: #2563eb;
}
.stat-card.purple .stat-value {
    color: #7c3aed;
}
.stat-card.orange .stat-value {
    color: #ea580c;
}
.stat-card.pink .stat-value {
    color: #db2777;
}

.stat-sub {
    font-size: 0.6rem;
    color: #9ca3af;
    margin-top: 0.125rem;
}

.stat-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

@media (min-width: 640px) {
    .stat-icon {
        width: 2.5rem;
        height: 2.5rem;
    }
}

.stat-icon i {
    font-size: 1rem;
}

.stat-icon.teal {
    background: #ccfbf1;
    color: #0d9488;
}
.stat-icon.emerald {
    background: #d1fae5;
    color: #059669;
}
.stat-icon.blue {
    background: #dbeafe;
    color: #2563eb;
}
.stat-icon.purple {
    background: #ede9fe;
    color: #7c3aed;
}
.stat-icon.orange {
    background: #ffedd5;
    color: #ea580c;
}
.stat-icon.pink {
    background: #fce7f3;
    color: #db2777;
}

.dark .stat-icon.teal {
    background: rgba(13, 148, 136, 0.2);
}
.dark .stat-icon.emerald {
    background: rgba(5, 150, 105, 0.2);
}
.dark .stat-icon.blue {
    background: rgba(37, 99, 235, 0.2);
}
.dark .stat-icon.purple {
    background: rgba(124, 58, 237, 0.2);
}
.dark .stat-icon.orange {
    background: rgba(234, 88, 12, 0.2);
}
.dark .stat-icon.pink {
    background: rgba(219, 39, 119, 0.2);
}

/* ============================================ */
/* SERVICES SECTION */
/* ============================================ */
.services-section {
    background: white;
    padding: 1rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    margin-bottom: 1rem;
}

.dark .services-section {
    background: #1f2937;
    border-color: #374151;
}

@media (min-width: 640px) {
    .services-section {
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
}

.services-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.services-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.dark .services-title {
    color: #d1d5db;
}

@media (min-width: 640px) {
    .services-title {
        font-size: 1rem;
    }
}

.reset-filter-btn {
    font-size: 0.7rem !important;
    padding: 0.25rem 0.75rem !important;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}

@media (min-width: 480px) {
    .services-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
}

@media (min-width: 768px) {
    .services-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 1024px) {
    .services-grid {
        grid-template-columns: repeat(6, 1fr);
        gap: 0.75rem;
    }
}

.service-card {
    padding: 0.75rem;
    border-radius: 0.75rem;
    border-width: 2px;
    cursor: pointer;
    transition: all 0.2s;
}

.service-card:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.service-card.selected {
    box-shadow: 0 0 0 2px #14b8a6;
    transform: scale(1.02);
}

.service-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.service-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.5);
}

.dark .service-icon {
    background: rgba(31, 41, 55, 0.5);
}

.service-icon.selected-icon {
    background: #14b8a6 !important;
    color: white !important;
}

.service-icon i {
    font-size: 1rem;
}

.status-badge {
    font-size: 0.55rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.dark .status-badge.inactive {
    background: #374151;
    color: #9ca3af;
}

.service-name {
    font-size: 0.75rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (min-width: 640px) {
    .service-name {
        font-size: 0.875rem;
    }
}

.service-stats {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.service-stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.6rem;
    color: #6b7280;
}

.service-stat i {
    font-size: 0.6rem;
    width: 1rem;
}

.service-gerant {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding-top: 0.5rem;
    margin-top: 0.25rem;
    border-top: 1px solid #e5e7eb;
    font-size: 0.6rem;
    color: #6b7280;
}

.dark .service-gerant {
    border-color: #4b5563;
}

.service-gerant span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.no-services {
    grid-column: 1 / -1;
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.no-services i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.no-services p {
    font-size: 0.75rem;
    margin: 0;
}

/* ============================================ */
/* CHART & ALERTS GRID */
/* ============================================ */
.chart-alerts-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 1024px) {
    .chart-alerts-grid {
        grid-template-columns: 2fr 1fr;
    }
}

.chart-section,
.alerts-section {
    min-width: 0;
}

.chart-card,
.alerts-card {
    background: white;
    padding: 1rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    height: 100%;
}

.dark .chart-card,
.dark .alerts-card {
    background: #1f2937;
    border-color: #374151;
}

@media (min-width: 640px) {
    .chart-card,
    .alerts-card {
        padding: 1.25rem;
    }
}

.chart-header,
.alerts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.chart-title,
.alerts-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.dark .chart-title,
.dark .alerts-title {
    color: #d1d5db;
}

@media (min-width: 640px) {
    .chart-title,
    .alerts-title {
        font-size: 1rem;
    }
}

.filter-badge {
    font-size: 0.6rem;
    padding: 0.125rem 0.5rem;
    background: #ccfbf1;
    color: #0d9488;
    border-radius: 9999px;
}

.dark .filter-badge {
    background: rgba(20, 184, 166, 0.2);
    color: #5eead4;
}

.alerts-count {
    font-size: 0.7rem;
    padding: 0.125rem 0.5rem;
    background: #fee2e2;
    color: #dc2626;
    border-radius: 9999px;
    font-weight: 600;
}

.chart-container {
    height: 250px;
}

@media (min-width: 640px) {
    .chart-container {
        height: 300px;
    }
}

@media (min-width: 1024px) {
    .chart-container {
        height: 320px;
    }
}

.chart-empty {
    height: 250px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    background: #f9fafb;
    border-radius: 0.75rem;
    border: 2px dashed #e5e7eb;
}

.dark .chart-empty {
    background: #111827;
    border-color: #374151;
}

.chart-empty i {
    font-size: 3rem;
    margin-bottom: 0.75rem;
}

.chart-empty p {
    font-size: 0.875rem;
    font-weight: 500;
    margin: 0;
}

.empty-sub {
    font-size: 0.75rem !important;
    margin-top: 0.25rem !important;
    color: #9ca3af !important;
}

/* ============================================ */
/* ALERTS LIST */
/* ============================================ */
.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
    padding-right: 0.25rem;
}

@media (min-width: 640px) {
    .alerts-list {
        max-height: 350px;
    }
}

.alert-item {
    padding: 0.75rem;
    border-radius: 0.5rem;
    border-left-width: 4px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.alert-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.alert-icon {
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.5rem;
    background: rgba(239, 68, 68, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
    flex-shrink: 0;
}

.alert-icon i {
    font-size: 0.875rem;
}

.alert-content {
    flex: 1;
    min-width: 0;
}

.alert-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dark .alert-title {
    color: #e5e7eb;
}

@media (min-width: 640px) {
    .alert-title {
        font-size: 0.875rem;
    }
}

.alert-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.125rem;
}

.alert-date {
    font-size: 0.65rem;
    font-weight: 500;
}

.alert-full-date {
    font-size: 0.6rem;
    color: #9ca3af;
}

.alert-service {
    font-size: 0.6rem;
    color: #6b7280;
    margin-top: 0.125rem;
}

.alert-btn {
    flex-shrink: 0;
}

.no-alerts {
    text-align: center;
    padding: 2rem 1rem;
    color: #9ca3af;
    border: 2px dashed #e5e7eb;
    border-radius: 0.75rem;
}

.dark .no-alerts {
    border-color: #374151;
}

.no-alerts i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.no-alerts p {
    font-size: 0.75rem;
    margin: 0;
    font-style: italic;
}

/* ============================================ */
/* SCROLLBAR */
/* ============================================ */
.alerts-list::-webkit-scrollbar {
    width: 3px;
}

.alerts-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.alerts-list::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 3px;
}

.dark .alerts-list::-webkit-scrollbar-track {
    background: #374151;
}

/* ============================================ */
/* ANIMATION */
/* ============================================ */
@keyframes softPulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.pi-clock {
    animation: softPulse 2s infinite;
}
.service-banner {
    background: linear-gradient(to right, #10b981, #059669);
    border-radius: 0.75rem;
    padding: 1rem;
    color: white;
    margin-bottom: 1rem;
}

@media (min-width: 640px) {
    .service-banner {
        padding: 1.25rem 1.5rem;
    }
}

.service-banner-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 768px) {
    .service-banner-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.service-banner-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.service-banner-icon {
    width: 3rem;
    height: 3rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.service-banner-icon i {
    font-size: 1.5rem;
}

.service-banner-left h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
}

.service-banner-left p {
    font-size: 0.75rem;
    color: #a7f3d0;
    margin: 0.25rem 0 0 0;
}

.service-banner-right {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.service-stat-item {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    text-align: center;
    min-width: 70px;
}

.service-stat-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
}

.service-stat-label {
    display: block;
    font-size: 0.6rem;
    color: #a7f3d0;
}

/* ============================================ */
/* GERANT GRID */
/* ============================================ */
.gerant-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (min-width: 1024px) {
    .gerant-grid {
        grid-template-columns: 3fr 2fr;
    }
}

.pie-container {
    height: 200px;
}

@media (min-width: 640px) {
    .pie-container {
        height: 250px;
    }
}

.pie-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.75rem;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    color: #6b7280;
}

.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* ============================================ */
/* STATS CONTENT */
/* ============================================ */
.stats-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
</style>
