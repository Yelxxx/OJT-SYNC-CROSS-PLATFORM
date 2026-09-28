<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CheckinTokenService;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Company-side live check-in QR (CROSS_PLATFORM_PLAN.md, Phase 3).
 *
 * HR opens the kiosk page on a screen or tablet at the worksite. It shows a
 * fullscreen QR encoding the current 60-second window's token and polls the
 * refresh endpoint so the QR swaps itself out every window — nobody has to
 * touch the display during the day. The payload is generated server-side;
 * the browser only ever sees the rendered SVG.
 */
class CheckinQrController extends Controller
{
    public function __construct(private readonly CheckinTokenService $tokens) {}

    public function show(): View
    {
        $company = $this->company();

        return view('hr.checkin.qr', [
            'company' => $company,
            'qrSvg' => $this->qrSvg($this->tokens->payloadFor($company->id)),
            'secondsLeft' => $this->tokens->secondsLeftInWindow(),
        ]);
    }

    /**
     * Payload for the current window — polled by the kiosk page so the
     * displayed QR always matches the window students are scanning in.
     */
    public function token(): JsonResponse
    {
        $company = $this->company();

        return response()->json([
            'svg' => $this->qrSvg($this->tokens->payloadFor($company->id)),
            'window' => $this->tokens->currentWindow(),
            'seconds_left' => $this->tokens->secondsLeftInWindow(),
        ]);
    }

    private function company(): Company
    {
        $company = Auth::user()->company;

        abort_if(! $company, 403, 'Your account is not linked to a company.');

        return $company;
    }

    private function qrSvg(string $payload): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'svgAddXmlHeader' => false,
            'outputBase64' => false,
            'scale' => 10,
            'imageTransparent' => false,
        ]);

        return (new QRCode($options))->render($payload);
    }
}
