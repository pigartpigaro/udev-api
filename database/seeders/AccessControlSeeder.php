<?php

namespace Database\Seeders;

use App\Models\AccessControl\Menu;
use App\Models\AccessControl\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Workspace;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $workspaceId = Workspace::query()->where('slug', 'workspace-utama')->value('id');
        if (! $workspaceId) {
            return;
        }

        $permissionLabels = [
            'navigation.view' => 'Melihat navigasi aplikasi',
            'master.customers.manage' => 'Mengelola master pelanggan',
            'master.project-types.manage' => 'Mengelola master jenis proyek',
            'projects.manage' => 'Mengelola project',
            'projects.invoices.manage' => 'Mengelola invoice project',
            'projects.payments.manage' => 'Mengelola pembayaran invoice project',
            'projects.expenses.manage' => 'Mengelola pengeluaran project dan operasional tim',
            'projects.reports.view' => 'Melihat laporan keuangan project',
            'finance.team-loans.manage' => 'Mencatat kasbon dan pembayaran kasbon tim',
            'access-control.menus.manage' => 'Mengelola menu aplikasi',
            'access-control.permissions.manage' => 'Mengelola daftar izin',
            'access-control.roles.manage' => 'Mengatur izin per role',
            'workspaces.manage' => 'Mengatur workspace aktif',
            'users.manage' => 'Mengelola pengguna dan role workspace',
        ];

        $permissions = [];

        foreach ($permissionLabels as $key => $label) {
            $permissions[$key] = Permission::updateOrCreate(['key' => $key], ['label' => $label]);
        }

        Menu::updateOrCreate(
            ['key' => 'dashboard'],
            ['parent_id' => null, 'permission_id' => $permissions['navigation.view']->id, 'label' => 'Dashboard', 'route_name' => 'dashboard', 'icon' => 'dashboard', 'sort_order' => 1, 'is_active' => true],
        );
        $master = Menu::updateOrCreate(
            ['key' => 'master'],
            ['parent_id' => null, 'permission_id' => null, 'label' => 'Master', 'route_name' => null, 'icon' => 'database', 'sort_order' => 10, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'master.customers'],
            ['parent_id' => $master->id, 'permission_id' => $permissions['master.customers.manage']->id, 'label' => 'Pelanggan', 'route_name' => 'master.customers.index', 'icon' => 'users', 'sort_order' => 1, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'master.project-types'],
            ['parent_id' => $master->id, 'permission_id' => $permissions['master.project-types.manage']->id, 'label' => 'Jenis Proyek', 'route_name' => 'master.project-types.index', 'icon' => 'folders', 'sort_order' => 2, 'is_active' => true],
        );
        $projects = Menu::updateOrCreate(
            ['key' => 'projects'],
            ['parent_id' => null, 'permission_id' => null, 'label' => 'Project', 'route_name' => null, 'icon' => 'folder-kanban', 'sort_order' => 20, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'projects.project'],
            ['parent_id' => $projects->id, 'permission_id' => $permissions['projects.manage']->id, 'label' => 'Daftar Project', 'route_name' => 'projects.index', 'icon' => 'folder-kanban', 'sort_order' => 1, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'projects.invoices'],
            ['parent_id' => $projects->id, 'permission_id' => $permissions['projects.invoices.manage']->id, 'label' => 'Invoice', 'route_name' => 'projects.invoices.index', 'icon' => 'invoice', 'sort_order' => 2, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'projects.payments'],
            ['parent_id' => $projects->id, 'permission_id' => $permissions['projects.payments.manage']->id, 'label' => 'Pembayaran', 'route_name' => 'projects.payments.index', 'icon' => 'wallet', 'sort_order' => 3, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'projects.expenses'],
            ['parent_id' => $projects->id, 'permission_id' => $permissions['projects.expenses.manage']->id, 'label' => 'Pengeluaran', 'route_name' => 'projects.expenses.index', 'icon' => 'receipt', 'sort_order' => 4, 'is_active' => true],
        );
        $finance = Menu::updateOrCreate(
            ['key' => 'finance'],
            ['parent_id' => null, 'permission_id' => null, 'label' => 'Keuangan', 'route_name' => null, 'icon' => 'finance', 'sort_order' => 25, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'finance.team-loans'],
            ['parent_id' => $finance->id, 'permission_id' => $permissions['finance.team-loans.manage']->id, 'label' => 'Kasbon Tim', 'route_name' => 'finance.team-loans.index', 'icon' => 'receipt', 'sort_order' => 1, 'is_active' => true],
        );
        Menu::where('key', 'projects.reports.financial')->delete();
        $reports = Menu::updateOrCreate(
            ['key' => 'reports'],
            ['parent_id' => null, 'permission_id' => null, 'label' => 'Laporan', 'route_name' => null, 'icon' => 'chart-bar-big', 'sort_order' => 30, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'reports.financial'],
            ['parent_id' => $reports->id, 'permission_id' => $permissions['projects.reports.view']->id, 'label' => 'Laporan Keuangan', 'route_name' => 'reports.financial.index', 'icon' => 'chart-line', 'sort_order' => 1, 'is_active' => true],
        );

        $settings = Menu::updateOrCreate(
            ['key' => 'settings'],
            ['parent_id' => null, 'permission_id' => null, 'label' => 'Pengaturan', 'route_name' => null, 'icon' => 'settings', 'sort_order' => 90, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'settings.workspace'],
            ['parent_id' => $settings->id, 'permission_id' => $permissions['workspaces.manage']->id, 'label' => 'Workspace', 'route_name' => 'settings.workspace.index', 'icon' => 'briefcase', 'sort_order' => 1, 'is_active' => true],
        );
        Menu::updateOrCreate(
            ['key' => 'settings.users'],
            ['parent_id' => $settings->id, 'permission_id' => $permissions['users.manage']->id, 'label' => 'Pengguna', 'route_name' => 'settings.users.index', 'icon' => 'users', 'sort_order' => 2, 'is_active' => true],
        );
        $accessControl = Menu::updateOrCreate(
            ['key' => 'settings.access-control'],
            ['parent_id' => $settings->id, 'permission_id' => $permissions['access-control.roles.manage']->id, 'label' => 'Hak Akses', 'route_name' => 'settings.roles.index', 'icon' => 'shield', 'sort_order' => 3, 'is_active' => true],
        );

        foreach ([
            ['Menu', 'access-control.menus.manage', 'settings.access-control.menus', 'access-control.menus.index', 'list', 1],
            ['Daftar Izin', 'access-control.permissions.manage', 'settings.access-control.permissions', 'access-control.permissions.index', 'key', 2],
            ['Role dan Izin', 'access-control.roles.manage', 'settings.access-control.roles', 'settings.roles.index', 'user-cog', 3],
        ] as [$label, $permissionKey, $key, $routeName, $icon, $order]) {
            Menu::updateOrCreate(
                ['key' => $key],
                ['parent_id' => $accessControl->id, 'permission_id' => $permissions[$permissionKey]->id, 'label' => $label, 'route_name' => $routeName, 'icon' => $icon, 'sort_order' => $order, 'is_active' => false],
            );
        }

        foreach (['admin' => array_keys($permissionLabels), 'member' => ['navigation.view', 'finance.team-loans.manage']] as $role => $keys) {
            foreach ($keys as $key) {
                DB::table('role_permissions')->insertOrIgnore([
                    'workspace_id' => $workspaceId,
                    'role' => $role,
                    'permission_id' => $permissions[$key]->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach (Workspace::query()->pluck('id') as $existingWorkspaceId) {
            DB::table('role_permissions')->insertOrIgnore([
                'workspace_id' => $existingWorkspaceId,
                'role' => 'admin',
                'permission_id' => $permissions['finance.team-loans.manage']->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insertOrIgnore([
                'workspace_id' => $existingWorkspaceId,
                'role' => 'admin',
                'permission_id' => $permissions['users.manage']->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insertOrIgnore([
                'workspace_id' => $existingWorkspaceId,
                'role' => 'member',
                'permission_id' => $permissions['finance.team-loans.manage']->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
