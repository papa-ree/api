<?php

namespace Bale\Api\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bale\Api\Api
 */
class Api extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bale\Api\Api::class;
    }
}
