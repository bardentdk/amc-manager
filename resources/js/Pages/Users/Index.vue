<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { 
    UserPlusIcon, 
    TrashIcon, 
    ShieldCheckIcon,
    UserIcon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue'; // Assure-toi d'avoir un composant Modal générique, sinon utilise celui de Breeze/Jetstream ou refais-en un simple

const props = defineProps({
    users: Array,
    availableRoles: Array
});

const isModalOpen = ref(false);

const form = useForm({
    name: '',
    email: '',
    role: 'lawyer', // Valeur par défaut
});

const submit = () => {
    form.post(route('users.store'), {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
        },
    });
};

const deleteUser = (user) => {
    if (confirm('Voulez-vous vraiment supprimer cet utilisateur ? Cette action est irréversible.')) {
        router.delete(route('users.destroy', user.id));
    }
};

// Map des couleurs pour les rôles
const roleColors = {
    admin: 'bg-purple-50 text-purple-700 ring-purple-600/20',
    lawyer: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
    assistant: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
};

const formatRole = (role) => {
    const map = { admin: 'Admin', lawyer: 'Avocat', assistant: 'Assistant' };
    return map[role] || role;
};
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <MainLayout>
        <template #header>Équipe & Accès</template>

        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Membres du cabinet</h2>
                <p class="text-sm text-slate-500">Gérez les accès et les rôles de vos collaborateurs.</p>
            </div>
            <button @click="isModalOpen = true" class="flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-5 rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <UserPlusIcon class="h-5 w-5" />
                Nouveau Membre
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <ul role="list" class="divide-y divide-slate-100">
                <li v-for="user in users" :key="user.id" class="flex items-center justify-between gap-x-6 py-5 px-6 hover:bg-slate-50 transition-colors">
                    <div class="flex min-w-0 gap-x-4">
                        <div class="h-12 w-12 flex-none rounded-full bg-slate-100 flex items-center justify-center text-lg font-bold text-slate-500">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-auto">
                            <p class="text-sm font-semibold leading-6 text-slate-900">{{ user.name }}</p>
                            <p class="mt-1 truncate text-xs leading-5 text-slate-500">{{ user.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div v-for="role in user.roles" :key="role.id">
                            <span :class="[roleColors[role.name] || 'bg-slate-50 text-slate-600 ring-slate-500/10', 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset']">
                                {{ formatRole(role.name) }}
                            </span>
                        </div>
                        
                        <button v-if="$page.props.auth.user.id !== user.id" @click="deleteUser(user)" class="text-slate-400 hover:text-rose-600 transition-colors p-2 rounded-lg hover:bg-rose-50">
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <Modal :show="isModalOpen" @close="isModalOpen = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Inviter un collaborateur</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nom complet</label>
                        <input v-model="form.name" type="text" class="py-3 px-3 mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ex: Jean Dupont" required />
                        <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Adresse Email</label>
                        <input v-model="form.email" type="email" class="py-3 px-3 mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="jean@nexa.app" required />
                        <div v-if="form.errors.email" class="text-rose-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Rôle</label>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label v-for="role in availableRoles" :key="role" class="cursor-pointer relative">
                                <input type="radio" v-model="form.role" :value="role" class="peer sr-only">
                                <div class="rounded-xl border border-slate-200 p-4 hover:bg-slate-50 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:ring-1 peer-checked:ring-indigo-600 transition-all text-center">
                                    <span class="block text-sm font-semibold text-slate-900 capitalize">{{ formatRole(role) }}</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="isModalOpen = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-500 disabled:opacity-50">
                            {{ form.processing ? 'Envoi...' : 'Créer et Envoyer Email' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </MainLayout>
</template>