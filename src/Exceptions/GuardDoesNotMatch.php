<?php

namespace Spatie\Permission\Exceptions;

use InvalidArgumentException;
use Illuminate\Support\Collection;

class GuardDoesNotMatch extends InvalidArgumentException
{
    public static function create(string $givenGuard, Collection $expectedGuards)
    {
        return new static("The given module or permission should use guard `{$expectedGuards->implode(', ')}` instead of `{$givenGuard}`.");
    }
}
