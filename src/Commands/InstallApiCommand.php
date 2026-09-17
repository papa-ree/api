<?php

namespace Bale\Api\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class InstallApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install bale/api: Seed token permissions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting bale/api installation...');

        $this->seedPermissions();

        $this->info('bale/api installation completed successfully!');

        return self::SUCCESS;
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            'api-token.read',
            'api-token.create',
            'api-token.update',
            'api-token.revoke',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission], ['guard_name' => 'web']);
        }

        $this->info('Permissions seeded: '.implode(', ', $permissions));

        // Force sync to root role if exists
        $rootRole = Role::where('name', 'root')->first();

        if ($rootRole) {
            $this->info('Force syncing ALL permissions to root role...');
            $rootRole->syncPermissions(Permission::where('name', '!=', 'guest.sidebar')->get());

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->info('Permissions force synced and cache cleared for root role.');
        }
    }
}
