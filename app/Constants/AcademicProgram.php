<?php

namespace App\Constants;

class AcademicProgram
{
    public const PROGRAM_ALL = 'ALL';

    /**
     * Grouped academic programs by college offered at Cavite State University (CvSU - Main Campus).
     * Excludes non-OJT and strictly hospital/clinical residency programs (Medicine, Nursing/MedTech, VetMed).
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'General / Universal' => [
                self::PROGRAM_ALL => 'Open to All Programs',
            ],
            'College of Agriculture, Food, Environment & Natural Resources (CAFENR)' => [
                'BAE' => 'Bachelor in Agricultural Entrepreneurship (BAE)',
                'BSA-AGRI' => 'Bachelor of Science in Agriculture (BSA)',
                'BSES' => 'Bachelor of Science in Environmental Science (BSES)',
                'BSFT' => 'Bachelor of Science in Food Technology (BSFT)',
            ],
            'College of Arts and Sciences (CAS)' => [
                'BACOMM' => 'Bachelor of Arts in Communication (BACOMM)',
                'BAELS' => 'Bachelor of Arts in English Language Studies (BAELS)',
                'BAJOURN' => 'Bachelor of Arts in Journalism (BAJOURN)',
                'BAPOLSCI' => 'Bachelor of Arts in Political Science (BAPOLSCI)',
                'BSAM' => 'Bachelor of Science in Applied Mathematics (BSAM)',
                'BSBIO' => 'Bachelor of Science in Biology (BSBIO)',
                'BSDC' => 'Bachelor of Science in Development Communication (BSDC)',
                'BSPSYCH' => 'Bachelor of Science in Psychology (BSPSYCH)',
                'BSSW' => 'Bachelor of Science in Social Work (BSSW)',
            ],
            'College of Criminal Justice (CCJ)' => [
                'BSCrim' => 'Bachelor of Science in Criminology (BSCrim)',
                'BSISM' => 'Bachelor of Science in Industrial Security Management (BSISM)',
            ],
            'College of Economics, Management & Development Studies (CEMDS)' => [
                'BSA' => 'Bachelor of Science in Accountancy (BSA)',
                'BSBA-FM' => 'BSBA - Financial Management',
                'BSBA-HRM' => 'BSBA - Human Resource Management',
                'BSBA-MM' => 'BSBA - Marketing Management',
                'BSBA-OM' => 'BSBA - Operations Management',
                'BSBA-SM' => 'BSBA - Service Management',
                'BSDM' => 'Bachelor of Science in Development Management (BSDM)',
                'BSECON' => 'Bachelor of Science in Economics (BSECON)',
                'BSIS-INT' => 'Bachelor of Science in International Studies (BSIS)',
                'BSOA' => 'Bachelor of Science in Office Administration (BSOA)',
            ],
            'College of Education (CED)' => [
                'BECE' => 'Bachelor of Early Childhood Education (BECE)',
                'BEED' => 'Bachelor of Elementary Education (BEED)',
                'BSED' => 'Bachelor of Secondary Education (BSED)',
                'BSNEd' => 'Bachelor of Special Needs Education (BSNEd)',
                'BTLE' => 'Bachelor of Technology and Livelihood Education (BTLE)',
            ],
            'College of Tourism and Hospitality Management (CTHM)' => [
                'BSHM' => 'Bachelor of Science in Hospitality Management (BSHM)',
                'BSTM' => 'Bachelor of Science in Tourism Management (BSTM)',
            ],
            'College of Engineering & Information Technology (CEIT)' => [
                'BSABE' => 'Bachelor of Science in Agricultural & Biosystems Engineering (BSABE)',
                'BSARCH' => 'Bachelor of Science in Architecture (BSARCH)',
                'BSCE' => 'Bachelor of Science in Civil Engineering (BSCE)',
                'BSCpE' => 'Bachelor of Science in Computer Engineering (BSCpE)',
                'BSCS' => 'Bachelor of Science in Computer Science (BSCS)',
                'BSEE' => 'Bachelor of Science in Electrical Engineering (BSEE)',
                'BSECE' => 'Bachelor of Science in Electronics & Communication Engineering (BSECE)',
                'BSIE' => 'Bachelor of Science in Industrial Engineering (BSIE)',
                'BSIT' => 'Bachelor of Science in Information Technology (BSIT)',
                'BSIndTech' => 'Bachelor of Science in Industrial Technology (BSIndTech)',
            ],
            'College of Sports, Physical Education & Recreation (CSPEAR)' => [
                'BPED' => 'Bachelor of Physical Education (BPED)',
                'BSESS' => 'Bachelor of Science in Exercise and Sports Sciences (BSESS)',
            ],
        ];
    }

    /**
     * Get a flat array of [code => label].
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $flat = [];
        foreach (self::grouped() as $group => $programs) {
            foreach ($programs as $code => $label) {
                $flat[$code] = $label;
            }
        }
        return $flat;
    }

    /**
     * Get the descriptive label for a program code.
     */
    public static function label(?string $code): string
    {
        if (! $code) {
            return 'Unspecified';
        }

        $all = self::all();
        return $all[$code] ?? $code;
    }
}
