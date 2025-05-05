<?php

namespace App\commons\exceptions;

use Exception;

class StripeStrategyException extends StripeCustomException
{
    public function __construct(string $message = "No applicable strategy found for event type:")
    {
        parent::__construct($message);
    }
}