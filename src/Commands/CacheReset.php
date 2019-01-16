<?php

namespace LeoMachado\Permission\Commands;

use Illuminate\Console\Command;
use LeoMachado\Permission\PermissionRegistrar;

class CacheReset extends Command
{
    protected $signature = 'permission:cache-reset';

    protected $description = 'Reset the permission cache';

    public function handle()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permission cache flushed.');
    }
}
