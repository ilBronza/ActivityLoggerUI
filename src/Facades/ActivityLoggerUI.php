<?php

namespace IlBronza\ActivityLoggerUI\Facades;

use Illuminate\Support\Facades\Facade;

class ActivityLoggerUI extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'activityloggerui';
    }
}
