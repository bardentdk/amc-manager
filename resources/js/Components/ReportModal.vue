<script setup>
import { watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { SparklesIcon } from '@heroicons/vue/24/solid'; // Pour le bouton IA futur

const props = defineProps({
    show: Boolean,
    dossierId: Number,
    report: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    dossier_id: props.dossierId,
    type: 'legal_meeting',
    report_date: new Date().toISOString().substr(0, 10),
    content_body: '',
    status: 'draft',
});

watch(() => props.report, (val) => {
    if (val) {
        form.type = val.type;
        form.report_date = val.report_date; // Assure-toi que c'est YYYY-MM-DD
        // On récupère le texte depuis le JSON
        form.content_body = val.content?.body || '';
        form.status = val.status;
    } else {
        form.reset();
        form.dossier_id = props.dossierId;
        form.report_date = new Date().toISOString().substr(0, 10);
    }
});

const submit = () => {
    if (props.report) {
        form.put(route('reports.update', props.report.id), { onSuccess: () => emit('close') });
    } else {
        form.dossier_id = props.dossierId;
        form.post(route('reports.store'), { onSuccess: () => emit('close') });
    }
};

const generateWithAI = () => {
    alert("Fonctionnalité IA à venir ! Elle générera un résumé structuré ici.");
};
</script>

<template>
    <TransitionRoot as="template" :show="show">
        <Dialog as="div" class="relative z-50" @close="emit('close')">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-slate-900/75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                        
                        <div class="mt-3 text-center sm:mt-5">
                            <DialogTitle as="h3" class="text-base font-semibold leading-6 text-slate-900">
                                {{ report ? 'Modifier le compte rendu' : 'Rédiger un compte rendu' }}
                            </DialogTitle>
                            
                            <form @submit.prevent="submit" class="mt-4 text-left space-y-4">
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Type</label>
                                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm sm:text-sm">
                                            <option value="legal_meeting">RDV Avocat</option>
                                            <option value="court_hearing">Audience</option>
                                            <option value="closing">Closing</option>
                                            <option value="phone_call">Appel téléphonique</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Date</label>
                                        <input type="date" v-model="form.report_date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm py-2" required />
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-sm font-medium text-slate-700">Contenu</label>
                                        <button @click.prevent="generateWithAI" type="button" class="text-xs flex items-center text-indigo-600 hover:text-indigo-800 font-medium">
                                            <SparklesIcon class="w-3 h-3 mr-1" /> Assistant IA
                                        </button>
                                    </div>
                                    <textarea 
                                        v-model="form.content_body" 
                                        rows="12" 
                                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-slate-700"
                                        placeholder="Saisissez vos notes ici..."
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Statut</label>
                                    <div class="mt-2 flex space-x-4">
                                        <label class="inline-flex items-center">
                                            <input type="radio" v-model="form.status" value="draft" class="text-indigo-600 border-slate-300">
                                            <span class="ml-2 text-sm text-slate-700">Brouillon</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="radio" v-model="form.status" value="finalized" class="text-indigo-600 border-slate-300">
                                            <span class="ml-2 text-sm text-slate-700 font-semibold">Finalisé</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2" :disabled="form.processing">Enregistrer</button>
                                    <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:col-start-1 sm:mt-0" @click="emit('close')">Annuler</button>
                                </div>

                            </form>
                        </div>
                    </DialogPanel>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>