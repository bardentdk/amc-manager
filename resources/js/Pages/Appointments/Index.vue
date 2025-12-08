<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import AppointmentModal from '@/Components/AppointmentModal.vue';
import { 
    PlusIcon, ClockIcon, MapPinIcon, 
    PencilSquareIcon, TrashIcon, MagnifyingGlassIcon,
    UserCircleIcon, CalendarDaysIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce'; // N'oublie pas : npm install lodash si erreur

const props = defineProps({
    appointments: Object,
    filters: Object, // Reçu du contrôleur
});

// --- État des Filtres ---
const currentFilter = ref(props.filters?.filter || 'all');
const search = ref(props.filters?.search || '');

// --- Logique de Filtrage ---
// Fonction pour changer le filtre (Boutons)
const setFilter = (val) => {
    currentFilter.value = val;
    triggerSearch();
};

// Fonction unique pour lancer la requête Inertia
const triggerSearch = () => {
    router.get(route('appointments.index'), { 
        filter: currentFilter.value, 
        search: search.value 
    }, {
        preserveState: true, // Garde l'état des composants (modales, etc)
        preserveScroll: true, // Ne remonte pas en haut de page
        replace: true // Remplace l'historique URL pour ne pas polluer le bouton "précédent"
    });
};

// Recherche avec Debounce (attendre que l'utilisateur finisse de taper)
watch(search, debounce(() => {
    triggerSearch();
}, 300));


// --- Gestion Modale (Identique avant) ---
const isModalOpen = ref(false);
const editingApt = ref(null);

const openCreate = () => {
    editingApt.value = null;
    isModalOpen.value = true;
};

const openEdit = (apt) => {
    editingApt.value = apt;
    isModalOpen.value = true;
};

const deleteApt = (apt) => {
    if (confirm('Supprimer définitivement ce rendez-vous ?')) {
        router.delete(route('appointments.destroy', apt.id));
    }
};

// --- Helpers de Formatage ---
const getDayNumber = (date) => new Date(date).getDate();
const getMonthAbbr = (date) => new Date(date).toLocaleDateString('fr-FR', { month: 'short' }).replace('.', '');
const getDayName = (date) => new Date(date).toLocaleDateString('fr-FR', { weekday: 'long' });
const formatTime = (date) => new Date(date).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

const typeStyles = {
    legal: { label: 'Avocat', bg: 'bg-indigo-50', text: 'text-indigo-700', border: 'border-indigo-100', icon: '⚖️' },
    closing: { label: 'Closing', bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-100', icon: '✍️' },
    phone: { label: 'Appel', bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-100', icon: '📞' },
    other: { label: 'Autre', bg: 'bg-slate-50', text: 'text-slate-600', border: 'border-slate-100', icon: '📌' },
};
const getTypeStyle = (type) => typeStyles[type] || typeStyles.other;
</script>

<template>
    <Head title="Agenda" />

    <MainLayout>
        <template #header>Agenda</template>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            
            <div class="flex p-1 bg-white border border-slate-200 rounded-xl shadow-sm w-fit">
                <button 
                    @click="setFilter('all')"
                    class="px-4 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
                    :class="currentFilter === 'all' 
                        ? 'bg-slate-900 text-white shadow-md' 
                        : 'text-slate-500 hover:text-indigo-600 hover:bg-slate-50'"
                >
                    Tout
                </button>
                <button 
                    @click="setFilter('upcoming')"
                    class="px-4 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
                    :class="currentFilter === 'upcoming' 
                        ? 'bg-slate-900 text-white shadow-md' 
                        : 'text-slate-500 hover:text-indigo-600 hover:bg-slate-50'"
                >
                    À venir
                </button>
                <button 
                    @click="setFilter('past')"
                    class="px-4 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
                    :class="currentFilter === 'past' 
                        ? 'bg-slate-900 text-white shadow-md' 
                        : 'text-slate-500 hover:text-indigo-600 hover:bg-slate-50'"
                >
                    Passés
                </button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative hidden sm:block">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                    <input 
                        type="text" 
                        v-model="search"
                        placeholder="Rechercher (Titre, Lieu...)" 
                        class="pl-9 pr-4 py-2 text-sm border-none ring-1 ring-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 w-64 shadow-sm transition-shadow" 
                    />
                </div>
                
                <button @click="openCreate" class="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold py-2.5 px-5 rounded-xl shadow-lg shadow-indigo-500/30 transform transition-all active:scale-95 duration-200">
                    <PlusIcon class="h-5 w-5" />
                    <span class="hidden sm:inline">Nouveau RDV</span>
                </button>
            </div>
        </div>

        <div class="relative max-w-5xl mx-auto pb-10">
            
            <div class="absolute left-8 top-0 bottom-0 w-px bg-slate-200 hidden md:block"></div>

            <div class="space-y-6">
                <div v-for="apt in appointments.data" :key="apt.id" class="relative group">
                    
                    <div class="absolute left-0 top-0 hidden md:flex flex-col items-center justify-center w-16 h-16 bg-white border border-slate-100 rounded-2xl shadow-sm z-10 group-hover:scale-110 group-hover:border-indigo-200 transition-all duration-300">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wide">{{ getMonthAbbr(apt.start_time) }}</span>
                        <span class="text-xl font-black text-slate-800">{{ getDayNumber(apt.start_time) }}</span>
                    </div>

                    <div class="md:ml-24 bg-white rounded-2xl p-5 border border-slate-100 shadow-[0_2px_8px_-2px_rgba(0,0,0,0.05)] hover:shadow-lg hover:shadow-indigo-500/5 hover:-translate-y-0.5 transition-all duration-300 flex flex-col sm:flex-row gap-5">
                        
                        <div class="flex flex-row sm:flex-col items-center sm:items-start gap-3 sm:w-32 flex-shrink-0">
                            <div class="md:hidden flex flex-col items-center justify-center w-12 h-12 bg-slate-50 rounded-lg border border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">{{ getMonthAbbr(apt.start_time) }}</span>
                                <span class="text-lg font-bold text-slate-800">{{ getDayNumber(apt.start_time) }}</span>
                            </div>

                            <div class="text-right sm:text-left">
                                <div class="flex items-center gap-1.5 text-slate-700 font-bold font-mono text-base">
                                    <ClockIcon class="w-4 h-4 text-indigo-500" />
                                    {{ formatTime(apt.start_time) }}
                                </div>
                                <div class="text-xs text-slate-400 font-medium mt-0.5 capitalize hidden sm:block">
                                    {{ getDayName(apt.start_time) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0 border-l border-slate-50 pl-0 sm:pl-5 sm:border-l-0">
                            <div class="flex items-center gap-3 mb-1">
                                <span :class="`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-bold border ${getTypeStyle(apt.type).bg} ${getTypeStyle(apt.type).text} ${getTypeStyle(apt.type).border}`">
                                    <span>{{ getTypeStyle(apt.type).icon }}</span>
                                    {{ getTypeStyle(apt.type).label }}
                                </span>
                                <span v-if="apt.dossier" class="text-xs font-medium text-slate-400 bg-slate-50 px-2 py-0.5 rounded border border-slate-100 truncate max-w-[150px]">
                                    Réf: {{ apt.dossier.ref_number }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 truncate mb-1">{{ apt.title }}</h3>
                            
                            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500 mt-2">
                                <div class="flex items-center gap-1.5">
                                    <UserCircleIcon class="w-4 h-4 text-slate-400" />
                                    <span class="font-medium text-slate-700">
                                        {{ apt.dossier?.client?.name || apt.client?.name || 'Client inconnu' }}
                                    </span>
                                </div>
                                <div v-if="apt.location" class="flex items-center gap-1.5">
                                    <MapPinIcon class="w-4 h-4 text-slate-400" />
                                    <span class="truncate max-w-[150px]">{{ apt.location }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center sm:flex-col justify-end gap-2 opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-200 border-t sm:border-t-0 border-slate-50 pt-3 sm:pt-0 mt-3 sm:mt-0">
                            <button @click="openEdit(apt)" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                                <PencilSquareIcon class="w-5 h-5" />
                            </button>
                            <button @click="deleteApt(apt)" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>

                    </div>
                </div>

                <div v-if="appointments.data.length === 0" class="text-center py-20 bg-white rounded-[2rem] border border-dashed border-slate-200">
                    <div class="bg-indigo-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <CalendarDaysIcon class="w-8 h-8 text-indigo-500" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">
                        {{ currentFilter === 'all' ? 'Agenda vide' : (currentFilter === 'upcoming' ? 'Rien à venir' : 'Aucun historique') }}
                    </h3>
                    <p class="text-slate-500 max-w-xs mx-auto mt-1">
                        {{ currentFilter === 'all' ? 'Aucun rendez-vous planifié.' : 'Changez de filtre pour voir d\'autres résultats.' }}
                    </p>
                </div>
            </div>

            <div class="mt-10 flex justify-center" v-if="appointments.data.length > 0">
                 <div class="flex bg-white rounded-xl shadow-sm border border-slate-200 p-1">
                    <Link 
                        v-for="(link, k) in appointments.links" 
                        :key="k" 
                        :href="link.url ?? '#'" 
                        v-html="link.label"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                        :class="link.active 
                            ? 'bg-slate-900 text-white shadow-md' 
                            : 'text-slate-500 hover:bg-slate-50 hover:text-indigo-600'"
                    />
                 </div>
            </div>

        </div>

        <AppointmentModal 
            :show="isModalOpen" 
            :appointment="editingApt" 
            :dossier-id="editingApt?.dossier_id || null"
            @close="isModalOpen = false" 
        />
    </MainLayout>
</template>