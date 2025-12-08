<script setup>
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <Head title="Connexion" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">
                NEXA
            </h1>
            <p class="text-slate-500 text-sm mt-2">Gestion Cabinet & Clients</p>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-xl rounded-2xl border border-slate-100">
            <form @submit.prevent="submit">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input 
                        id="email" 
                        type="email" 
                        v-model="form.email" 
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5" 
                        required 
                        autofocus 
                    />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                </div>

                <div class="mt-4">
                    <label for="password" class="block text-sm font-medium text-slate-700">Mot de passe</label>
                    <input 
                        id="password" 
                        type="password" 
                        v-model="form.password" 
                        class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2.5" 
                        required 
                    />
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" v-model="form.remember" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-slate-600">Se souvenir de moi</span>
                    </label>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <button 
                        class="w-full inline-flex justify-center py-2.5 px-4 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-colors" 
                        :class="{ 'opacity-25': form.processing }" 
                        :disabled="form.processing"
                    >
                        Se connecter
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>