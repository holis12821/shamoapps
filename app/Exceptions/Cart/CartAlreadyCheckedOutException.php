<?php

namespace App\Exceptions\Cart;

use App\Exceptions\ApiException;
use Exception;

class CartAlreadyCheckedOutException extends ApiException
{
    public function __construct()
    {
        return parent::__construct(message: 'Cart has already been checked out', statusCode: 409);
    }
}
