<?php
namespace Database\Seeders;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Permisos base
        $perms = [
            'orders.view',         // ver listado y detalle
            'orders.update',       // cambiar estado del pedido
            'payments.validate',   // validar/rechazar voucher
            'catalog.manage',      // (futuro) admin de productos
            'users.manage',        // (futuro) admin de usuarios
        ];
        foreach ($perms as $p) Permission::findOrCreate($p, 'web');

        // Roles
        $admin    = Role::findOrCreate('admin', 'web');
        $vendedor = Role::findOrCreate('vendedor', 'web');
        $cliente  = Role::findOrCreate('cliente', 'web');

        // Asignación de permisos
        $admin->givePermissionTo(Permission::all());
        $vendedor->syncPermissions(['orders.view', 'orders.update', 'payments.validate']);
        // cliente sin permisos especiales (comportamiento por defecto)
    }
}
