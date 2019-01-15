<?php

namespace Spatie\Permission\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    private $requiredModules = [];

    private $requiredPermissions = [];

    public static function forModules(array $modules): self
    {
        $message = 'User does not have the right modules.';

        if (config('permission.display_permission_in_exception')) {
            $permStr = implode(', ', $modules);
            $message = 'User does not have the right modules. Necessary modules are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredModules = $modules;

        return $exception;
    }

    public static function forPermissions(array $permissions): self
    {
        $message = 'User does not have the right permissions.';

        if (config('permission.display_permission_in_exception')) {
            $permStr = implode(', ', $permissions);
            $message = 'User does not have the right permissions. Necessary permissions are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredPermissions = $permissions;

        return $exception;
    }

    public static function forModulesOrPermissions(array $modulesOrPermissions): self
    {
        $message = 'User does not have any of the necessary access rights.';

        if (config('permission.display_permission_in_exception') && config('permission.display_module_in_exception')) {
            $permStr = implode(', ', $modulesOrPermissions);
            $message = 'User does not have the right permissions. Necessary permissions are '.$permStr;
        }

        $exception = new static(403, $message, null, []);
        $exception->requiredPermissions = $modulesOrPermissions;

        return $exception;
    }

    public static function notLoggedIn(): self
    {
        return new static(403, 'User is not logged in.', null, []);
    }

    public function getRequiredModules(): array
    {
        return $this->requiredModules;
    }

    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }
}
