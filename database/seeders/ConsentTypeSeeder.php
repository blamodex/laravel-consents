<?php

namespace Blamodex\Consent\Database\Seeders;

use Blamodex\Consent\Models\ConsentType;
use Illuminate\Database\Seeder;

class ConsentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $consentTypes = [
            [
                'name' => 'Email Marketing',
                'slug' => 'email_marketing',
                'description' => 'Consent for receiving email marketing communications.',
            ],
            [
                'name' => 'SMS Marketing',
                'slug' => 'sms_marketing',
                'description' => 'Consent for receiving SMS marketing communications.',
            ],
            [
                'name' => 'First-Party Analytics',
                'slug' => 'first_party_analytics',
                'description' =>
                    'Consent for measuring site usage, page views, click interactions, and other first-party analytics activity.',
            ],
            [
                'name' => 'Personalization',
                'slug' => 'personalization',
                'description' =>
                    'Consent for personalizing on-site content, recommendations, and A/B testing based on user behavior.'
            ],
            [
                'name' => 'Cross-Site Tracking (Client Properties)',
                'slug' => 'cross_site_tracking',
                'description' =>
                    'Consent for cross-site tracking, confined to the client properties',
            ],
            [
                'name' => 'Session Stitching',
                'slug' => 'session_stitching',
                'description' => 'Consent to allow previously anonymous click and view activity '
                . 'to be retroactively associated with the identified user or session.”',
            ],
            [
                'name' => 'Location',
                'slug' => 'location',
                'description' =>
                    'Consent for collecting and using location data for providing location-based services and analytics.',
            ],
        ];

        foreach ($consentTypes as $type) {
            ConsentType::firstOrCreate($type);
        }
    }
}
