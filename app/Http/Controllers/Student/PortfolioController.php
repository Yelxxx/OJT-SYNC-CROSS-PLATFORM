<?php

namespace App\Http\Controllers\Student;

use App\Constants\AcademicProgram;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        $student = Auth::user()->studentProfile;
        abort_if(! $student, 403, 'Student profile not found.');

        $portfolio = $student->competencyPortfolio();

        return view('student.portfolio.index', [
            'student' => $student,
            'portfolio' => $portfolio,
            'programLabel' => AcademicProgram::label($student->program),
        ]);
    }

    public function print(): View
    {
        $student = Auth::user()->studentProfile;
        abort_if(! $student, 403, 'Student profile not found.');

        $portfolio = $student->competencyPortfolio();

        return view('student.portfolio.print', [
            'student' => $student,
            'portfolio' => $portfolio,
            'programLabel' => AcademicProgram::label($student->program),
        ]);
    }
}
