<?php

namespace Blamodex\Consent\Database\Seeders;

use Blamodex\Consent\Models\ConsentSource;
use Illuminate\Database\Seeder;
    
class ConsentSourceSeeder extends Seeder
{
    public function run(): void
    {
        $consentSources = [
            [
                'name' => 'Checkbox',
                'slug' => 'checkbox',
                'description' =>
                    'Consent obtained via a checkbox on a registration, signup, or other web form.',
            ],
            [
                'name' => 'Banner',
                'slug' => 'banner',
                'description' => 'Consent obtained via a cookie banner, privacy banner, or CMP popup.',
            ],
            [
                'name' => 'Imported',
                'slug' => 'imported',
                'description' => 'Consent imported from a legacy system or external data source.',
            ],
            [
                'name' => 'API',
                'slug' => 'api',
                'description' => 'Consent obtained through an API request from a trusted system or integration.',
            ],
            [
                'name' => 'Email Confirmation',
                'slug' => 'email',
                'description' => 'Consent obtained via an email confirmation link or verification action.',
            ],
            [
                'name' => 'Keyword',
                'slug' => 'keyword',
                'description' => 'Consent obtained when a user opts in by sending a specific SMS keyword.',
            ],
        ];

        foreach ($consentSources as $source) {
            ConsentSource::firstOrCreate($source);
        }
    }
}
