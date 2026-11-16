<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by domain services when a guarded action cannot proceed.
 *
 * Web controllers re-throw it with abort() to keep the historical 403/409
 * redirects.
 */
class DomainActionException extends Exception
{
    public function __construct(
        public readonly string $errorKey,
        string $message,
        public readonly int $status = 409,
    ) {
        parent::__construct($message);
    }
}
