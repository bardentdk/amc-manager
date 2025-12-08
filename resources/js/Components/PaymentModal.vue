<script setup>
import { watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    dossierId: Number,
    payment: Object, // Si présent = Mode Édition
});

const emit = defineEmits(['close']);

const form = useForm({
    dossier_id: props.dossierId,
    amount: '',
    payment_date: new Date().toISOString().substr(0, 10), // Date du jour par défaut
    method: 'Virement',
    reference: '',
    status: 'paid',
});

// Observation pour pré-remplir en cas d'édition
watch(() => props.payment, (val) => {
    if (val) {
        form.amount = val.amount;
        form.payment_date = val.payment_date; // Assure-toi que le format est YYYY-MM-DD
        form.method = val.method;
        form.reference = val.reference;
        form.status = val.status;
    } else {
        form.reset();
        form.dossier_id = props.dossierId;
        form.payment_date = new Date().toISOString().substr(0, 10);
    }
});

const submit = () => {
    if (props.payment) {
        form.put(route('payments.update', props.payment.id), { onSuccess: () => emit('close') });
    } else {
        form.dossier_id = props.dossierId;
        form.post(route('payments.store'), { onSuccess: () => emit('close') });
    }
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
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-sm sm:p-6">
                            
                            <div class="mt-3 text-center sm:mt-5">
                                <DialogTitle as="h3" class="text-base font-semibold leading-6 text-slate-900">
                                    {{ payment ? 'Modifier le règlement' : 'Nouveau règlement' }}
                                </DialogTitle>
                                
                                <form @submit.prevent="submit" class="mt-4 text-left space-y-4">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Montant (€)</label>
                                        <div class="relative mt-1 rounded-md shadow-sm">
                                            <input type="number" step="0.01" v-model="form.amount" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-12 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" placeholder="0.00" required />
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                <span class="text-slate-500 sm:text-sm">EUR</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Date</label>
                                        <input type="date" v-model="form.payment_date" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Moyen de paiement</label>
                                        <select v-model="form.method" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option>Virement</option>
                                            <option>Carte Bancaire</option>
                                            <option>Espèces</option>
                                            <option>Chèque</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Référence (Optionnel)</label>
                                        <input type="text" v-model="form.reference" placeholder="N° Fac, N° Chèque..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Statut</label>
                                        <select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="paid">Payé (Encaissé)</option>
                                            <option value="pending">En attente</option>
                                            <option value="partial">Partiel</option>
                                        </select>
                                    </div>

                                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:col-start-2" :disabled="form.processing">Enregistrer</button>
                                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:col-start-1 sm:mt-0" @click="emit('close')">Annuler</button>
                                    </div>

                                </form>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>