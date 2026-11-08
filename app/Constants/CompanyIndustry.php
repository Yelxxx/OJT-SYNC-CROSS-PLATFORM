<?php

namespace App\Constants;

class CompanyIndustry
{
    public const HOSPITALITY_TOURISM = 'hospitality_tourism';
    public const IT_TECH = 'it_technology';
    public const BUSINESS_FINANCE = 'business_finance';
    public const ENGINEERING_CONSTRUCTION = 'engineering_construction';
    public const MEDIA_COMMUNICATION = 'media_communication';
    public const GENERAL_DIVERSIFIED = 'general_diversified';

    /**
     * All industry sectors with labels, relevant colleges, and target courses.
     *
     * @return array<string, array{label: string, college: string, default_programs: list<string>}>
     */
    public static function all(): array
    {
        return [
            self::HOSPITALITY_TOURISM => [
                'label' => 'Hospitality, Tourism, Food & Beverage (Hotels, Resorts, Restaurants, Events, Travel)',
                'college' => 'College of Hospitality & Tourism Management (CTHM)',
                'default_programs' => ['BSHM', 'BSTM'],
            ],
            self::IT_TECH => [
                'label' => 'Information Technology, Software, Telecommunications & BPO Tech',
                'college' => 'College of Computing & IT (CCIT)',
                'default_programs' => ['BSIT', 'BSCS', 'BSIS', 'BSEMC', 'BSCpE'],
            ],
            self::BUSINESS_FINANCE => [
                'label' => 'Business, Accounting, Banking, Marketing & Human Resources',
                'college' => 'College of Business & Management (CBM)',
                'default_programs' => ['BSA', 'BSBA-HR', 'BSBA-MM', 'BSBA-FM', 'BSBA-OM', 'BSE'],
            ],
            self::ENGINEERING_CONSTRUCTION => [
                'label' => 'Engineering, Construction, Manufacturing & Architecture',
                'college' => 'College of Engineering & Architecture (CEA)',
                'default_programs' => ['BSCE', 'BSARCH', 'BSEE', 'BSECE', 'BSME', 'BSIE', 'BSCpE'],
            ],
            self::MEDIA_COMMUNICATION => [
                'label' => 'Media, Public Relations, Advertising, Education & Social Sciences',
                'college' => 'College of Arts, Sciences & Education (CASE)',
                'default_programs' => ['BACOMM', 'AB-PSYCH', 'BS-PSYCH', 'BSED', 'BEED'],
            ],
            self::GENERAL_DIVERSIFIED => [
                'label' => 'General Corporate / Multi-Industry / Public Sector (Open to All Fields)',
                'college' => 'Multi-Disciplinary / Universal',
                'default_programs' => ['ALL'],
            ],
        ];
    }

    /**
     * Get label for an industry key.
     */
    public static function label(?string $key): string
    {
        if (! $key) {
            return 'General / Multi-Industry';
        }

        return self::all()[$key]['label'] ?? 'General / Multi-Industry';
    }

    /**
     * Get default eligible academic programs for an industry sector.
     *
     * @return list<string>
     */
    public static function defaultPrograms(?string $key): array
    {
        if (! $key) {
            return ['ALL'];
        }

        return self::all()[$key]['default_programs'] ?? ['ALL'];
    }

    /**
     * Get primary college name for an industry sector.
     */
    public static function college(?string $key): string
    {
        if (! $key) {
            return 'Multi-Disciplinary';
        }

        return self::all()[$key]['college'] ?? 'Multi-Disciplinary';
    }
}

