<?php

namespace App\Constants;

class CompanyIndustry
{
    public const HOSPITALITY_TOURISM = 'hospitality_tourism';
    public const IT_TECH = 'it_technology';
    public const BUSINESS_FINANCE = 'business_finance';
    public const ENGINEERING_CONSTRUCTION = 'engineering_construction';
    public const AGRICULTURE_FOOD_ENV = 'agriculture_food_env';
    public const CRIMINOLOGY_SECURITY = 'criminology_security';
    public const EDUCATION_ACADEMIC = 'education_academic';
    public const MEDIA_COMM_SOCIAL = 'media_comm_social';
    public const SPORTS_WELLNESS = 'sports_wellness';
    public const GENERAL_DIVERSIFIED = 'general_diversified';

    /**
     * All industry sectors with labels, relevant colleges, and target courses for CvSU Main Campus.
     *
     * @return array<string, array{label: string, college: string, default_programs: list<string>}>
     */
    public static function all(): array
    {
        return [
            self::HOSPITALITY_TOURISM => [
                'label' => 'Hospitality, Tourism, Food & Beverage (Hotels, Resorts, Restaurants, Travel & Events)',
                'college' => 'College of Tourism and Hospitality Management (CTHM)',
                'default_programs' => ['BSHM', 'BSTM'],
            ],
            self::IT_TECH => [
                'label' => 'Information Technology, Software Development, Telecom & Tech Solutions',
                'college' => 'College of Engineering & Information Technology (CEIT)',
                'default_programs' => ['BSIT', 'BSCS', 'BSCpE', 'BSECE', 'BSIndTech'],
            ],
            self::BUSINESS_FINANCE => [
                'label' => 'Business, Banking, Accounting, Marketing, HR & Office Administration',
                'college' => 'College of Economics, Management & Development Studies (CEMDS)',
                'default_programs' => ['BSA', 'BSBA-FM', 'BSBA-HRM', 'BSBA-MM', 'BSBA-OM', 'BSBA-SM', 'BSDM', 'BSECON', 'BSIS-INT', 'BSOA'],
            ],
            self::ENGINEERING_CONSTRUCTION => [
                'label' => 'Engineering, Construction, Civil Works, Architecture & Manufacturing',
                'college' => 'College of Engineering & Information Technology (CEIT)',
                'default_programs' => ['BSCE', 'BSARCH', 'BSABE', 'BSEE', 'BSECE', 'BSIE', 'BSCpE', 'BSIndTech'],
            ],
            self::AGRICULTURE_FOOD_ENV => [
                'label' => 'Agriculture, Farming, Agribusiness, Food Technology & Environmental Science',
                'college' => 'College of Agriculture, Food, Environment & Natural Resources (CAFENR)',
                'default_programs' => ['BAE', 'BSA-AGRI', 'BSES', 'BSFT'],
            ],
            self::CRIMINOLOGY_SECURITY => [
                'label' => 'Law Enforcement, Security Management, Public Safety & Investigation Agencies',
                'college' => 'College of Criminal Justice (CCJ)',
                'default_programs' => ['BSCrim', 'BSISM'],
            ],
            self::EDUCATION_ACADEMIC => [
                'label' => 'Education, Academic Institutions, Training, Tutorial & Review Centers',
                'college' => 'College of Education (CED)',
                'default_programs' => ['BECE', 'BEED', 'BSED', 'BSNEd', 'BTLE'],
            ],
            self::MEDIA_COMM_SOCIAL => [
                'label' => 'Media, Journalism, Broadcasting, Public Relations, Psychology & Social Work',
                'college' => 'College of Arts and Sciences (CAS)',
                'default_programs' => ['BACOMM', 'BAELS', 'BAJOURN', 'BAPOLSCI', 'BSAM', 'BSBIO', 'BSDC', 'BSPSYCH', 'BSSW'],
            ],
            self::SPORTS_WELLNESS => [
                'label' => 'Sports Facilities, Fitness Centers, Coaching & Athletic Wellness',
                'college' => 'College of Sports, Physical Education & Recreation (CSPEAR)',
                'default_programs' => ['BPED', 'BSESS'],
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
