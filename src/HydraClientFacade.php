<?php

namespace Hardcorp\HydraClient;

use Illuminate\Support\Facades\Facade;

class HydraClientFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'hydra-client';
    }
}
