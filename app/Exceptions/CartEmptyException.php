<?php

namespace App\Exceptions;

use Exception;

class CartEmptyException extends CheckoutException
{
    protected $message = 'Cart Empty';
}
