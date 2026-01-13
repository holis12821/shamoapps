<?php

namespace App\Exceptions;

use Exception;

class CartEmptyException extends ApiException
{
    public function __construct()
    {
        return parent::__construct('Cart Empty', 400);
    }
}
