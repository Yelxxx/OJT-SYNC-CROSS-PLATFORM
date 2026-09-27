<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Route the authenticated user to their role-specific dashboard.
     */
    public function index(): RedirectResponse
    {
        return match (Auth::user()->role) {
            User::ROLE_STUDENT => redirect()->route('student.dashboard'),
            User::ROLE_COORDINATOR => redirect()->route('coordinator.dashboard'),
            User::ROLE_HR => redirect()->route('hr.dashboard'),
            User::ROLE_ADMIN => redirect()->route('admin.companies.index'),
            default => abort(403),
        };
    }
}
