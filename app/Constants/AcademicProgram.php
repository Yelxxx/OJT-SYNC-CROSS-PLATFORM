<?php

namespace App\Constants;

class AcademicProgram
{
    public const PROGRAM_ALL = 'ALL';

    /**
     * Grouped academic programs by university college/discipline.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'General / Universal' => [
                self::PROGRAM_ALL => 'Open to All Programs',
            ],
            'College of Computing & Information Technologies' => [
                'BSIT' => 'BS in Information Technology (BSIT)',
                'BSCS' => 'BS in Computer Science (BSCS)',
                'BSIS' => 'BS in Information Systems (BSIS)',
                'BSEMC' => 'BS in Entertainment & Multimedia Computing',
            ],
            'College of Business & Management' => [
                'BSA' => 'BS in Accountancy (BSA)',
                'BSBA-HR' => 'BSBA - Human Resource Management',
                'BSBA-MM' => 'BSBA - Marketing Management',
                'BSBA-FM' => 'BSBA - Financial Management',
                'BSBA-OM' => 'BSBA - Operations Management',
                'BSE' => 'BS in Entrepreneurship (BSE)',
            ],
            'College of Hospitality & Tourism Management' => [
                'BSHM' => 'BS in Hospitality Management (Hotel/Restaurant)',
                'BSTM' => 'BS in Tourism Management',
            ],
            'College of Engineering & Architecture' => [
                'BSCE' => 'BS in Civil Engineering',
                'BSCpE' => 'BS in Computer Engineering',
                'BSEE' => 'BS in Electrical Engineering',
                'BSECE' => 'BS in Electronics Engineering',
                'BSME' => 'BS in Mechanical Engineering',
                'BSIE' => 'BS in Industrial Engineering',
                'BSARCH' => 'BS in Architecture',
            ],
            'College of Arts, Sciences & Education' => [
                'BACOMM' => 'BA in Communication',
                'AB-PSYCH' => 'AB in Psychology',
                'BS-PSYCH' => 'BS in Psychology',
                'BSED' => 'Bachelor of Secondary Education',
                'BEED' => 'Bachelor of Elementary Education',
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

