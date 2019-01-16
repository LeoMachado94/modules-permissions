<?php

namespace LeoMachado\Permission\Exceptions;

use InvalidArgumentException;

class ModuleDoesNotExist extends InvalidArgumentException
{
    public static function named(string $moduleName)
    {
        return new static("There is no module named `{$moduleName}`.");
    }

    public static function withId(int $moduleId)
    {
        return new static("There is no module with id `{$moduleId}`.");
    }
}
