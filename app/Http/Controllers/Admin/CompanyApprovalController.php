<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyApprovalController extends Controller
{
    public function index(): View
    {
        return view('admin.companies', [
            'pendingCompanies' => Company::where('status', Company::STATUS_PENDING)
                ->with('hrUsers')
                ->orderBy('created_at')
                ->get(),
            'reviewedCompanies' => Company::whereIn('status', [Company::STATUS_APPROVED, Company::STATUS_REJECTED])
                ->with('hrUsers')
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function approve(Request $request, Company $company): RedirectResponse
    {
        abort_unless($company->isPending(), 404, 'This application was already reviewed.');

        DB::transaction(function () use ($company): void {
            $company->update([
                'status' => Company::STATUS_APPROVED,
                'is_active' => true,
            ]);

            // Activate every HR account under the company (currently the one
            // who applied). Email verification picks up from here.
            $company->hrUsers()->update(['is_active' => true]);
            $company->hrUsers->each(fn (User $hr) => event(new Registered($hr)));
        });

        return back()->with('status', "{$company->name} approved. Their HR can now sign in.");
    }

    public function reject(Request $request, Company $company): RedirectResponse
    {
        abort_unless($company->isPending(), 404, 'This application was already reviewed.');

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $company->update([
            'status' => Company::STATUS_REJECTED,
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('status', "{$company->name} rejected.");
    }
}
