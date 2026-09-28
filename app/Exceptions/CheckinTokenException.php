<?php

namespace App\Exceptions;

/**
 * Thrown by CheckinTokenService when a scanned QR check-in token cannot be
 * accepted. The errorKey distinguishes invalid / expired / reused for the
 * client (the scanner page shows a different recovery hint per key) and the
 * API controller turns it into the same JSON error payload shape the offline
 * sync endpoints use.
 */
class CheckinTokenException extends DomainActionException {}
