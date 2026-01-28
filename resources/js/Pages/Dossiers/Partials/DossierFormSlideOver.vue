<script setup>
import { watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    clients: Array, // Liste ID/Nom
    lawyers: Array, // Liste ID/Nom
    dossier: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    client_id: '',
    lawyer_id: '',
    subject: '',
    ref_number: '', // Laisser vide pour auto-génération
    type: 'Litige',
    status: 'open',
    description: '',
});

// Reset ou Remplissage
watch(() => props.dossier, (newVal) => {
    if (newVal) {
        form.client_id = newVal.client_id;
        form.lawyer_id = newVal.lawyer_id;
        form.subject = newVal.subject;
        form.ref_number = newVal.ref_number;
        form.type = newVal.type;
        form.status = newVal.status;
        form.description = newVal.description;
    } else {
        form.reset();
        form.status = 'open';
        form.type = 'Litige';
    }
}, { immediate: true });

const submit = () => {
    if (props.dossier) {
        form.put(route('dossiers.update', props.dossier.id), { onSuccess: () => emit('close') });
    } else {
        form.post(route('dossiers.store'), { onSuccess: () => emit('close') });
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
                                                    {{ dossier ? 'Modifier le dossier' : 'Nouveau dossier' }}
                                                </DialogTitle>
                                                <button type="button" class="rounded-md bg-white text-slate-400 hover:text-slate-500" @click="emit('close')">
                                                    <XMarkIcon class="h-6 w-6" />
                                                </button>
                                            </div>
                                        </div>
                                        <div class="relative mt-6 flex-1 px-4 sm:px-6 space-y-5">
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Client *</label>
                                                <select v-model="form.client_id" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                    <option value="" disabled>Choisir un client...</option>
                                                    <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Objet du dossier *</label>
                                                <input type="text" v-model="form.subject" placeholder="Ex: Divorce M. X, Recouvrement Y..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" required />
                                            </div>

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Type</label>
                                                    <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm sm:text-sm">
                                                        <option>Litige</option>
                                                        <option>Conseil</option>
                                                        <option>Rédaction d'acte</option>
                                                        <option>Divorce</option>
                                                        <option>Pénal</option>
                                                        <option>Autre</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Statut</label>
                                                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm sm:text-sm">
                                                        <option value="open">Ouvert</option>
                                                        <option value="in_progress">En cours</option>
                                                        <option value="waiting">En attente</option>
                                                        <option value="closed">Clôturé</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <div class="sm:col-span-2">
                                                    <label for="lawyer" class="block text-sm font-medium leading-6 text-slate-900">Avocat référent</label>
                                                    <div class="mt-2">
                                                        <select 
                                                            id="lawyer" 
                                                            v-model="form.lawyer_id" 
                                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                                        >
                                                            <option :value="null">Aucun</option>
                                                            <option v-for="lawyer in lawyers" :key="lawyer.id" :value="lawyer.id">
                                                                {{ lawyer.name }}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-slate-700">Description / Notes</label>
                                                <textarea v-model="form.description" rows="4" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2"></textarea>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="flex flex-shrink-0 justify-end px-4 py-4">
                                        <button type="button" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50" @click="emit('close')">Annuler</button>
                                        <button type="submit" class="ml-4 inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" :disabled="form.processing">
                                            {{ dossier ? 'Mettre à jour' : 'Créer le dossier' }}
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