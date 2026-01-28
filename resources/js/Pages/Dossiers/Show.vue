<script setup>
    import { ref } from 'vue';
    import { Head, Link } from '@inertiajs/vue3';
    import MainLayout from '@/Layouts/MainLayout.vue';
    import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue';
    import PaymentModal from '@/Components/PaymentModal.vue'; // <--- Nouveau
    import AppointmentModal from '@/Components/AppointmentModal.vue';
    import { router } from '@inertiajs/vue3';
    import { computed } from 'vue'; // Pour le total
    import ReportModal from '@/Components/ReportModal.vue';
    import DocumentsTab from './Partials/DocumentsTab.vue';
    import ActivityFeed from '@/Components/ActivityFeed.vue';
    import { 
        BriefcaseIcon, CalendarIcon, BanknotesIcon, DocumentTextIcon, PaperClipIcon,
        PencilIcon, ArrowLeftIcon, PlusIcon, TrashIcon, PencilSquareIcon, ClockIcon, MapPinIcon, UserCircleIcon, ArrowDownTrayIcon, PaperAirplaneIcon
    } from '@heroicons/vue/24/outline';
    import DossierFormSlideOver from './Partials/DossierFormSlideOver.vue';

    const props = defineProps({
        dossier: Object,
        lawyers: Array,
    });

    const isEditOpen = ref(false);

    const categories = [
        { name: 'Informations', icon: BriefcaseIcon },
        { name: 'Règlements', icon: BanknotesIcon },
        { name: 'Rendez-vous', icon: CalendarIcon },
        { name: 'Comptes Rendus', icon: DocumentTextIcon },
        { name: 'Documents', icon: PaperClipIcon },
        { name: 'Historique', icon: ClockIcon }
    ];

    const statusColors = {
        open: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-indigo-100 text-indigo-700',
        waiting: 'bg-amber-100 text-amber-700',
        closed: 'bg-slate-100 text-slate-600',
    };

    // On utilise Inertia "reload" pour rafraichir les données si on édite via le slideover
    const refreshDossier = () => {
        isEditOpen.value = false;
        window.location.reload(); // Simple refresh pour l'instant
    };

    // --- Logique Paiements ---
    const isPaymentModalOpen = ref(false);
    const editingPayment = ref(null);

    const openPaymentCreate = () => {
        editingPayment.value = null;
        isPaymentModalOpen.value = true;
    };

    const openPaymentEdit = (payment) => {
        editingPayment.value = payment;
        isPaymentModalOpen.value = true;
    };

    const deletePayment = (payment) => {
        if (confirm('Supprimer ce règlement ?')) {
            router.delete(route('payments.destroy', payment.id));
        }
    };

    // Calcul du total encaissé (statut 'paid' ou 'partial')
    const totalPaid = computed(() => {
        return props.dossier.payments
            .filter(p => p.status === 'paid' || p.status === 'partial')
            .reduce((sum, p) => sum + parseFloat(p.amount), 0);
    });

    // Formatage monétaire
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(value);
    };

    // Helpers Date
    const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');

    // --- Logique RDV ---
    const isAptModalOpen = ref(false);
    const editingApt = ref(null);

    const openAptCreate = () => {
        editingApt.value = null;
        isAptModalOpen.value = true;
    };

    const openAptEdit = (apt) => {
        editingApt.value = apt;
        isAptModalOpen.value = true;
    };

    const deleteApt = (apt) => {
        if (confirm('Supprimer ce rendez-vous ?')) {
            router.delete(route('appointments.destroy', apt.id));
        }
    };

    const typeColors = {
        legal: 'text-indigo-600 bg-indigo-50',
        closing: 'text-emerald-600 bg-emerald-50',
        phone: 'text-amber-600 bg-amber-50',
        other: 'text-slate-600 bg-slate-50',
    };

    const formatDateTime = (date) => {
        return new Intl.DateTimeFormat('fr-FR', { 
            day: 'numeric', month: 'long', hour: '2-digit', minute:'2-digit' 
        }).format(new Date(date));
    };

    // --- Logique Rapports ---
    const isReportModalOpen = ref(false);
    const editingReport = ref(null);

    const openReportCreate = () => {
        editingReport.value = null;
        isReportModalOpen.value = true;
    };

    const openReportEdit = (report) => {
        editingReport.value = report;
        isReportModalOpen.value = true;
    };

    const deleteReport = (report) => {
        if (confirm('Supprimer ce compte rendu ?')) {
            router.delete(route('reports.destroy', report.id));
        }
    };

    const reportTypeLabels = {
        legal_meeting: 'RDV Avocat',
        court_hearing: 'Audience',
        closing: 'Closing',
        phone_call: 'Appel',
    };

    // Fonction pour déclencher l'envoi email
    const sendReportByEmail = (report) => {
        if (confirm(`Envoyer ce rapport par email au client (${props.dossier.client.email || 'Pas d\'email'}) ?`)) {
            router.post(route('reports.email', report.id), {}, {
                preserveScroll: true,
                onSuccess: () => alert('Email envoyé !'), // Tu pourras utiliser un Toast notification plus tard
            });
        }
    };
</script>

<template>
    <Head :title="dossier.ref_number" />

    <MainLayout>
        <div class="mb-6">
            <nav class="sm:hidden" aria-label="Back">
                <Link :href="route('dossiers.index')" class="flex items-center text-sm font-medium text-slate-500 hover:text-slate-700">
                    <ArrowLeftIcon class="-ml-1 mr-1 h-5 w-5" aria-hidden="true" />
                    Retour aux dossiers
                </Link>
            </nav>
            <nav class="hidden sm:flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-4">
                    <li>
                        <div class="flex">
                            <Link :href="route('dossiers.index')" class="text-sm font-medium text-slate-500 hover:text-slate-700">Dossiers</Link>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="h-5 w-5 flex-shrink-0 text-slate-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" /></svg>
                            <span class="ml-4 text-sm font-medium text-slate-500">{{ dossier.ref_number }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="md:flex md:items-center md:justify-between bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-6">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        {{ dossier.subject }}
                    </h2>
                    <span :class="[statusColors[dossier.status], 'rounded-full px-3 py-1 text-xs font-semibold']">
                        {{ dossier.status }}
                    </span>
                </div>
                <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                    <div class="mt-2 flex items-center text-sm text-slate-500">
                        <BriefcaseIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-slate-400" aria-hidden="true" />
                        {{ dossier.type }}
                    </div>
                    <div class="mt-2 flex items-center text-sm text-slate-500">
                        <span class="font-medium text-slate-900 mr-1">Client:</span> {{ dossier.client.name }}
                    </div>
                </div>
            </div>
            <div class="mt-4 flex md:ml-4 md:mt-0">
                <button @click="isEditOpen = true" type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                    <PencilIcon class="-ml-0.5 mr-1.5 h-5 w-5 text-slate-400" aria-hidden="true" />
                    Modifier
                </button>
            </div>
        </div>

        <TabGroup>
            <TabList class="flex space-x-1 rounded-xl bg-slate-200/50 p-1 mb-6">
                <Tab v-for="category in categories" as="template" :key="category.name" v-slot="{ selected }">
                    <button
                        :class="[
                            'w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-all focus:outline-none',
                            'flex items-center justify-center gap-2',
                            selected
                                ? 'bg-white text-indigo-700 shadow'
                                : 'text-slate-600 hover:bg-white/[0.12] hover:text-slate-800',
                        ]"
                    >
                        <component :is="category.icon" class="h-4 w-4" />
                        {{ category.name }}
                    </button>
                </Tab>
            </TabList>

            <TabPanels>
                
                <TabPanel class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 focus:outline-none">
                    <h3 class="text-lg font-medium leading-6 text-slate-900 mb-4">Détails du dossier</h3>
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-slate-500">Description</dt>
                            <dd class="mt-1 text-sm text-slate-900 whitespace-pre-wrap">{{ dossier.description || 'Aucune description.' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Avocat référent</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ dossier.lawyer ? dossier.lawyer.name : 'Non attribué' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Date d'ouverture</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ new Date(dossier.created_at).toLocaleDateString() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-slate-500">Coordonnées Client</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                {{ dossier.client.email }} <br>
                                {{ dossier.client.phone }}
                            </dd>
                        </div>
                    </dl>
                </TabPanel>

                <TabPanel class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 focus:outline-none overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-slate-900">Suivi des paiements</h3>
                            <p class="mt-1 text-sm text-slate-500">Total encaissé : <span class="font-bold text-emerald-600">{{ formatCurrency(totalPaid) }}</span></p>
                        </div>
                        <button @click="openPaymentCreate" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                            Nouveau règlement
                        </button>
                    </div>

                    <ul role="list" class="divide-y divide-slate-100">
                        <li v-for="payment in dossier.payments" :key="payment.id" class="flex items-center justify-between gap-x-6 px-6 py-5 hover:bg-slate-50 transition">
                            <div class="min-w-0">
                                <div class="flex items-start gap-x-3">
                                    <p class="text-sm font-semibold leading-6 text-slate-900">{{ formatCurrency(payment.amount) }}</p>
                                    <span :class="[
                                        payment.status === 'paid' ? 'text-emerald-700 bg-emerald-50 ring-emerald-600/20' : 
                                        payment.status === 'pending' ? 'text-amber-700 bg-amber-50 ring-amber-600/20' : 'text-slate-600 bg-slate-50 ring-slate-500/10',
                                        'rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset'
                                    ]">
                                        {{ payment.status === 'paid' ? 'Payé' : (payment.status === 'pending' ? 'En attente' : 'Partiel') }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-slate-500">
                                    <p class="whitespace-nowrap">Le {{ formatDate(payment.payment_date) }}</p>
                                    <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current"><circle cx="1" cy="1" r="1" /></svg>
                                    <p class="truncate">{{ payment.method }} <span v-if="payment.reference">({{ payment.reference }})</span></p>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-4">
                                <button @click="openPaymentEdit(payment)" class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:block">Modifier</button>
                                <button @click="deletePayment(payment)" class="text-red-400 hover:text-red-600 p-2">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </li>
                        <li v-if="dossier.payments.length === 0" class="px-6 py-12 text-center text-slate-500 text-sm">
                            Aucun règlement enregistré pour ce dossier.
                        </li>
                    </ul>

                    <PaymentModal 
                        :show="isPaymentModalOpen" 
                        :dossier-id="dossier.id" 
                        :payment="editingPayment" 
                        @close="isPaymentModalOpen = false" 
                    />
                </TabPanel>

                <TabPanel class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 focus:outline-none overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-slate-900">Agenda du dossier</h3>
                            <p class="mt-1 text-sm text-slate-500">Prochains événements</p>
                        </div>
                        <button @click="openAptCreate" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                            Planifier RDV
                        </button>
                    </div>

                    <ul role="list" class="divide-y divide-slate-100">
                        <li v-for="apt in dossier.appointments" :key="apt.id" class="flex items-center justify-between gap-x-6 px-6 py-5 hover:bg-slate-50 transition">
                            <div class="min-w-0">
                                <div class="flex items-start gap-x-3">
                                    <p class="text-sm font-semibold leading-6 text-slate-900">{{ apt.title }}</p>
                                    <span :class="[typeColors[apt.type] || typeColors.other, 'rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-slate-500/10']">
                                        {{ apt.type }}
                                    </span>
                                    <span v-if="apt.status === 'completed'" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Terminé</span>
                                    <span v-if="apt.status === 'cancelled'" class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Annulé</span>
                                </div>
                                <div class="mt-1 flex items-center gap-x-4 text-xs leading-5 text-slate-500">
                                    <div class="flex items-center gap-x-1">
                                        <ClockIcon class="h-4 w-4 text-slate-400" />
                                        <p>{{ formatDateTime(apt.start_time) }}</p>
                                    </div>
                                    <div v-if="apt.location" class="flex items-center gap-x-1">
                                        <MapPinIcon class="h-4 w-4 text-slate-400" />
                                        <p>{{ apt.location }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-none items-center gap-x-4">
                                <button @click="openAptEdit(apt)" class="hidden rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:block">Modifier</button>
                                <button @click="deleteApt(apt)" class="text-red-400 hover:text-red-600 p-2">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>
                        </li>
                        <li v-if="dossier.appointments.length === 0" class="px-6 py-12 text-center text-slate-500 text-sm">
                            Aucun rendez-vous planifié.
                        </li>
                    </ul>

                    <AppointmentModal 
                        :show="isAptModalOpen" 
                        :dossier-id="dossier.id" 
                        :appointment="editingApt" 
                        @close="isAptModalOpen = false" 
                    />
                </TabPanel>

                <TabPanel class="rounded-xl bg-white shadow-sm ring-1 ring-slate-900/5 focus:outline-none overflow-hidden">
                    <div class="border-b border-slate-200 px-6 py-5 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-slate-900">Comptes Rendus</h3>
                            <p class="mt-1 text-sm text-slate-500">Historique des notes et audiences</p>
                        </div>
                        <button @click="openReportCreate" type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                            Rédiger une note
                        </button>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div v-for="report in dossier.reports" :key="report.id" class="p-6 hover:bg-slate-50 transition group">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-slate-900">{{ reportTypeLabels[report.type] || report.type }}</span>
                                    <span v-if="report.status === 'draft'" class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Brouillon</span>
                                </div>
                                <!-- <div class="flex items-center text-xs text-slate-400 gap-2">
                                    <span>{{ formatDate(report.report_date) }}</span>
                                    <span>•</span>
                                    <div class="flex items-center">
                                        <UserCircleIcon class="h-3 w-3 mr-1" />
                                        {{ report.author?.name }}
                                    </div>
                                    <div class="hidden group-hover:flex gap-2 ml-4">
                                        <button @click="openReportEdit(report)" class="text-indigo-600 hover:text-indigo-900 font-medium">Éditer</button>
                                        <button @click="deleteReport(report)" class="text-red-400 hover:text-red-600 font-medium">Supprimer</button>
                                    </div>
                                </div> -->
                                <div class="flex items-center gap-2 ml-auto"> <a :href="route('reports.download', report.id)" target="_blank" class="p-1 text-slate-400 hover:text-indigo-600 transition" title="Télécharger PDF">
                                        <ArrowDownTrayIcon class="h-4 w-4" />
                                    </a>

                                    <button @click="sendReportByEmail(report)" class="p-1 text-slate-400 hover:text-emerald-600 transition" title="Envoyer par email" :disabled="!dossier.client.email">
                                        <PaperAirplaneIcon class="h-4 w-4" />
                                    </button>

                                    <div class="h-4 w-px bg-slate-200 mx-1"></div> <button @click="openReportEdit(report)" class="text-xs font-medium text-slate-500 hover:text-indigo-600">Éditer</button>
                                    <button @click="deleteReport(report)" class="text-xs font-medium text-slate-500 hover:text-red-600">Supprimer</button>
                                </div>
                            </div>
                            
                            <div class="text-sm text-slate-600 whitespace-pre-line line-clamp-3">
                                {{ report.content?.body }}
                            </div>
                            <button @click="openReportEdit(report)" class="mt-2 text-xs text-indigo-500 hover:text-indigo-700 font-medium">Lire la suite</button>
                        </div>
                        
                        <div v-if="dossier.reports.length === 0" class="px-6 py-12 text-center text-slate-500 text-sm">
                            Aucun compte rendu rédigé.
                        </div>
                    </div>

                    <ReportModal 
                        :show="isReportModalOpen" 
                        :dossier-id="dossier.id" 
                        :report="editingReport" 
                        @close="isReportModalOpen = false" 
                    />
                </TabPanel>

                <TabPanel class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 focus:outline-none">
                    <DocumentsTab :dossier="dossier" />
                </TabPanel>
                
                <TabPanel class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-900/5 focus:outline-none">
                    <h3 class="text-base font-semibold leading-6 text-slate-900 mb-6">Journal d'activité</h3>
                    <ActivityFeed :activities="activities" />
                </TabPanel>
            </TabPanels>
        </TabGroup>

        <DossierFormSlideOver 
            :show="isEditOpen" 
            :dossier="dossier" 
            :clients="[]" 
            :lawyers="[]"
            @close="refreshDossier" 
        />
        </MainLayout>
</template>