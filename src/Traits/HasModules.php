<?php

namespace LeoMachado\Permission\Traits;

use Illuminate\Support\Collection;
use LeoMachado\Permission\Contracts\Module;
use Illuminate\Database\Eloquent\Builder;
use LeoMachado\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasModules
{
    use HasPermissions;

    private $moduleClass;

    public static function bootHasModules()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->modules()->detach();
        });
    }

    public function getModuleClass()
    {
        if (! isset($this->moduleClass)) {
            $this->moduleClass = app(PermissionRegistrar::class)->getModuleClass();
        }

        return $this->moduleClass;
    }

    /**
     * A model may have multiple modules.
     */
    public function modules(): MorphToMany
    {
        return $this->morphToMany(
            config('permission.models.module'),
            'model',
            config('permission.table_names.model_has_modules'),
            config('permission.column_names.model_morph_key'),
            'module_id'
        );
    }

    /**
     * Scope the model query to certain modules only.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array|\LeoMachado\Permission\Contracts\Module|\Illuminate\Support\Collection $modules
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeModule(Builder $query, $modules): Builder
    {
        if ($modules instanceof Collection) {
            $modules = $modules->all();
        }

        if (! is_array($modules)) {
            $modules = [$modules];
        }

        $modules = array_map(function ($module) {
            if ($module instanceof Module) {
                return $module;
            }

            $method = is_numeric($module) ? 'findById' : 'findByName';

            return $this->getModuleClass()->{$method}($module, $this->getDefaultGuardName());
        }, $modules);

        return $query->whereHas('modules', function ($query) use ($modules) {
            $query->where(function ($query) use ($modules) {
                foreach ($modules as $module) {
                    $query->orWhere(config('permission.table_names.modules').'.id', $module->id);
                }
            });
        });
    }

    /**
     * Assign the given module to the model.
     *
     * @param array|string|\LeoMachado\Permission\Contracts\Module ...$modules
     *
     * @return $this
     */
    public function assignModule(...$modules)
    {
        $modules = collect($modules)
            ->flatten()
            ->map(function ($module) {
                if (empty($module)) {
                    return false;
                }

                return $this->getStoredModule($module);
            })
            ->filter(function ($module) {
                return $module instanceof Module;
            })
            ->each(function ($module) {
                $this->ensureModelSharesGuard($module);
            })
            ->map->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->modules()->sync($modules, false);
            $model->load('modules');
        } else {
            $class = \get_class($model);

            $class::saved(
                function ($object) use ($modules, $model) {
                    static $modelLastFiredOn;
                    if ($modelLastFiredOn !== null && $modelLastFiredOn === $model) {
                        return;
                    }
                    $object->modules()->sync($modules, false);
                    $object->load('modules');
                    $modelLastFiredOn = $object;
                });
        }

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Revoke the given module from the model.
     *
     * @param string|\LeoMachado\Permission\Contracts\Module $module
     */
    public function removeModule($module)
    {
        $this->modules()->detach($this->getStoredModule($module));

        $this->load('modules');
    }

    /**
     * Remove all current modules and set the given ones.
     *
     * @param array|\LeoMachado\Permission\Contracts\Module|string ...$modules
     *
     * @return $this
     */
    public function syncModules(...$modules)
    {
        $this->modules()->detach();

        return $this->assignModule($modules);
    }

    /**
     * Determine if the model has (one of) the given module(s).
     *
     * @param string|int|array|\LeoMachado\Permission\Contracts\Module|\Illuminate\Support\Collection $modules
     *
     * @return bool
     */
    public function hasModule($modules): bool
    {
        if (is_string($modules) && false !== strpos($modules, '|')) {
            $modules = $this->convertPipeToArray($modules);
        }

        if (is_string($modules)) {
            return $this->modules->contains('name', $modules);
        }

        if (is_int($modules)) {
            return $this->modules->contains('id', $modules);
        }

        if ($modules instanceof Module) {
            return $this->modules->contains('id', $modules->id);
        }

        if (is_array($modules)) {
            foreach ($modules as $module) {
                if ($this->hasModule($module)) {
                    return true;
                }
            }

            return false;
        }

        return $modules->intersect($this->modules)->isNotEmpty();
    }

    /**
     * Determine if the model has any of the given module(s).
     *
     * @param string|array|\LeoMachado\Permission\Contracts\Module|\Illuminate\Support\Collection $modules
     *
     * @return bool
     */
    public function hasAnyModule($modules): bool
    {
        return $this->hasModule($modules);
    }

    /**
     * Determine if the model has all of the given module(s).
     *
     * @param string|\LeoMachado\Permission\Contracts\Module|\Illuminate\Support\Collection $modules
     *
     * @return bool
     */
    public function hasAllModules($modules): bool
    {
        if (is_string($modules) && false !== strpos($modules, '|')) {
            $modules = $this->convertPipeToArray($modules);
        }

        if (is_string($modules)) {
            return $this->modules->contains('name', $modules);
        }

        if ($modules instanceof Module) {
            return $this->modules->contains('id', $modules->id);
        }

        $modules = collect()->make($modules)->map(function ($module) {
            return $module instanceof Module ? $module->name : $module;
        });

        return $modules->intersect($this->modules->pluck('name')) == $modules;
    }

    /**
     * Return all permissions directly coupled to the model.
     */
    public function getDirectPermissions(): Collection
    {
        return $this->permissions;
    }

    public function getModuleNames(): Collection
    {
        return $this->modules->pluck('name');
    }

    protected function getStoredModule($module): Module
    {
        $moduleClass = $this->getModuleClass();

        if (is_numeric($module)) {
            return $moduleClass->findById($module, $this->getDefaultGuardName());
        }

        if (is_string($module)) {
            return $moduleClass->findByName($module, $this->getDefaultGuardName());
        }

        return $module;
    }

    protected function convertPipeToArray(string $pipeString)
    {
        $pipeString = trim($pipeString);

        if (strlen($pipeString) <= 2) {
            return $pipeString;
        }

        $quoteCharacter = substr($pipeString, 0, 1);
        $endCharacter = substr($quoteCharacter, -1, 1);

        if ($quoteCharacter !== $endCharacter) {
            return explode('|', $pipeString);
        }

        if (! in_array($quoteCharacter, ["'", '"'])) {
            return explode('|', $pipeString);
        }

        return explode('|', trim($pipeString, $quoteCharacter));
    }
}
