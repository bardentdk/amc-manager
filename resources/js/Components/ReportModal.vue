<script setup>
import { watch, ref } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { SparklesIcon, ArrowPathIcon } from '@heroicons/vue/24/solid'; // Pour le bouton IA futur

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
    content:'',
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

// const generateWithAI = () => {
//     alert("Fonctionnalité IA à venir ! Elle générera un résumé structuré ici.");
// };
// Variables pour l'IA
const roughNotes = ref('');
const isGenerating = ref(false);

const generateWithAI = async () => {
    if (!roughNotes.value) {
        alert("Veuillez d'abord saisir quelques notes brutes.");
        return;
    }

    isGenerating.value = true;
    try {
        const response = await axios.post(route('reports.generateAi'), {
            dossier_id: props.dossierId,
            type: form.type,
            notes: roughNotes.value
        });

        if (response.data.success) {
            // On injecte le texte généré dans le champ principal du compte rendu
            form.content = response.data.content;
            roughNotes.value = ''; // Optionnel : vider les notes brutes
        }
    } catch (error) {
        alert("Une erreur est survenue lors de la génération IA.");
        console.error(error);
    } finally {
        isGenerating.value = false;
    }
};
</script>
<template>
    <div class="space-y-4 mx-5 my-5">
        
        <div>
            <label class="block text-sm font-medium text-slate-700">Type de compte rendu</label>
            <select v-model="form.type" class="p-3 mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="legal_meeting">RDV Avocat</option>
                <option value="court_hearing">Audience</option>
                <option value="closing">Closing</option>
                <option value="phone_call">Appel téléphonique</option>
            </select>
        </div>

        <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-bold text-indigo-900 flex items-center gap-2">
                    <SparklesIcon class="h-5 w-5 text-indigo-600" />
                    Assistant IA (Notes rapides)
                </label>
            </div>
            <textarea 
                v-model="roughNotes" 
                rows="2" 
                placeholder="Ex: Client en retard. Accord trouvé pour 50k€. Prochaine audience le 12 mai."
                class="px-2 py-2 block w-full rounded-md border-indigo-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm placeholder:text-slate-400 text-slate-700"
            ></textarea>
            
            <div class="mt-3 flex justify-end">
                <button 
                    type="button" 
                    @click="generateWithAI" 
                    :disabled="isGenerating || !roughNotes"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition-all"
                >
                    <ArrowPathIcon v-if="isGenerating" class="h-4 w-4 animate-spin" />
                    <SparklesIcon v-else class="h-4 w-4" />
                    {{ isGenerating ? 'Génération en cours...' : 'Générer la rédaction' }}
                </button>
            </div>
        </div>

        <div>
            <label class=" block text-sm font-medium text-slate-700">Contenu du Compte Rendu final</label>
            <textarea 
                v-model="form.content" 
                rows="8" 
                required
                class="px-2 mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="Le compte rendu généré apparaîtra ici..."
            ></textarea>
        </div>

    </div>
    
    </template>
<!-- <template>
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
</template> -->