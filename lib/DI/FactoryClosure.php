<?php

namespace Proklung\Profilier\DI;

use Closure;

/**
 * Class FactoryClosure
 * @package Proklung\Profilier\DI
 *
 * @since 13.07.2021
 */
class FactoryClosure
{
    /**
     * @param Closure $closure Closure.
     *
     * @return mixed
     */
    public function from(Closure $closure)
    {
        return $closure();
    }
}