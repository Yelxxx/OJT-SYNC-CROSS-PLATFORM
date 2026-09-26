<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent guard: demo rows use fixed emails/student numbers, so
        // seeding a database that already has users would violate unique
        // constraints. Safe to run on every deploy/boot.
        if (User::exists()) {
            $this->command?->info('Database already has data; skipping demo seed.');

            return;
        }

        $password = 'password';

        
        $coordinator = User::create([
            'name' => 'Prof. Michael Palines',
            'email' => 'coordinator@cvsu.edu.ph',
            'password' => Hash::make($password),
            'role' => User::ROLE_COORDINATOR,
            'department' => 'College of Engineering and Information Technology',
        ]);

        
        $techNologia = Company::create([
            'name' => 'Technologia.',
            'address' => 'General Trias, Cavite',
            'latitude' => 14.386386,
            'longitude' => 120.881602,
            'contact_email' => 'hr@technologia.test',
            'phone' => '(046) 555-1234',
            'website' => 'https://technologia.test',
            'description' => 'Software development and IT services company operating inside the Cavite PEZA zone.',
            'is_active' => true,
        ]);

        $melissaDigital = Company::create([
            'name' => 'Melissa Digital Works',
            'address' => 'Brgy. Panungyanan, General Trias, Cavite',
            'latitude' => 14.382240,
            'longitude' => 120.881400,
            'contact_email' => 'careers@melissadigital.test',
            'phone' => '(046) 555-8899',
            'website' => 'https://melissadigital.test',
            'description' => 'Web design and digital marketing agency for local businesses.',
            'is_active' => true,
        ]);

        
        $hr = User::create([
            'name' => 'Kurt Harve',
            'email' => 'hr@technologia.test',
            'password' => Hash::make($password),
            'role' => User::ROLE_HR,
            'company_id' => $techNologia->id,
        ]);

        User::create([
            'name' => 'Melissa Digital Works HR',
            'email' => 'careers@melissadigital.test',
            'password' => Hash::make($password),
            'role' => User::ROLE_HR,
            'company_id' => $melissaDigital->id,
        ]);

        
        $studentData = [
            ['name' => 'Peter Parker', 'email' => 'peter.parker@cvsu.edu.ph', 'student_no' => '2024-1001', 'program' => 'BSIT', 'year_level' => '3rd Year'],
            ['name' => 'Mary Grace Piattos', 'email' => 'marygrace.piattos@cvsu.edu.ph', 'student_no' => '2024-1002', 'program' => 'BSIT', 'year_level' => '3rd Year'],
            ['name' => 'Jose Rizal Jr.', 'email' => 'jose.rizaljr@cvsu.edu.ph', 'student_no' => '2024-1003', 'program' => 'BSCS', 'year_level' => '4th Year'],
        ];

        $students = [];
        foreach ($studentData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($password),
                'role' => User::ROLE_STUDENT,
            ]);

            $students[] = Student::create([
                'user_id' => $user->id,
                'coordinator_id' => $coordinator->id,
                'company_id' => $techNologia->id,
                'student_no' => $data['student_no'],
                'program' => $data['program'],
                'year_level' => $data['year_level'],
                'required_hours' => 300,
                'status' => 'active',
            ]);
        }

        
        $job1 = JobPosting::create([
            'company_id' => $techNologia->id,
            'title' => 'Software Development Intern',
            'description' => 'Join our engineering team to build web applications for enterprise clients. You will work alongside senior developers on real production features.',
            'requirements' => "Currently enrolled in BSIT or BSCS\nBasic knowledge of PHP, JavaScript, or any programming language\nWilling to render 300 hours of OJT",
            'slots' => 3,
            'status' => 'open',
        ]);

        JobPosting::create([
            'company_id' => $techNologia->id,
            'title' => 'QA / Testing Intern',
            'description' => 'Assist our quality assurance team in testing software releases, writing test cases, and reporting bugs.',
            'requirements' => 'Attention to detail, familiarity with testing concepts is a plus.',
            'slots' => 2,
            'status' => 'open',
        ]);

        JobPosting::create([
            'company_id' => Company::where('name', 'Melissa Digital Works')->firstOrFail()->id,
            'title' => 'Web Design Intern',
            'description' => 'Help build modern, responsive websites for our local business clients using HTML, CSS, and WordPress.',
            'requirements' => 'Basic HTML/CSS knowledge, creativity, and a good eye for design.',
            'slots' => 2,
            'status' => 'open',
        ]);

        
        Application::create([
            'job_posting_id' => $job1->id,
            'student_id' => $students[0]->id,
            'cover_letter' => 'I am eager to apply my programming skills in a professional environment.',
            'status' => Application::STATUS_PENDING,
        ]);

        $job1->increment('applications_count');

        $this->command->info('Demo data seeded. Logins:');
        $this->command->info('  Coordinator: coordinator@cvsu.edu.ph / password');
        $this->command->info('  HR:          hr@technologia.test / password');
        $this->command->info('  HR:          careers@melissadigital.test / password');
        $this->command->info('  Student:     peter.parker@cvsu.edu.ph / password');
    }
}
