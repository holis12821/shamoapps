<?php

namespace App\Exceptions;

class CartNotActiveException extends ApiException
{
    public function __construct()
    {
        return parent::__construct('Cart not found or already checked out', 400);
    }
}
