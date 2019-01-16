<?php

namespace LeoMachado\Permission\Models;

use LeoMachado\Permission\Guard;
use Illuminate\Database\Eloquent\Model;
use LeoMachado\Permission\Traits\HasPermissions;
use LeoMachado\Permission\Exceptions\ModuleDoesNotExist;
use LeoMachado\Permission\Exceptions\GuardDoesNotMatch;
use LeoMachado\Permission\Exceptions\ModuleAlreadyExists;
use LeoMachado\Permission\Contracts\Module as ModuleContract;
use LeoMachado\Permission\Traits\RefreshesPermissionCache;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model implements ModuleContract
{
    use HasPermissions;
    use RefreshesPermissionCache;

    public $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);

        $this->setTable(config('permission.table_names.modules'));
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? Guard::getDefaultName(static::class);

        if (static::where('name', $attributes['name'])->where('guard_name', $attributes['guard_name'])->first()) {
            throw ModuleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        if (isNotLumen() && app()::VERSION < '5.4') {
            return parent::create($attributes);
        }

        return static::query()->create($attributes);
    }

    /**
     * A module may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.module_has_permissions')
        );
    }

    /**
     * A module belongs to some users of the model associated with its guard.
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name']),
            'model',
            config('permission.table_names.model_has_modules'),
            'module_id',
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Find a module by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \LeoMachado\Permission\Contracts\Module|\LeoMachado\Permission\Models\Module
     *
     * @throws \LeoMachado\Permission\Exceptions\ModuleDoesNotExist
     */
    public static function findByName(string $name, $guardName = null): ModuleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $module = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (! $module) {
            throw ModuleDoesNotExist::named($name);
        }

        return $module;
    }

    public static function findById(int $id, $guardName = null): ModuleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $module = static::where('id', $id)->where('guard_name', $guardName)->first();

        if (! $module) {
            throw ModuleDoesNotExist::withId($id);
        }

        return $module;
    }

    /**
     * Find or create module by its name (and optionally guardName).
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \LeoMachado\Permission\Contracts\Module
     */
    public static function findOrCreate(string $name, $guardName = null): ModuleContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);

        $module = static::where('name', $name)->where('guard_name', $guardName)->first();

        if (! $module) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName]);
        }

        return $module;
    }

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     *
     * @throws \LeoMachado\Permission\Exceptions\GuardDoesNotMatch
     */
    public function hasPermissionTo($permission): bool
    {
        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission, $this->getDefaultGuardName());
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission, $this->getDefaultGuardName());
        }

        if (! $this->getGuardNames()->contains($permission->guard_name)) {
            throw GuardDoesNotMatch::create($permission->guard_name, $this->getGuardNames());
        }

        return $this->permissions->contains('id', $permission->id);
    }
}
