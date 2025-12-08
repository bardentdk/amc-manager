<script setup>
import { ref, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    client: Object, // Si null = Création, sinon = Edition
});

const emit = defineEmits(['close']);

// Initialisation du formulaire
const form = useForm({
    name: '',
    type: 'individual',
    email: '',
    phone: '',
    address: '',
    notes: '',
});

// Dès que la prop "client" change, on remplit le form (ou on le vide)
watch(() => props.client, (newVal) => {
    if (newVal) {
        form.name = newVal.name;
        form.type = newVal.type;
        form.email = newVal.email;
        form.phone = newVal.phone;
        form.address = newVal.address;
        form.notes = newVal.notes;
    } else {
        form.reset();
        form.type = 'individual'; // Valeur par défaut
    }
}, { immediate: true });

const submit = () => {
    if (props.client) {
        // Mode Édition
        form.put(route('clients.update', props.client.id), {
            onSuccess: () => emit('close'),
        });
    } else {
        // Mode Création
        form.post(route('clients.store'), {
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="emit('close')">
            <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-slate-900/75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <TransitionChild as="template" enter="transform transition ease-in-out duration-300 sm:duration-500" enter-from="translate-x-full" enter-to="translate-x-0" leave="transform transition ease-in-out duration-300 sm:duration-500" leave-from="translate-x-0" leave-to="translate-x-full">
                            <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                <form @submit.prevent="submit" class="flex h-full flex-col divide-y divide-slate-200 bg-white shadow-xl">
                                    <div class="flex min-h-0 flex-1 flex-col overflow-y-scroll py-6">
                                        <div class="px-4 sm:px-6">
                                            <div class="flex items-start justify-between">
                                                <DialogTitle class="text-base font-semibold leading-6 text-slate-900">
                                                    {{ client ? 'Modifier le client' : 'Nouveau client' }}
                                                </DialogTitle>
                                                <div class="ml-3 flex h-7 items-center">
                                                    <button type="button" class="rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none" @click="emit('close')">
                                                        <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative mt-6 flex-1 px-4 sm:px-6 space-y-5">
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Type de client</label>
                                                <div class="mt-2 flex space-x-4">
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" v-model="form.type" value="individual" class="text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                                        <span class="ml-2 text-sm text-slate-700">Particulier</span>
                                                    </label>
                                                    <label class="inline-flex items-center">
                                                        <input type="radio" v-model="form.type" value="company" class="text-indigo-600 focus:ring-indigo-500 border-slate-300">
                                                        <span class="ml-2 text-sm text-slate-700">Entreprise</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Nom / Raison Sociale</label>
                                                <input type="text" v-model="form.name" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" required />
                                                <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Email</label>
                                                <input type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Téléphone</label>
                                                <input type="text" v-model="form.phone" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" />
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Adresse</label>
                                                <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2"></textarea>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Notes internes</label>
                                                <textarea v-model="form.notes" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 bg-slate-50"></textarea>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="flex flex-shrink-0 justify-end px-4 py-4">
                                        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50" @click="emit('close')">Annuler</button>
                                        <button type="submit" class="ml-4 inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" :disabled="form.processing">
                                            {{ client ? 'Enregistrer les modifications' : 'Créer le client' }}
                                        </button>
                                    </div>
                                </form>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>