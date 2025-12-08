<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ton compte Admin
        User::create([
            'name' => 'Admin Nexa', // Mets ton prénom ici
            'email' => 'admin@nexa.app',
            'password' => Hash::make('password'), // Change le mot de passe bien sûr
            'role' => 'admin',
        ]);
        // Comptes fictifs pour Aïcha et Ali (pour tester plus tard)
        User::create(['name' => 'Aïcha Assistant', 'email' => 'aicha@nexa.app', 'password' => Hash::make('password'), 'role' => 'assistant']);
        User::create(['name' => 'Me Ali', 'email' => 'ali@nexa.app', 'password' => Hash::make('password'), 'role' => 'lawyer']);
    }
}