<?php

namespace App\Exceptions;

use Exception;
use RuntimeException;

class CartNotActiveException extends CheckoutException
{
    protected $message = 'Cart not found or already checked out';
}
