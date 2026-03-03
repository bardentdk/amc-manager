<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // return array_merge(parent::share($request), [
        //     // C'est ici qu'on partage l'utilisateur globalement
        //     'auth' => [
        //         'user' => $request->user(),
        //     ],
        //     // On peut aussi partager les messages flash (succès/erreur)
        //     'flash' => [
        //         'success' => fn () => $request->session()->get('success'),
        //         'error' => fn () => $request->session()->get('error'),
        //     ],
        // ]);
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    // On ajoute les rôles (utile pour le frontend)
                    'roles' => $request->user()->getRoleNames(),
                    // 👇 AJOUT DES NOTIFICATIONS ICI 👇
                    'unreadNotificationsCount' => $request->user()->unreadNotifications()->count(),
                    // On prend seulement les 5 plus récentes pour le menu déroulant
                    'notifications' => $request->user()->notifications()->take(5)->get(), 
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ]);
    }
}
