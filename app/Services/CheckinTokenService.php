<?php

namespace App\Services;

use App\Exceptions\CheckinTokenException;
use Illuminate\Support\Facades\Cache;

/**
 * Rotating check-in tokens (CROSS_PLATFORM_PLAN.md, Phase 3).
 *
 * The company displays a QR code at the worksite encoding a compact
 * self-validating payload: `ojtsync://checkin?company=ID&window=W&token=HMAC`.
 * The token is hash_hmac('sha256', "ID|W", APP_KEY) — no extra secret to
 * manage, and every new 60-second window produces a new signature, so the
 * displayed QR rotates each minute.
 *
 * A custom `ojtsync://` scheme (instead of an https URL) means the phone's
 * *native* camera app can't usefully open the payload — only the in-app
 * scanner submits it, and the server never trusts the client beyond the
 * HMAC anyway.
 *
 * Verification: correct HMAC + a window no older than the grace period
 * proves the student scanned the code "right now"; a per-(company, window,
 * student) cache nonce consumed on first use stops the same student from
 * replaying a photographed QR while it is still valid. Other students
 * scanning the same displayed code get their own nonce, so a line of
 * students can check in with one screen.
 */
class CheckinTokenService
{
    /** Lifetime of one displayed QR window, in seconds. */
    public const WINDOW_SECONDS = 60;

    /**
     * How many past windows are still accepted — covers the student who
     * scans just before the company's screen refreshes. Worst case a token
     * lives ~3 minutes, which still satisfies "reject expired tokens".
     */
    public const GRACE_WINDOWS = 2;

    /** Cache TTL for the one-time nonce: full token lifetime + slack. */
    public const NONCE_TTL_SECONDS = self::WINDOW_SECONDS * (self::GRACE_WINDOWS + 1) + 30;

    public const PAYLOAD_SCHEME = 'ojtsync://checkin';

    public function currentWindow(): int
    {
        return (int) floor(now()->timestamp / self::WINDOW_SECONDS);
    }

    /**
     * Seconds until the displayed QR rotates.
     */
    public function secondsLeftInWindow(): int
    {
        return self::WINDOW_SECONDS - (now()->timestamp % self::WINDOW_SECONDS);
    }

    /**
     * QR payload for the given company, bound to one time window.
     */
    public function payloadFor(int $companyId, ?int $window = null): string
    {
        $window ??= $this->currentWindow();

        return sprintf(
            '%s?company=%d&window=%d&token=%s',
            self::PAYLOAD_SCHEME,
            $companyId,
            $window,
            $this->signatureFor($companyId, $window),
        );
    }

    /**
     * Verify a scanned payload. Returns the company id and window it was
     * signed for; the caller must pass the window to releaseNonce() if the
     * subsequent check-in fails, so a legitimate retry stays possible.
     *
     * @return array{company_id: int, window: int}
     *
     * @throws CheckinTokenException invalid / expired / reused tokens
     */
    public function verify(string $scannedPayload, int $studentId): array
    {
        $params = [];

        parse_str((string) parse_url($scannedPayload, PHP_URL_QUERY), $params);

        $companyId = (int) ($params['company'] ?? 0);
        $window = (int) ($params['window'] ?? 0);
        $token = (string) ($params['token'] ?? '');

        $signatureOk = str_starts_with($scannedPayload, self::PAYLOAD_SCHEME.'?')
            && $companyId >= 1
            && $window >= 1
            && $token !== ''
            && hash_equals($this->signatureFor($companyId, $window), $token);

        if (! $signatureOk) {
            $this->reject('invalid', 'This QR code is not a valid OJT Sync check-in code.', 422);
        }

        $currentWindow = $this->currentWindow();

        if ($window > $currentWindow) {
            $this->reject('invalid', 'This check-in code is not valid yet. Scan the QR shown right now.', 422);
        }

        if ($window < $currentWindow - self::GRACE_WINDOWS) {
            $this->reject('expired', 'This check-in code has expired. Scan the QR shown at the company right now.', 410);
        }

        // One-time use per student per window: the first scan consumes the
        // nonce, so a photographed QR replayed later in the same window is
        // rejected even though the HMAC would still verify.
        $nonce = sprintf('checkin-token:%d:%d:%d', $companyId, $window, $studentId);

        if (! Cache::add($nonce, true, self::NONCE_TTL_SECONDS)) {
            $this->reject('reused', 'You already used this check-in code. Scan the QR shown right now.', 409);
        }

        return ['company_id' => $companyId, 'window' => $window];
    }

    /**
     * Give back the one-time nonce after a failed check-in (e.g. the student
     * still had an active entry): the token was never actually *used*, so a
     * corrected retry within the same window must not be flagged as reuse.
     */
    public function releaseNonce(int $companyId, int $window, int $studentId): void
    {
        Cache::forget(sprintf('checkin-token:%d:%d:%d', $companyId, $window, $studentId));
    }

    private function signatureFor(int $companyId, int $window): string
    {
        return hash_hmac('sha256', $companyId.'|'.$window, $this->key());
    }

    /**
     * APP_KEY in its raw binary form (Laravel stores it base64-prefixed).
     */
    private function key(): string
    {
        $key = (string) config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }

        return $key;
    }

    private function reject(string $errorKey, string $message, int $status): never
    {
        throw new CheckinTokenException($errorKey, $message, $status);
    }
}
