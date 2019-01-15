<?php

namespace Spatie\Permission;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Spatie\Permission\Contracts\Module as ModuleContract;
use Spatie\Permission\Contracts\Permission as PermissionContract;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistrar $permissionLoader, Filesystem $filesystem)
    {
        if (isNotLumen()) {
            $this->publishes([
                __DIR__.'/../config/permission.php' => config_path('permission.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/create_permission_tables.php.stub' => $this->getMigrationFileName($filesystem),
            ], 'migrations');

            if (app()->version() >= '5.5') {
                $this->registerMacroHelpers();
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\CacheReset::class,
                Commands\CreateModule::class,
                Commands\CreatePermission::class,
            ]);
        }

        $this->registerModelBindings();

        $permissionLoader->registerPermissions();

        $this->app->singleton(PermissionRegistrar::class, function ($app) use ($permissionLoader) {
            return $permissionLoader;
        });
    }

    public function register()
    {
        if (isNotLumen()) {
            $this->mergeConfigFrom(
                __DIR__.'/../config/permission.php',
                'permission'
            );
        }

        $this->registerBladeExtensions();
    }

    protected function registerModelBindings()
    {
        $config = $this->app->config['permission.models'];

        $this->app->bind(PermissionContract::class, $config['permission']);
        $this->app->bind(ModuleContract::class, $config['module']);
    }

    protected function registerBladeExtensions()
    {
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $bladeCompiler) {
            $bladeCompiler->directive('module', function ($arguments) {
                list($module, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasModule({$module})): ?>";
            });
            $bladeCompiler->directive('elsemodule', function ($arguments) {
                list($module, $guard) = explode(',', $arguments.',');

                return "<?php elseif(auth({$guard})->check() && auth({$guard})->user()->hasModule({$module})): ?>";
            });
            $bladeCompiler->directive('endmodule', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasmodule', function ($arguments) {
                list($module, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasModule({$module})): ?>";
            });
            $bladeCompiler->directive('endhasmodule', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasanymodule', function ($arguments) {
                list($module, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAnyModule({$modules})): ?>";
            });
            $bladeCompiler->directive('endhasanymodule', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('hasallmodules', function ($arguments) {
                list($modules, $guard) = explode(',', $arguments.',');

                return "<?php if(auth({$guard})->check() && auth({$guard})->user()->hasAllModules({$modules})): ?>";
            });
            $bladeCompiler->directive('endhasallmodules', function () {
                return '<?php endif; ?>';
            });

            $bladeCompiler->directive('unlessmodule', function ($arguments) {
                list($module, $guard) = explode(',', $arguments.',');

                return "<?php if(!auth({$guard})->check() || ! auth({$guard})->user()->hasModule({$module})): ?>";
            });
            $bladeCompiler->directive('endunlessmodule', function () {
                return '<?php endif; ?>';
            });
        });
    }

    protected function registerMacroHelpers()
    {
        Route::macro('module', function ($modules = []) {
            if (! is_array($modules)) {
                $modules = [$modules];
            }

            $modules = implode('|', $modules);

            $this->middleware("module:$modules");

            return $this;
        });

        Route::macro('permission', function ($permissions = []) {
            if (! is_array($permissions)) {
                $permissions = [$permissions];
            }

            $permissions = implode('|', $permissions);

            $this->middleware("permission:$permissions");

            return $this;
        });
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_permission_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_permission_tables.php")
            ->first();
    }
}
