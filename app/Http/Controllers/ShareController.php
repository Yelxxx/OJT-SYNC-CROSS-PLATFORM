<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JobPosting;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\View\View;

class ShareController extends Controller
{
    /**
     * Public, no-login company page listing the company's open OJT posts.
     * Reachable via the tokenized QR links HR shares.
     */
    public function company(string $token): View
    {
        $company = Company::query()
            ->where('share_token', $token)
            ->where('is_active', true)
            ->with(['jobPostings' => fn ($q) => $q->open()->latest()])
            ->firstOrFail();

        return view('share.company', ['company' => $company]);
    }

    public function jobQr(JobPosting $job): View
    {
        $company = auth()->user()->company;

        abort_if(! $company || $job->company_id !== $company->id, 403, 'This job posting belongs to another company.');

        $targetUrl = route('share.company', ['token' => $company->share_token]);

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'svgAddXmlHeader' => false,
            'outputBase64' => false,
            'scale' => 8,
            'imageTransparent' => false,
        ]);

        $qrSvg = (new QRCode($options))->render($targetUrl);

        return view('hr.jobs.qr', [
            'job' => $job,
            'company' => $company,
            'targetUrl' => $targetUrl,
            'qrSvg' => $qrSvg,
        ]);
    }
}
