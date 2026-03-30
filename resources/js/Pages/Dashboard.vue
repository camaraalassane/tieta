<script setup>
import AppLayout from "@/sakai/layout/AppLayout.vue";
import Chart from 'primevue/chart';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    count_concours_actifs: { type: Number, default: 0 },
    users_non_admin: { type: Number, default: 0 },
    count_admins: { type: Number, default: 0 },
    count_superadmins: { type: Number, default: 0 },
    concours_urgents: { type: Array, default: () => [] },
    chart_data: { 
        type: Object, 
        default: () => ({ labels: [], datasets: [] }) 
    }
});

// Données formatées pour le graphique
const formattedChartData = computed(() => {
    return {
        labels: props.chart_data?.labels || [],
        datasets: props.chart_data?.datasets || []
    };
});

// Configuration améliorée du graphique
const chartOptions = ref({
    maintainAspectRatio: false,
    aspectRatio: 0.8,
    plugins: {
        legend: {
            display: true,
            position: 'top',
            labels: { 
                color: '#495057',
                font: { weight: 500 }
            }
        },
        tooltip: { 
            enabled: true,
            backgroundColor: '#1e293b',
            titleColor: '#f1f5f9',
            bodyColor: '#cbd5e1',
            borderColor: '#10b981',
            borderWidth: 1
        }
    },
    scales: {
        x: {
            ticks: {
                color: '#64748b',
                font: { weight: 500, size: 12 }
            },
            grid: { display: false }
        },
        y: {
            beginAtZero: true,
            ticks: {
                color: '#64748b',
                stepSize: 1,
                precision: 0,
                font: { size: 12 }
            },
            grid: {
                color: '#e2e8f0',
                drawBorder: false
            }
        }
    },
    animation: {
        duration: 1000,
        easing: 'easeInOutQuart'
    }
});

// Statistiques calculées
const stats = computed(() => ({
    totalUsers: props.users_non_admin + props.count_admins + props.count_superadmins,
    totalConcours: props.count_concours_actifs,
    urgentCount: props.concours_urgents.length
}));

// Formatage des dates
const formatDate = (dateString) => {
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
    const date = new Date(dateString);
    const today = new Date();
    const diffDays = Math.ceil((date - today) / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) return 'border-gray-500 bg-gray-50 dark:bg-gray-900/20';
    if (diffDays <= 3) return 'border-red-500 bg-red-50 dark:bg-red-900/10';
    if (diffDays <= 7) return 'border-orange-500 bg-orange-50 dark:bg-orange-900/10';
    return 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10';
};

// Rafraîchir les données
const refreshData = () => {
    router.reload({ only: ['count_concours_actifs', 'users_non_admin', 'count_admins', 'count_superadmins', 'concours_urgents', 'chart_data'] });
};

// Animation au montage
onMounted(() => {
    // Animation supplémentaire si nécessaire
});
</script>

<template>
    <app-layout>
        <!-- En-tête du dashboard -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                    <i class="pi pi-chart-line text-emerald-500"></i>
                    Tableau de bord
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Aperçu général de l'activité de la plateforme
                </p>
            </div>
            
            <div class="flex items-center gap-2 mt-4 sm:mt-0">
                <Button 
                    icon="pi pi-refresh" 
                    class="p-button-outlined p-button-sm border-emerald-500 text-emerald-600 hover:bg-emerald-50" 
                    @click="refreshData"
                    v-tooltip.top="'Rafraîchir les données'"
                />
                <span class="text-xs text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg">
                    {{ new Date().toLocaleTimeString() }}
                </span>
            </div>
        </div>

        <!-- Statistiques générales -->
        <div class="grid grid-cols-12 gap-4 mb-4">
            <div class="col-span-12 bg-emerald-50 dark:bg-emerald-900/10 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-500 rounded-xl flex items-center justify-center text-white">
                            <i class="pi pi-chart-bar text-2xl"></i>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Vue d'ensemble</span>
                            <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ stats.totalConcours }} concours • {{ stats.totalUsers }} utilisateurs
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-emerald-200 dark:bg-emerald-800 text-emerald-700 dark:text-emerald-300 rounded-full text-sm font-semibold">
                            {{ stats.urgentCount }} urgence(s)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cartes de statistiques -->
        <div class="grid grid-cols-12 gap-4">
            
            <!-- Concours Actifs -->
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="card dashboard-card hover:border-emerald-500 transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-medium text-gray-500 uppercase tracking-wider">Concours Actifs</span>
                            <div class="text-3xl font-bold text-emerald-600 mt-2">{{ props.count_concours_actifs }}</div>
                            <div class="text-xs text-gray-400 mt-1">+2 cette semaine</div>
                        </div>
                        <div class="stat-icon bg-emerald-100 dark:bg-emerald-900/30">
                            <i class="pi pi-trophy text-emerald-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="progress-bar mt-4">
                        <div class="h-1 bg-emerald-200 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 w-3/4"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Candidats -->
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="card dashboard-card hover:border-blue-500 transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-medium text-gray-500 uppercase tracking-wider">Candidats</span>
                            <div class="text-3xl font-bold text-blue-600 mt-2">{{ props.users_non_admin }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ ((props.users_non_admin / stats.totalUsers) * 100).toFixed(1) }}% du total</div>
                        </div>
                        <div class="stat-icon bg-blue-100 dark:bg-blue-900/30">
                            <i class="pi pi-users text-blue-500 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admins -->
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="card dashboard-card hover:border-purple-500 transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-medium text-gray-500 uppercase tracking-wider">Administrateurs</span>
                            <div class="text-3xl font-bold text-purple-600 mt-2">{{ props.count_admins }}</div>
                            <div class="text-xs text-gray-400 mt-1">Gestion des concours</div>
                        </div>
                        <div class="stat-icon bg-purple-100 dark:bg-purple-900/30">
                            <i class="pi pi-shield text-purple-500 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Super Admins -->
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <div class="card dashboard-card hover:border-orange-500 transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-sm font-medium text-gray-500 uppercase tracking-wider">Super Admins</span>
                            <div class="text-3xl font-bold text-orange-600 mt-2">{{ props.count_superadmins }}</div>
                            <div class="text-xs text-gray-400 mt-1">Accès total</div>
                        </div>
                        <div class="stat-icon bg-orange-100 dark:bg-orange-900/30">
                            <i class="pi pi-star-fill text-orange-500 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphique des candidatures -->
            <div class="col-span-12 lg:col-span-8">
                <div class="card dashboard-card h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h5 class="font-bold text-gray-700 dark:text-gray-300 text-lg flex items-center gap-2">
                            <i class="pi pi-chart-bar text-emerald-500"></i>
                            Candidatures par Concours
                        </h5>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-1 bg-emerald-100 text-emerald-600 rounded-full">Évolution</span>
                        </div>
                    </div>
                    
                    <div v-if="formattedChartData.labels.length > 0" class="h-80">
                        <Chart 
                            type="bar" 
                            :data="formattedChartData" 
                            :options="chartOptions" 
                            class="h-full" 
                        />
                    </div>
                    
                    <div v-else class="h-80 flex flex-col items-center justify-center text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700">
                        <i class="pi pi-chart-bar text-5xl mb-4 text-gray-300"></i>
                        <p class="font-medium text-gray-500">Aucune donnée disponible</p>
                        <p class="text-sm text-gray-400 mt-1">Les statistiques apparaîtront dès les premières candidatures</p>
                    </div>
                </div>
            </div>

            <!-- Alertes urgentes -->
            <div class="col-span-12 lg:col-span-4">
                <div class="card dashboard-card h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h5 class="font-bold text-gray-700 dark:text-gray-300 text-lg flex items-center gap-2">
                            <i class="pi pi-exclamation-triangle text-red-500"></i>
                            Dates limites proches
                        </h5>
                        <span class="text-xs px-2 py-1 bg-red-100 text-red-600 rounded-full">
                            {{ props.concours_urgents.length }} alerte(s)
                        </span>
                    </div>
                    
                    <div class="space-y-3 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                        <div 
                            v-for="c in props.concours_urgents" 
                            :key="c.id" 
                            :class="['concours-urgent-item', getUrgencyClass(c.date_limite)]"
                        >
                            <div class="flex items-start gap-3">
                                <div class="urgent-icon">
                                    <i class="pi pi-clock"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-800 dark:text-gray-200 truncate">{{ c.intitule }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs font-medium" :class="{
                                            'text-red-600': getUrgencyClass(c.date_limite).includes('red'),
                                            'text-orange-600': getUrgencyClass(c.date_limite).includes('orange'),
                                            'text-yellow-600': getUrgencyClass(c.date_limite).includes('yellow'),
                                            'text-gray-600': getUrgencyClass(c.date_limite).includes('gray')
                                        }">
                                            {{ formatDate(c.date_limite) }}
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ new Date(c.date_limite).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </div>
                                <Button 
                                    icon="pi pi-arrow-right" 
                                    class="p-button-rounded p-button-text p-button-sm"
                                    v-tooltip.left="'Voir les candidatures'"
                                />
                            </div>
                        </div>
                        
                        <div v-if="props.concours_urgents.length === 0" class="text-center py-12 text-gray-400 italic border-2 border-dashed border-gray-200 rounded-xl">
                            <i class="pi pi-check-circle text-4xl mb-3 text-emerald-400"></i>
                            <p>Aucune urgence</p>
                            <p class="text-sm">Toutes les dates limites sont respectées</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<style scoped>
.dashboard-card {
    background: var(--surface-card);
    padding: 1.5rem;
    border-radius: 16px;
    border: 1px solid var(--surface-border);
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.dashboard-card:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
}

.concours-urgent-item {
    padding: 1rem;
    border-radius: 12px;
    border-left-width: 4px;
    border-left-style: solid;
    transition: all 0.2s ease;
    cursor: pointer;
}

.concours-urgent-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.urgent-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: rgba(239, 68, 68, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
}

/* Scrollbar personnalisée */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #10b981;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #059669;
}

/* Animation de pulse pour les alertes */
@keyframes softPulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.pi-clock {
    animation: softPulse 2s infinite;
}

/* Dark mode adjustments */
.dark .dashboard-card {
    background: var(--surface-card);
    border-color: var(--surface-border);
}

.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: #1e293b;
}

.dark .concours-urgent-item:hover {
    background: rgba(255, 255, 255, 0.02);
}
</style>