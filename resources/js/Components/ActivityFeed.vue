<script setup>
import { 
    UserCircleIcon, 
    PencilSquareIcon, 
    PlusCircleIcon, 
    TrashIcon, 
    DocumentIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activities: Array
});

const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit' });

const getActionIcon = (description) => {
    if (description === 'created') return PlusCircleIcon;
    if (description === 'updated') return PencilSquareIcon;
    if (description === 'deleted') return TrashIcon;
    return UserCircleIcon;
};

const getActionColor = (description) => {
    if (description === 'created') return 'bg-emerald-100 text-emerald-600 ring-emerald-200';
    if (description === 'updated') return 'bg-amber-100 text-amber-600 ring-amber-200';
    if (description === 'deleted') return 'bg-rose-100 text-rose-600 ring-rose-200';
    return 'bg-slate-100 text-slate-600 ring-slate-200';
};

const translateAction = (desc) => {
    const map = { created: 'a créé', updated: 'a modifié', deleted: 'a supprimé' };
    return map[desc] || desc;
};
</script>

<template>
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            <li v-for="(activity, idx) in activities" :key="activity.id">
                <div class="relative pb-8">
                    <span v-if="idx !== activities.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                    
                    <div class="relative flex space-x-3">
                        <div>
                            <span :class="[getActionColor(activity.description), 'h-8 w-8 rounded-full flex items-center justify-center ring-4 ring-white']">
                                <component :is="getActionIcon(activity.description)" class="h-4 w-4" />
                            </span>
                        </div>
                        
                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                            <div>
                                <p class="text-sm text-slate-500">
                                    <span class="font-medium text-slate-900">{{ activity.causer?.name || 'Système' }}</span>
                                    {{ translateAction(activity.description) }}
                                    <span class="font-medium text-slate-900">le dossier</span>
                                </p>
                                
                                <div v-if="activity.properties?.attributes" class="mt-2 text-xs text-slate-500 bg-slate-50 p-2 rounded border border-slate-100">
                                    <div v-for="(val, key) in activity.properties.attributes" :key="key">
                                        <span class="font-semibold">{{ key }}:</span> {{ val }}
                                    </div>
                                </div>
                            </div>
                            <div class="whitespace-nowrap text-right text-xs text-slate-400">
                                {{ formatDate(activity.created_at) }}
                            </div>
                        </div>
                    </div>
                </div>
            </li>
            <li v-if="activities.length === 0" class="text-sm text-slate-500 italic text-center py-4">
                Aucune activité enregistrée.
            </li>
        </ul>
    </div>
</template>