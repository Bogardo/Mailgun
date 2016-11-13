<?php

namespace Bogardo\Mailgun\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Mailgun.
 */
class Mailgun extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mailgun';
    }
}
