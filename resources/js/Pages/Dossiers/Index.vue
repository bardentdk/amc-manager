<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import DossierFormSlideOver from './Partials/DossierFormSlideOver.vue';
import { MagnifyingGlassIcon, PlusIcon, FolderIcon } from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce';

const props = defineProps({
    dossiers: Object,
    clients: Array,
    lawyers: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');

// Filtrage
const updateFilters = debounce(() => {
    router.get(route('dossiers.index'), { search: search.value, status: statusFilter.value }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, statusFilter], updateFilters);

// SlideOver Logic
const isSlideOverOpen = ref(false);

const statusColors = {
    open: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    in_progress: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
    waiting: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    closed: 'bg-slate-50 text-slate-600 ring-slate-500/10',
};

const statusLabels = {
    open: 'Ouvert', in_progress: 'En cours', waiting: 'En attente', closed: 'Clôturé'
};
</script>

<template>
    <Head title="Dossiers" />
    <MainLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dossiers</h1>
                <p class="mt-2 text-sm text-slate-700">Gestion de tous les dossiers juridiques.</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <select v-model="statusFilter" class="rounded-md border-0 py-1.5 pl-3 pr-8 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <option value="all">Tous les statuts</option>
                    <option value="open">Ouvert</option>
                    <option value="in_progress">En cours</option>
                    <option value="closed">Clôturé</option>
                </select>

                <div class="relative rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" />
                    </div>
                    <input type="text" v-model="search" class="block w-full rounded-md border-0 py-1.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-600 sm:text-sm" placeholder="Ref, Client, Sujet..." />
                </div>
                
                <button @click="isSlideOverOpen = true" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <div class="flex items-center"><PlusIcon class="h-5 w-5 mr-1" /> Dossier</div>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="dossier in dossiers.data" :key="dossier.id" :href="route('dossiers.show', dossier.id)" class="group relative flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm hover:border-indigo-300 hover:shadow-md transition">
                <div class="p-5 flex-1">
                    <div class="flex justify-between items-start">
                        <span :class="[statusColors[dossier.status], 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset']">
                            {{ statusLabels[dossier.status] || dossier.status }}
                        </span>
                        <span class="text-xs text-slate-400 font-mono">{{ dossier.ref_number }}</span>
                    </div>
                    <h3 class="mt-3 text-lg font-semibold text-slate-900 group-hover:text-indigo-600 truncate">
                        {{ dossier.subject }}
                    </h3>
                    <!-- <p class="text-sm text-slate-500 mt-1">{{ dossier.client.name }}</p> -->
                    <p class="text-sm text-slate-500 mt-1">{{ dossier.client?.name || 'Client inconnu' }}</p>
                    <div class="mt-4 flex items-center text-xs text-slate-400">
                        <FolderIcon class="mr-1.5 h-4 w-4 flex-shrink-0" />
                        {{ dossier.type }}
                    </div>
                </div>
                <div class="bg-slate-50 px-5 py-3 border-t border-slate-100 flex justify-between items-center">
                    <div class="text-xs text-slate-500">
                        Avocat: 
                        <!-- <span class="font-medium text-slate-700">{{ dossier.lawyer ? dossier.lawyer.name : 'Non assigné' }}</span> -->
                        <span class="font-medium text-slate-700">{{ dossier.lawyer?.name || 'Non assigné' }}</span>
                    </div>
                </div>
            </Link>
        </div>

        <div v-if="dossiers.data.length === 0" class="text-center py-12">
            <FolderIcon class="mx-auto h-12 w-12 text-slate-300" />
            <h3 class="mt-2 text-sm font-semibold text-slate-900">Aucun dossier</h3>
            <p class="mt-1 text-sm text-slate-500">Créez votre premier dossier pour commencer.</p>
        </div>

        <DossierFormSlideOver :show="isSlideOverOpen" :clients="clients" :lawyers="lawyers" @close="isSlideOverOpen = false" />
    </MainLayout>
</template>