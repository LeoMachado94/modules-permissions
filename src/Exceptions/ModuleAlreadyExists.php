<?php

namespace LeoMachado\Permission\Exceptions;

use InvalidArgumentException;

class ModuleAlreadyExists extends InvalidArgumentException
{
    public static function create(string $moduleName, string $guardName)
    {
        return new static("A module `{$moduleName}` already exists for guard `{$guardName}`.");
    }
}
