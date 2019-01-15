<?php

namespace Spatie\Permission\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Contracts\Module as ModuleContract;
use Spatie\Permission\Contracts\Permission as PermissionContract;

class CreateModule extends Command
{
    protected $signature = 'permission:create-module
        {name : The name of the module}
        {guard? : The name of the guard}
        {permissions? : A list of permissions to assign to the module, separated by | }';

    protected $description = 'Create a module';

    public function handle()
    {
        $moduleClass = app(ModuleContract::class);

        $module = $moduleClass::findOrCreate($this->argument('name'), $this->argument('guard'));

        $module->givePermissionTo($this->makePermissions($this->argument('permissions')));

        $this->info("Role `{$module->name}` created");
    }

    protected function makePermissions($string = null)
    {
        if (empty($string)) {
            return;
        }

        $permissionClass = app(PermissionContract::class);

        $permissions = explode('|', $string);

        $models = [];

        foreach ($permissions as $permission) {
            $models[] = $permissionClass::findOrCreate(trim($permission), $this->argument('guard'));
        }

        return collect($models);
    }
}
