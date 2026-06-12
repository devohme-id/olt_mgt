<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create Permissions ──
        $modules = [
            'dashboard' => ['view'],
            'olt'       => ['view', 'create', 'edit', 'delete', 'sync', 'reboot', 'backup'],
            'onu'       => ['view', 'create', 'edit', 'delete', 'provision', 'reboot', 'reset'],
            'alarm'     => ['view', 'acknowledge', 'resolve', 'configure'],
            'report'    => ['view', 'generate', 'export'],
            'topology'  => ['view'],
            'oid'       => ['view', 'explore', 'map'],
            'user'      => ['view', 'create', 'edit', 'delete'],
            'settings'  => ['view', 'edit'],
            'audit'     => ['view'],
            'api'       => ['access'],
        ];

        $permissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissions[] = Permission::create([
                    'name'   => ucfirst($module) . ' ' . ucfirst($action),
                    'slug'   => "{$module}.{$action}",
                    'module' => $module,
                    'action' => $action,
                ]);
            }
        }

        // ── Create Roles ──
        $roles = [
            'super-admin' => [
                'name'        => 'Super Admin',
                'description' => 'Full system access',
                'is_system'   => true,
                'permissions'  => '*', // All permissions
            ],
            'noc' => [
                'name'        => 'NOC',
                'description' => 'Network Operations Center — monitoring & alarm management',
                'is_system'   => true,
                'permissions'  => [
                    'dashboard.view', 'olt.view', 'onu.view',
                    'alarm.view', 'alarm.acknowledge', 'alarm.resolve',
                    'report.view', 'report.generate', 'report.export',
                    'topology.view', 'audit.view', 'api.access',
                ],
            ],
            'engineer' => [
                'name'        => 'Engineer',
                'description' => 'Network engineer — full device management',
                'is_system'   => true,
                'permissions'  => [
                    'dashboard.view',
                    'olt.view', 'olt.create', 'olt.edit', 'olt.sync', 'olt.reboot', 'olt.backup',
                    'onu.view', 'onu.create', 'onu.edit', 'onu.delete', 'onu.provision', 'onu.reboot', 'onu.reset',
                    'alarm.view', 'alarm.acknowledge', 'alarm.resolve',
                    'report.view', 'report.generate', 'report.export',
                    'topology.view', 'oid.view', 'oid.explore', 'oid.map',
                    'audit.view', 'api.access',
                ],
            ],
            'operator' => [
                'name'        => 'Operator',
                'description' => 'Field operator — device monitoring & ONU provisioning',
                'is_system'   => true,
                'permissions'  => [
                    'dashboard.view', 'olt.view', 'onu.view',
                    'onu.provision', 'onu.reboot',
                    'alarm.view', 'alarm.acknowledge',
                    'topology.view', 'api.access',
                ],
            ],
            'viewer' => [
                'name'        => 'Viewer',
                'description' => 'Read-only access',
                'is_system'   => true,
                'permissions'  => [
                    'dashboard.view', 'olt.view', 'onu.view',
                    'alarm.view', 'report.view', 'topology.view',
                ],
            ],
        ];

        foreach ($roles as $slug => $roleData) {
            $role = Role::create([
                'name'        => $roleData['name'],
                'slug'        => $slug,
                'description' => $roleData['description'],
                'is_system'   => $roleData['is_system'],
            ]);

            if ($roleData['permissions'] === '*') {
                $role->permissions()->attach(collect($permissions)->pluck('id'));
            } else {
                $permIds = collect($permissions)
                    ->whereIn('slug', $roleData['permissions'])
                    ->pluck('id');
                $role->permissions()->attach($permIds);
            }
        }

        // ── Create Default Super Admin User ──
        $adminRole = Role::where('slug', 'super-admin')->first();
        User::create([
            'name'     => 'Administrator',
            'email'    => 'admin@oltnms.local',
            'password' => 'admin123456',
            'role_id'  => $adminRole->id,
        ]);
    }
}
