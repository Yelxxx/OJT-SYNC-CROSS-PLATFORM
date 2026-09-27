<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by domain services when a guarded action cannot proceed.
 *
 * Web controllers re-throw it with abort() to keep the historical 403/409
 * redirects; API controllers translate it into a JSON error payload that the
 * offline sync queue can branch on (conflicts are success-with-notice,
 * everything else is retried or dropped).
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
