<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyApplicationSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CompanyRegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.company-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Company details
            'company_name' => ['required', 'string', 'max:255', 'unique:'.Company::class.',name'],
            'company_address' => ['required', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_website' => ['nullable', 'string', 'max:255'],
            'company_description' => ['required', 'string', 'max:2000'],

            // HR account details
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $company = DB::transaction(function () use ($validated): Company {
            $company = Company::create([
                'name' => $validated['company_name'],
                'address' => $validated['company_address'],
                'contact_person' => $validated['contact_person'],
                'contact_email' => $validated['email'],
                'phone' => $validated['phone'] ?? $validated['company_phone'] ?? null,
                'website' => $validated['company_website'],
                'description' => $validated['company_description'],
                // is_active stays false until an admin approves; status
                // records whether the application is pending or rejected.
                'is_active' => false,
                'status' => Company::STATUS_PENDING,
            ]);

            User::create([
                'name' => $validated['contact_person'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_HR,
                'is_active' => false, // login blocked until approval
                'company_id' => $company->id,
            ]);

            return $company;
        });

        User::query()->where('role', User::ROLE_ADMIN)->each(
            fn (User $admin) => $admin->notify(new CompanyApplicationSubmitted($company))
        );

        // No login here: the account is intentionally inert until approved.
        return redirect()
            ->route('company.register')
            ->with('company_application_status', 'pending');
    }
}
