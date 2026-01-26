<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
    DocumentIcon, CloudArrowUpIcon, TrashIcon, 
    ArrowDownTrayIcon, EyeIcon, PaperClipIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    dossier: Object,
});

const isDragging = ref(false);
const fileInput = ref(null);

const form = useForm({
    dossier_id: props.dossier.id,
    file: null,
});

const handleFiles = (files) => {
    if (files.length > 0) {
        form.file = files[0];
        submit();
    }
};

const onDrop = (e) => {
    isDragging.value = false;
    handleFiles(e.dataTransfer.files);
};

const onFileChange = (e) => {
    handleFiles(e.target.files);
};

const submit = () => {
    form.post(route('documents.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('file'),
    });
};

const deleteDoc = (doc) => {
    if(confirm('Supprimer ce document ?')) {
        useForm({}).delete(route('documents.destroy', doc.id), { preserveScroll: true });
    }
};

// Fonction pour deviner l'icône selon le type
const getFileIcon = (mime) => {
    if (mime.includes('pdf')) return 'text-rose-500';
    if (mime.includes('image')) return 'text-emerald-500';
    if (mime.includes('word') || mime.includes('officedocument')) return 'text-blue-500';
    return 'text-slate-400';
};
</script>

<template>
    <div class="space-y-6">
        
        <div 
            class="relative flex justify-center rounded-xl border-2 border-dashed px-6 py-10 transition-all duration-200 group"
            :class="[
                isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 hover:border-indigo-400 hover:bg-slate-50',
                form.processing ? 'opacity-50 pointer-events-none' : ''
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
        >
            <div class="text-center">
                <CloudArrowUpIcon class="mx-auto h-12 w-12 text-slate-300 group-hover:text-indigo-500 transition-colors" />
                <div class="mt-4 flex text-sm leading-6 text-slate-600 justify-center">
                    <label for="file-upload" class="relative cursor-pointer rounded-md font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                        <span>Téléverser un fichier</span>
                        <input id="file-upload" name="file-upload" type="file" class="sr-only" ref="fileInput" @change="onFileChange">
                    </label>
                    <p class="pl-1">ou glisser-déposer</p>
                </div>
                <p class="text-xs leading-5 text-slate-500">PDF, PNG, JPG, DOCX jusqu'à 10MB</p>
            </div>
            
            <div v-if="form.processing" class="absolute inset-0 bg-white/80 flex items-center justify-center rounded-xl">
                <span class="text-indigo-600 font-bold animate-pulse">Envoi en cours...</span>
            </div>
        </div>

        <div v-if="dossier.documents.length > 0">
            <h3 class="text-sm font-medium text-slate-900 mb-4">Fichiers du dossier ({{ dossier.documents.length }})</h3>
            <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8">
                <li v-for="doc in dossier.documents" :key="doc.id" class="relative group">
                    <div class="group block w-full overflow-hidden rounded-lg bg-white border border-slate-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3 truncate">
                                <DocumentIcon class="h-8 w-8 flex-shrink-0" :class="getFileIcon(doc.mime_type)" />
                                <div class="truncate">
                                    <p class="truncate text-sm font-medium text-slate-900" :title="doc.name">{{ doc.name }}</p>
                                    <p class="text-xs text-slate-500">{{ (doc.size / 1024 / 1024).toFixed(2) }} MB • {{ new Date(doc.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a :href="route('documents.preview', doc.id)" target="_blank" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-md" title="Voir">
                                <EyeIcon class="h-4 w-4" />
                            </a>
                            <a :href="route('documents.download', doc.id)" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-md" title="Télécharger">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </a>
                            <button @click="deleteDoc(doc)" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-md" title="Supprimer">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        
        <div v-else class="text-center py-8">
            <PaperClipIcon class="mx-auto h-10 w-10 text-slate-300" />
            <p class="mt-2 text-sm text-slate-500">Aucun document joint à ce dossier.</p>
        </div>

    </div>
</template>