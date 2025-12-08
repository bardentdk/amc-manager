<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import ClientFormSlideOver from './Partials/ClientFormSlideOver.vue';
import { 
    MagnifyingGlassIcon, 
    PlusIcon, 
    PencilSquareIcon, 
    TrashIcon,
    BuildingOfficeIcon,
    UserIcon
} from '@heroicons/vue/24/outline';
import debounce from 'lodash/debounce'; // Si lodash n'est pas dispo, on fera une fonction simple

const props = defineProps({
    clients: Object,
    filters: Object,
});

// --- Gestion de la recherche ---
const search = ref(props.filters.search || '');

// On observe la recherche pour recharger la page (avec un délai pour ne pas spammer)
watch(search, debounce((value) => {
    router.get(route('clients.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));


// --- Gestion du SlideOver (Formulaire) ---
const isSlideOverOpen = ref(false);
const editingClient = ref(null);

const openCreate = () => {
    editingClient.value = null;
    isSlideOverOpen.value = true;
};

const openEdit = (client) => {
    editingClient.value = client;
    isSlideOverOpen.value = true;
};

const closeSlideOver = () => {
    isSlideOverOpen.value = false;
    editingClient.value = null;
};

// --- Suppression ---
const deleteClient = (client) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce client et tous ses dossiers ?')) {
        router.delete(route('clients.destroy', client.id));
    }
};
</script>

<template>
    <Head title="Clients" />

    <MainLayout>
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Clients</h1>
                <p class="mt-2 text-sm text-slate-700">La liste complète de tes clients (Particuliers & Entreprises).</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                 <div class="relative rounded-md shadow-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <MagnifyingGlassIcon class="h-5 w-5 text-slate-400" aria-hidden="true" />
                    </div>
                    <input 
                        type="text" 
                        v-model="search"
                        class="block w-full rounded-md border-0 py-1.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" 
                        placeholder="Rechercher..." 
                    />
                </div>
                
                <button 
                    @click="openCreate"
                    type="button" 
                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    <div class="flex items-center">
                        <PlusIcon class="h-5 w-5 mr-1" />
                        Nouveau Client
                    </div>
                </button>
            </div>
        </div>

        <div class="flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg bg-white">
                        <table class="min-w-full divide-y divide-slate-300">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Nom</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Contact</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Type</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Ajouté le</th>
                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="client in clients.data" :key="client.id" class="hover:bg-slate-50 transition">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs uppercase">
                                                {{ client.name.substring(0, 2) }}
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-slate-900">{{ client.name }}</div>
                                                <div class="text-slate-500 text-xs truncate max-w-[200px]">{{ client.address }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <div v-if="client.email" class="text-slate-900">{{ client.email }}</div>
                                        <div v-if="client.phone" class="text-slate-500 text-xs">{{ client.phone }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" 
                                              :class="client.type === 'company' ? 'bg-purple-50 text-purple-700 ring-purple-700/10' : 'bg-blue-50 text-blue-700 ring-blue-700/10'">
                                            <component :is="client.type === 'company' ? BuildingOfficeIcon : UserIcon" class="w-3 h-3 mr-1" />
                                            {{ client.type === 'company' ? 'Entreprise' : 'Particulier' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        {{ new Date(client.created_at).toLocaleDateString('fr-FR') }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <button @click="openEdit(client)" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            <PencilSquareIcon class="w-5 h-5" />
                                        </button>
                                        <button @click="deleteClient(client)" class="text-red-400 hover:text-red-600">
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="clients.data.length === 0">
                                    <td colspan="5" class="py-10 text-center text-slate-500 text-sm">
                                        Aucun client trouvé.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 flex items-center justify-between" v-if="clients.data.length > 0">
                        <span class="text-sm text-slate-700">
                            Affichage de {{ clients.from }} à {{ clients.to }} sur {{ clients.total }} résultats
                        </span>
                        <div class="flex gap-2">
                            <component 
                                :is="link.url ? 'Link' : 'span'"
                                v-for="link in clients.links" 
                                :key="link.label"
                                :href="link.url"
                                class="px-3 py-1 border rounded text-sm"
                                :class="{ 'bg-indigo-600 text-white': link.active, 'text-slate-500': !link.url }"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ClientFormSlideOver 
            :show="isSlideOverOpen" 
            :client="editingClient" 
            @close="closeSlideOver" 
        />
        
    </MainLayout>
</template>

<script>
    import { Link } from '@inertiajs/vue3';
    export default { components: { Link } }
</script>