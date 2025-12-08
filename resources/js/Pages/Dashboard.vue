<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    UsersIcon, 
    BriefcaseIcon, 
    BanknotesIcon, 
    ClockIcon, 
    ArrowUpRightIcon, 
    PlusIcon,
    CalendarDaysIcon,
    ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats: Object,
    upcomingAppointments: Array,
    recentDossiers: Array,
});

const formatCurrency = (amount) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount);
const formatDateShort = (date) => new Date(date).toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' });
const formatTime = (date) => new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute:'2-digit' });
</script>

<template>
    <Head title="Tableau de bord" />

    <MainLayout>
        <template #header>Vue d'ensemble</template>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            
            <div class="group bg-white p-6 rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 transition-all duration-300 cursor-default">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-indigo-50 rounded-2xl group-hover:bg-indigo-600 transition-colors duration-300">
                        <UsersIcon class="h-6 w-6 text-indigo-600 group-hover:text-white transition-colors" />
                    </div>
                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100">+12%</span>
                </div>
                <h3 class="text-slate-500 text-sm font-medium">Base Clients</h3>
                <p class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ stats.total_clients }}</p>
            </div>

            <div class="group bg-white p-6 rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-xl hover:shadow-blue-500/10 hover:-translate-y-1 transition-all duration-300 cursor-default">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 rounded-2xl group-hover:bg-blue-600 transition-colors duration-300">
                        <BriefcaseIcon class="h-6 w-6 text-blue-600 group-hover:text-white transition-colors" />
                    </div>
                </div>
                <h3 class="text-slate-500 text-sm font-medium">Dossiers Actifs</h3>
                <p class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ stats.active_dossiers }}</p>
            </div>

            <div class="group bg-white p-6 rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-slate-100 hover:shadow-xl hover:shadow-emerald-500/10 hover:-translate-y-1 transition-all duration-300 cursor-default">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-emerald-50 rounded-2xl group-hover:bg-emerald-600 transition-colors duration-300">
                        <ArrowTrendingUpIcon class="h-6 w-6 text-emerald-600 group-hover:text-white transition-colors" />
                    </div>
                </div>
                <h3 class="text-slate-500 text-sm font-medium">CA (Ce mois)</h3>
                <p class="text-3xl font-extrabold text-slate-900 mt-1 tracking-tight">{{ formatCurrency(stats.revenue_month) }}</p>
            </div>

            <div class="group bg-gradient-to-br from-slate-900 to-slate-800 p-6 rounded-3xl shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-10 -mt-10 blur-3xl pointer-events-none"></div>
                
                <div class="flex justify-between items-start mb-4 relative z-10">
                    <div class="p-3 bg-white/10 rounded-2xl backdrop-blur-sm border border-white/5">
                        <ClockIcon class="h-6 w-6 text-amber-400" />
                    </div>
                </div>
                <h3 class="text-slate-400 text-sm font-medium relative z-10">En Attente</h3>
                <p class="text-3xl font-extrabold text-white mt-1 tracking-tight relative z-10">{{ formatCurrency(stats.pending_payments) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 flex flex-col gap-6">
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden flex-1">
                    <div class="px-8 py-6 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white border border-slate-100 rounded-lg shadow-sm text-indigo-600">
                                <CalendarDaysIcon class="w-5 h-5"/>
                            </div>
                            <h2 class="text-lg font-bold text-slate-900">Agenda à venir</h2>
                        </div>
                        <Link :href="route('appointments.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 transition-colors px-3 py-1.5 rounded-lg hover:bg-indigo-50">
                            Voir tout <ArrowUpRightIcon class="w-4 h-4" />
                        </Link>
                    </div>

                    <div class="p-4">
                        <div v-for="apt in upcomingAppointments" :key="apt.id" class="group flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 border border-transparent hover:border-slate-100 transition-all duration-200 mb-1 last:mb-0">
                            
                            <div class="flex flex-col items-center justify-center w-16 h-16 bg-indigo-50/50 text-indigo-600 rounded-2xl border border-indigo-100 group-hover:bg-indigo-600 group-hover:text-white group-hover:border-indigo-600 transition-all duration-300 shadow-sm">
                                <span class="text-[0.65rem] font-bold uppercase tracking-wider opacity-80">{{ new Date(apt.start_time).toLocaleDateString('fr-FR', { weekday: 'short' }) }}</span>
                                <span class="text-xl font-bold leading-none mt-0.5">{{ new Date(apt.start_time).getDate() }}</span>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h4 class="text-slate-900 font-bold group-hover:text-indigo-700 transition-colors truncate text-base">{{ apt.title }}</h4>
                                <p class="text-sm text-slate-500 flex items-center gap-2 mt-1">
                                    <ClockIcon class="w-4 h-4 text-slate-400" />
                                    {{ formatTime(apt.start_time) }}
                                    <span class="text-slate-300">|</span>
                                    <span class="truncate">{{ apt.client_name }}</span>
                                </p>
                            </div>

                            <div class="text-right hidden sm:block">
                                 <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-semibold bg-white border border-slate-200 text-slate-600 shadow-sm capitalize">
                                    {{ apt.type }}
                                </span>
                            </div>
                        </div>
                        
                        <div v-if="upcomingAppointments.length === 0" class="py-16 text-center flex flex-col items-center">
                            <div class="bg-slate-50 p-4 rounded-full mb-3">
                                <CalendarDaysIcon class="w-8 h-8 text-slate-300" />
                            </div>
                            <p class="text-slate-900 font-medium">Aucun rendez-vous</p>
                            <p class="text-slate-400 text-sm">Votre agenda est libre pour le moment.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-[2rem] p-8 text-white shadow-xl shadow-indigo-500/20 relative overflow-hidden group">
                    <div class="absolute -bottom-10 -right-10 w-48 h-48 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all duration-500"></div>
                    
                    <h3 class="text-xl font-bold mb-2 relative z-10">Action Rapide</h3>
                    <p class="text-indigo-100 text-sm mb-8 leading-relaxed relative z-10 opacity-90">Créez un nouveau dossier ou ajoutez un client en un clic.</p>
                    
                    <div class="flex flex-col gap-3 relative z-10">
                         <Link :href="route('dossiers.index')" class="w-full bg-white text-indigo-700 py-3 rounded-xl text-center text-sm font-bold shadow-lg hover:shadow-xl hover:bg-indigo-50 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                            <PlusIcon class="w-5 h-5" /> Nouveau Dossier
                        </Link>
                         <Link :href="route('clients.index')" class="w-full bg-indigo-500/40 text-white py-3 rounded-xl text-center text-sm font-bold hover:bg-indigo-500/60 transition-colors backdrop-blur-md border border-white/10 flex items-center justify-center gap-2">
                            <UsersIcon class="w-5 h-5" /> Nouveau Client
                        </Link>
                    </div>
                </div>

                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-slate-900 font-bold text-lg">Dossiers Récents</h3>
                    </div>
                    
                    <ul class="space-y-1">
                        <li v-for="dossier in recentDossiers" :key="dossier.id">
                            <Link :href="route('dossiers.show', dossier.id)" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 group transition-colors">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-700 group-hover:text-indigo-600 transition-colors truncate">
                                        {{ dossier.subject }}
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5 truncate">{{ dossier.client_name }}</p>
                                </div>
                                <div class="flex items-center ml-2">
                                     <span class="h-2.5 w-2.5 rounded-full ring-2 ring-white" :class="{
                                        'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.6)]': dossier.status === 'open' || dossier.status === 'in_progress',
                                        'bg-slate-300': dossier.status === 'closed',
                                        'bg-amber-400': dossier.status === 'waiting'
                                    }"></span>
                                </div>
                            </Link>
                        </li>
                         <li v-if="recentDossiers.length === 0" class="text-center text-slate-400 text-sm py-4">
                            Aucun dossier récent.
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </MainLayout>
</template>