<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Rôles
        // $roleAdmin = Role::create(['name' => 'admin']);
        // $roleLawyer = Role::create(['name' => 'lawyer']);
        // $roleAssistant = Role::create(['name' => 'assistant']);

        // Permissions (Exemples)
        // Permission::create(['name' => 'delete clients']);
        // Permission::create(['name' => 'view financials']);

        // Assignations
        $roleAdmin->givePermissionTo(Permission::all());
        
        $roleLawyer->givePermissionTo(['view financials']); // Peut voir mais pas supprimer clients par ex.
        
        $roleAssistant->givePermissionTo([]); // Droits basiques uniquement
    }
}