<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    HomeIcon, 
    UsersIcon, 
    FolderIcon, 
    CalendarIcon,
    UserPlusIcon,
    ArrowRightOnRectangleIcon, 
    BellIcon, 
    CheckCircleIcon,
    MagnifyingGlassIcon 
} from '@heroicons/vue/24/outline';
const page = usePage();
const user = computed(() => page.props.auth?.user || { name: 'Invité', role: 'guest', roles: [] });

// Navigation principale dynamique selon le rôle
const navigation = computed(() => {
    // On vérifie si l'utilisateur possède le rôle 'admin'
    const isAdmin = user.value.roles?.includes('admin');

    return [
        { name: 'Tableau de bord', href: route('dashboard'), icon: HomeIcon, active: route().current('dashboard') },
        { name: 'Clients', href: route('clients.index'), icon: UsersIcon, active: route().current('clients.*') },
        { name: 'Dossiers', href: route('dossiers.index'), icon: FolderIcon, active: route().current('dossiers.*') },
        { name: 'Agenda', href: route('appointments.index'), icon: CalendarIcon, active: route().current('appointments.*') },
        
        // La ligne magique : On ajoute l'onglet "Utilisateurs" UNIQUEMENT si isAdmin est vrai
        ...(isAdmin ? [{ name: 'Utilisateurs', href: route('users.index'), icon: UserPlusIcon, active: route().current('users.*') }] : [])
    ];
});

const markAsRead = (id) => {
    router.post(route('notifications.read', id), {}, { preserveScroll: true });
};

const markAllAsRead = () => {
    router.post(route('notifications.readAll'), {}, { preserveScroll: true });
};
</script>

<template>
    <div class="h-screen bg-slate-900 flex overflow-hidden font-sans">
        
        <aside class="w-20 lg:w-72 flex flex-col transition-all duration-300 relative z-20 flex-shrink-0">
            
            <div class="h-24 flex justify-center items-center px-6 lg:px-8">
                <div class="flex justify-center items-center gap-3">
                    <!-- <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-500/30">
                        N
                    </div>
                    <span class="text-2xl font-bold text-white tracking-tight hidden lg:block">NEXA</span> -->
                   <img src="/img/logo.png" alt="" width="100"> 
                </div>
            </div>

            <nav class="flex-1 px-4 space-y-2 mt-2 overflow-y-auto custom-scrollbar">
                <Link 
                    v-for="item in navigation" 
                    :key="item.name" 
                    :href="item.href"
                    class="group relative flex items-center px-4 py-3.5 text-sm font-medium rounded-xl transition-all duration-200"
                    :class="[
                        item.active 
                            ? 'text-white bg-white/10 shadow-inner backdrop-blur-sm' 
                            : 'text-slate-400 hover:text-white hover:bg-white/5'
                    ]"
                >
                    <div v-if="item.active" class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-400 rounded-r-full shadow-[0_0_12px_rgba(129,140,248,0.8)]"></div>

                    <component 
                        :is="item.icon" 
                        class="flex-shrink-0 h-6 w-6 transition-transform duration-300 group-hover:scale-110" 
                        :class="item.active ? 'text-indigo-400' : 'text-slate-400 group-hover:text-indigo-300'"
                    />
                    <span class="ml-4 hidden lg:block tracking-wide">{{ item.name }}</span>
                </Link>
            </nav>

            <div class="p-4 mt-auto">
                <div class="relative bg-slate-800/60 rounded-2xl p-4 border border-slate-700/50 backdrop-blur-sm">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-emerald-400 to-cyan-500 p-[2px]">
                            <div class="h-full w-full rounded-full bg-slate-900 flex items-center justify-center text-white font-bold text-sm">
                                {{ user.name.charAt(0) }}
                            </div>
                        </div>
                        <div class="hidden lg:block overflow-hidden min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ user.name }}</p>
                            <p class="text-xs text-slate-400 truncate capitalize">{{ user.role }}</p>
                        </div>
                        <Link :href="route('logout')" method="post" as="button" class="ml-auto text-slate-400 hover:text-rose-400 transition-colors p-1 rounded-md hover:bg-white/5">
                            <ArrowRightOnRectangleIcon class="h-5 w-5" />
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 bg-slate-50 lg:rounded-tl-[2.5rem] lg:my-3 lg:mr-3 shadow-2xl overflow-hidden relative isolate">
            
            <header class="sticky top-0 z-30 flex items-center justify-between px-8 py-5 bg-slate-50/80 backdrop-blur-xl border-b border-slate-200/50">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                    <slot name="header">AMC JURIDIQUE</slot>
                </h1>

                <div class="flex items-center gap-5">
                    <div class="hidden md:flex items-center bg-white px-4 py-2.5 rounded-full border border-slate-200 shadow-sm focus-within:ring-2 focus-within:ring-indigo-100 transition-all w-64 hover:border-slate-300">
                        <MagnifyingGlassIcon class="h-4 w-4 text-slate-400 mr-2" />
                        <input type="text" placeholder="Rechercher (Cmd+K)" class="bg-transparent border-none text-sm p-0 focus:ring-0 w-full placeholder:text-slate-400 text-slate-700">
                    </div>

                    <Menu as="div" class="relative ml-4 flex-shrink-0">
                        <div>
                            <MenuButton class="relative rounded-full bg-white p-1 text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <span class="sr-only">Voir les notifications</span>
                                <BellIcon class="h-6 w-6" aria-hidden="true" />
                                
                                <span v-if="$page.props.auth.user.unreadNotificationsCount > 0" class="absolute top-0 right-0 block h-2.5 w-2.5 transform translate-x-1 -translate-y-1 rounded-full bg-red-500 ring-2 ring-white"></span>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                
                                <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center">
                                    <p class="text-sm font-medium text-slate-900">Notifications</p>
                                    <button v-if="$page.props.auth.user.unreadNotificationsCount > 0" @click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800">Tout marquer comme lu</button>
                                </div>

                                <div class="max-h-96 overflow-y-auto">
                                    <div v-if="$page.props.auth.user.notifications.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Aucune notification.
                                    </div>
                                    
                                    <MenuItem v-for="notification in $page.props.auth.user.notifications" :key="notification.id" v-slot="{ active }">
                                        <div :class="[notification.read_at === null ? 'bg-indigo-50/50' : '', 'px-4 py-3 border-b border-slate-50 relative group cursor-pointer hover:bg-slate-50 transition']">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1 min-w-0" @click="notification.data.url ? router.get(notification.data.url) : null">
                                                    <p :class="[notification.read_at === null ? 'font-semibold text-slate-900' : 'font-medium text-slate-700', 'text-sm truncate']">
                                                        {{ notification.data.title }}
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ notification.data.message }}</p>
                                                    <p class="text-xs text-slate-400 mt-1">{{ new Date(notification.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit' }) }}</p>
                                                </div>
                                                
                                                <button v-if="notification.read_at === null" @click.stop="markAsRead(notification.id)" class="ml-2 p-1 text-slate-400 hover:text-indigo-600 opacity-0 group-hover:opacity-100 transition" title="Marquer comme lu">
                                                    <CheckCircleIcon class="h-5 w-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </MenuItem>
                                </div>
                                
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto px-6 py-6 lg:px-10 lg:py-8 scroll-smooth" id="main-scroll">
                <slot />
            </div>

        </main>
    </div>
</template>

<style scoped>
/* Scrollbar spécifique à la nav si besoin */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #475569;
}
</style>