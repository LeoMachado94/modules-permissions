<?php

namespace LeoMachado\Permission\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use LeoMachado\Permission\Exceptions\UnauthorizedException;

class ModuleOrPermissionMiddleware
{
    public function handle($request, Closure $next, $moduleOrPermission)
    {
        if (Auth::guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $modulesOrPermissions = is_array($moduleOrPermission)
            ? $moduleOrPermission
            : explode('|', $moduleOrPermission);

        if (! Auth::user()->hasAnyModule($modulesOrPermissions) && ! Auth::user()->hasAnyPermission($modulesOrPermissions)) {
            throw UnauthorizedException::forModuleOrPermissions($modulesOrPermissions);
        }

        return $next($request);
    }
}
