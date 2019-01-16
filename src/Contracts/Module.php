<?php

namespace LeoMachado\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface Module
{
    /**
     * A module may be given various permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a module by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \LeoMachado\Permission\Contracts\Module
     *
     * @throws \LeoMachado\Permission\Exceptions\ModuleDoesNotExist
     */
    public static function findByName(string $name, $guardName): self;

    /**
     * Find a module by its id and guard name.
     *
     * @param int $id
     * @param string|null $guardName
     *
     * @return \LeoMachado\Permission\Contracts\Module
     *
     * @throws \LeoMachado\Permission\Exceptions\ModuleDoesNotExist
     */
    public static function findById(int $id, $guardName): self;

    /**
     * Find or create a module by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \LeoMachado\Permission\Contracts\Module
     */
    public static function findOrCreate(string $name, $guardName): self;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|\LeoMachado\Permission\Contracts\Permission $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission): bool;
}
