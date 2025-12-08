<script setup>
import { watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    dossierId: Number,
    appointment: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    dossier_id: props.dossierId,
    title: '',
    type: 'legal',
    start_time: '', // Format datetime-local : YYYY-MM-DDTHH:MM
    end_time: '',
    location: '',
    notes: '',
    status: 'scheduled',
});

watch(() => props.appointment, (val) => {
    if (val) {
        form.title = val.title;
        form.type = val.type;
        // Formatage pour l'input datetime-local (coupe les secondes)
        form.start_time = val.start_time ? val.start_time.substring(0, 16) : '';
        form.end_time = val.end_time ? val.end_time.substring(0, 16) : '';
        form.location = val.location;
        form.notes = val.notes;
        form.status = val.status;
    } else {
        form.reset();
        form.dossier_id = props.dossierId;
        // Par défaut : Demain à 10h00
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(10, 0, 0, 0);
        form.start_time = tomorrow.toISOString().substring(0, 16);
    }
});

const submit = () => {
    if (props.appointment) {
        form.put(route('appointments.update', props.appointment.id), { onSuccess: () => emit('close') });
    } else {
        form.dossier_id = props.dossierId;
        form.post(route('appointments.store'), { onSuccess: () => emit('close') });
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
                    <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md sm:p-6">
                        <div class="mt-3 text-center sm:mt-5">
                            <DialogTitle as="h3" class="text-base font-semibold leading-6 text-slate-900">
                                {{ appointment ? 'Modifier le rendez-vous' : 'Planifier un rendez-vous' }}
                            </DialogTitle>
                            <form @submit.prevent="submit" class="mt-4 text-left space-y-4">
                                
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Titre</label>
                                    <input type="text" v-model="form.title" placeholder="Ex: Entretien client, Audience..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" required />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Type</label>
                                    <select v-model="form.type" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm sm:text-sm">
                                        <option value="legal">Rendez-vous Avocat</option>
                                        <option value="closing">Closing / Signature</option>
                                        <option value="phone">Appel Téléphonique</option>
                                        <option value="other">Autre</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Début</label>
                                        <input type="datetime-local" v-model="form.start_time" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm py-2" required />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Fin (Estimée)</label>
                                        <input type="datetime-local" v-model="form.end_time" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm py-2" />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Lieu</label>
                                    <input type="text" v-model="form.location" placeholder="Cabinet, Tribunal, Visio..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Notes préparatoires</label>
                                    <textarea v-model="form.notes" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm sm:text-sm py-2"></textarea>
                                </div>

                                <div v-if="appointment">
                                    <label class="block text-sm font-medium text-slate-700">Statut</label>
                                    <select v-model="form.status" class="mt-1 block w-full rounded-md border-slate-300 py-2 shadow-sm sm:text-sm">
                                        <option value="scheduled">Planifié</option>
                                        <option value="completed">Réalisé</option>
                                        <option value="cancelled">Annulé</option>
                                        <option value="postponed">Reporté</option>
                                    </select>
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