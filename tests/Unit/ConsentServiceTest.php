<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Models\ConsentType;
use Blamodex\Consent\Models\ConsentSource;
use Blamodex\Consent\Services\ConsentService;
use Blamodex\Consent\Tests\Fixtures\DummyConsentUser;
use Blamodex\Consent\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConsentServiceTest extends TestCase
{
    private ConsentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConsentService();
    }

    #[Test]
    public function it_creates_new_consent_when_none_exists(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $consentSource = ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'I agree to receive marketing emails',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(Consent::class, $results[0]);
        $this->assertEquals('consented', $results[0]->status);
        $this->assertEquals($consentType->id, $results[0]->consent_type_id);
        $this->assertEquals($consentSource->id, $results[0]->consent_source_id);
        $this->assertEquals('I agree to receive marketing emails', $results[0]->consent_text);
        $this->assertNotNull($results[0]->consented_at);
        $this->assertNull($results[0]->revoked_at);
    }

    #[Test]
    public function it_updates_existing_consented_record_with_new_source(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $oldSource = ConsentSource::factory()->create(['slug' => 'web-form']);
        $newSource = ConsentSource::factory()->create(['slug' => 'mobile-app']);

        $existingConsent = Consent::factory()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'consent_source_id' => $oldSource->id,
            'status' => 'consented',
        ]);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Updated consent text',
                    'source' => 'mobile-app',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(1, $results);
        $this->assertEquals($existingConsent->id, $results[0]->id);
        $this->assertEquals($newSource->id, $results[0]->consent_source_id);
        $this->assertEquals('Updated consent text', $results[0]->consent_text);
    }

    #[Test]
    public function it_preserves_consent_when_source_hasnt_changed(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $existingConsent = Consent::factory()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'consent_source_id' => $source->id,
            'status' => 'consented',
            'consent_text' => 'Original text',
        ]);

        $originalConsentedAt = $existingConsent->consented_at;

        $attributes = [
            'consents' => [
                [
                    'text' => 'Original text',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(1, $results);
        $this->assertEquals($existingConsent->id, $results[0]->id);
        $this->assertEquals($source->id, $results[0]->consent_source_id);
    }

    #[Test]
    public function it_soft_deletes_revoked_consent_and_creates_new_consented_record(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $revokedConsent = Consent::factory()->revoked()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'consent_source_id' => $source->id,
        ]);

        $attributes = [
            'consents' => [
                [
                    'text' => 'I consent again',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(1, $results);
        $this->assertNotEquals($revokedConsent->id, $results[0]->id);
        $this->assertEquals('consented', $results[0]->status);
        $this->assertNotNull($results[0]->consented_at);
        
        // Verify the old consent was soft-deleted
        $this->assertSoftDeleted('consents', ['id' => $revokedConsent->id]);
    }

    #[Test]
    public function it_revokes_existing_consent(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $originalSource = ConsentSource::factory()->create(['slug' => 'web-form']);
        $revocationSource = ConsentSource::factory()->create(['slug' => 'mobile-app']);

        $existingConsent = Consent::factory()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'consent_source_id' => $originalSource->id,
            'status' => 'consented',
        ]);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Revocation text',
                    'source' => 'mobile-app',
                    'slug' => 'email-marketing',
                    'state' => 'revoked',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(1, $results);
        $this->assertEquals($existingConsent->id, $results[0]->id);
        $this->assertEquals('revoked', $results[0]->status);
        $this->assertNotNull($results[0]->revoked_at);
        // Source should be preserved from original consent
        $this->assertEquals($originalSource->id, $results[0]->consent_source_id);
    }

    #[Test]
    public function it_creates_revoked_record_for_non_existent_consent(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Revoking consent I never gave',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'revoked',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(1, $results);
        $this->assertEquals('revoked', $results[0]->status);
        $this->assertNotNull($results[0]->revoked_at);
        // Note: consented_at may be set by the model's boot method, but the record is still revoked
    }

    #[Test]
    public function it_skips_record_when_consent_type_does_not_exist(): void
    {
        $user = DummyConsentUser::factory()->create();
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Some consent',
                    'source' => 'web-form',
                    'slug' => 'non-existent-type',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
        $this->assertEquals(0, Consent::count());
    }

    #[Test]
    public function it_skips_record_when_consent_source_does_not_exist(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Some consent',
                    'source' => 'non-existent-source',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
        $this->assertEquals(0, Consent::count());
    }

    #[Test]
    public function it_skips_record_when_state_is_missing(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Some consent',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    // Missing 'state'
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
        $this->assertEquals(0, Consent::count());
    }

    #[Test]
    public function it_processes_multiple_consent_records(): void
    {
        $user = DummyConsentUser::factory()->create();
        $emailType = ConsentType::factory()->create(['slug' => 'email-marketing']);
        $smsType = ConsentType::factory()->create(['slug' => 'sms-marketing']);
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Email consent',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
                [
                    'text' => 'SMS consent',
                    'source' => 'web-form',
                    'slug' => 'sms-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(2, $results);
        $this->assertEquals('consented', $results[0]->status);
        $this->assertEquals('consented', $results[1]->status);
        $this->assertEquals(2, Consent::count());
    }

    #[Test]
    public function it_handles_empty_consents_array(): void
    {
        $user = DummyConsentUser::factory()->create();

        $attributes = [
            'consents' => [],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_handles_missing_consents_key(): void
    {
        $user = DummyConsentUser::factory()->create();

        $attributes = [];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_skips_record_when_slug_key_is_missing(): void
    {
        $user = DummyConsentUser::factory()->create();
        $source = ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Some consent',
                    'source' => 'web-form',
                    // Missing 'slug' key
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
        $this->assertEquals(0, Consent::count());
    }

    #[Test]
    public function it_skips_record_when_source_key_is_missing(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Some consent',
                    // Missing 'source' key
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];

        $results = $this->service->createConsents($user, $attributes);

        $this->assertCount(0, $results);
        $this->assertEquals(0, Consent::count());
    }

    #[Test]
    public function it_formats_consents_for_frontend(): void
    {
        $consentType1 = ConsentType::factory()->create(['name' => 'Email Marketing']);
        $consentType2 = ConsentType::factory()->create(['name' => 'SMS Notifications']);
        
        $consent1 = Consent::factory()->create([
            'consent_type_id' => $consentType1->id,
            'status' => 'consented',
        ]);

        $consent2 = Consent::factory()->revoked()->create([
            'consent_type_id' => $consentType2->id,
        ]);

        $formatted = $this->service->formatConsentsFrontend([$consent1, $consent2]);

        $this->assertArrayHasKey('consents', $formatted);
        $this->assertCount(2, $formatted['consents']);
        
        $this->assertEquals('Email Marketing', $formatted['consents'][0]['type']);
        $this->assertEquals('CONSENTED', $formatted['consents'][0]['status']);
        
        $this->assertEquals('SMS Notifications', $formatted['consents'][1]['type']);
        $this->assertEquals('REVOKED', $formatted['consents'][1]['status']);
    }

    #[Test]
    public function it_formats_empty_consents_array_for_frontend(): void
    {
        $formatted = $this->service->formatConsentsFrontend([]);

        $this->assertArrayHasKey('consents', $formatted);
        $this->assertCount(0, $formatted['consents']);
    }

    #[Test]
    public function it_returns_true_when_user_has_consented(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);

        Consent::factory()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'status' => 'consented',
        ]);

        $result = $this->service->consentedToByConsentable($user, 'email-marketing');

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_no_consent_exists(): void
    {
        $user = DummyConsentUser::factory()->create();
        ConsentType::factory()->create(['slug' => 'email-marketing']);

        $result = $this->service->consentedToByConsentable($user, 'email-marketing');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_only_revoked_consent_exists(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);

        Consent::factory()->revoked()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
        ]);

        $result = $this->service->consentedToByConsentable($user, 'email-marketing');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_consent_type_does_not_exist(): void
    {
        $user = DummyConsentUser::factory()->create();

        $result = $this->service->consentedToByConsentable($user, 'non-existent-type');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_only_checks_consents_for_specific_user(): void
    {
        $user1 = DummyConsentUser::factory()->create();
        $user2 = DummyConsentUser::factory()->create();
        $consentType = ConsentType::factory()->create(['slug' => 'email-marketing']);

        // User2 has consented
        Consent::factory()->create([
            'consentable_id' => $user2->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'status' => 'consented',
        ]);

        // User1 has not consented
        $result = $this->service->consentedToByConsentable($user1, 'email-marketing');

        $this->assertFalse($result);
    }
}
