<?php

namespace App\Core\Http\Exceptions;

class RouteNotFoundException extends \Exception
{
    protected $message = 'Route not found';
    protected $code = 404;
} 