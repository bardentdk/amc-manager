<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Users/Index', [
            'users' => User::with('roles')->latest()->get(),
            
            // ICI : On envoie la liste des noms de rôles au frontend
            'availableRoles' => Role::pluck('name'), 
        ]);
    }

    public function store(Request $request, BrevoService $brevoService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|exists:roles,name',
        ]);

        // 1. Génération d'un mot de passe aléatoire (8 caractères)
        $rawPassword = Str::random(10);

        // 2. Création de l'utilisateur
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($rawPassword),
        ]);

        // 3. Assignation du rôle
        $user->assignRole($request->role);

        // 4. Envoi de l'email via l'API Brevo
        try {
            $brevoService->sendWelcomeEmail($user->email, $user->name, $rawPassword, $request->role);
            return Redirect::back()->with('success', 'Utilisateur créé et identifiants envoyés par email.');
        } catch (\Exception $e) {
            return Redirect::back()->with('warning', 'Utilisateur créé, mais échec de l\'envoi d\'email (' . $e->getMessage() . '). Le mot de passe est : ' . $rawPassword);
        }
    }
    
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return Redirect::back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        
        $user->delete();
        return Redirect::back()->with('success', 'Utilisateur supprimé.');
    }
}