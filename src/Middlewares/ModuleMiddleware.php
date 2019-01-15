<?php

namespace Spatie\Permission\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class ModuleMiddleware
{
    public function handle($request, Closure $next, $module)
    {
        if (Auth::guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $modules = is_array($module)
            ? $module
            : explode('|', $module);

        if (! Auth::user()->hasAnyModule($modules)) {
            throw UnauthorizedException::forModules($modules);
        }

        return $next($request);
    }
}
