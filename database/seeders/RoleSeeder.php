<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // On vide le cache des permissions pour éviter les conflits
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Création des rôles (firstOrCreate évite les doublons si on relance le seeder)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'lawyer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'assistant', 'guard_name' => 'web']);

        // Optionnel : Créer des permissions si besoin plus tard
        // Permission::firstOrCreate(['name' => 'manage users']);
    }
}