<?php

namespace Kiwina\CashaddrConverter;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kiwina\CashaddrConverter\CashaddrConverter
 */
class CashaddrConverterFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cashaddr-converter';
    }
}
