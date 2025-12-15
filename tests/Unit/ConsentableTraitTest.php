<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Tests\TestCase;
use Blamodex\Consent\Tests\Fixtures\DummyConsentUser;
use PHPUnit\Framework\Attributes\Test;

class ConsentableTraitTest extends TestCase
{
    #[Test]
    public function it_has_morph_many_consents_relationship(): void
    {
        $user = DummyConsentUser::factory()->create();

        $consent1 = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
        ]);

        $consent2 = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
        ]);

        $this->assertCount(2, $user->consents);
        $this->assertTrue($user->consents->contains($consent1));
        $this->assertTrue($user->consents->contains($consent2));
    }

    #[Test]
    public function it_returns_empty_collection_when_no_consents(): void
    {
        $user = DummyConsentUser::factory()->create();

        $this->assertCount(0, $user->consents);
    }

    #[Test]
    public function it_can_filter_consents_by_type(): void
    {
        $user = DummyConsentUser::factory()->create();
        
        $marketingConsent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
            'consent_type_id' => 1,
        ]);

        $analyticsConsent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
            'consent_type_id' => 2,
        ]);

        $marketingConsents = $user->consents()->where('consent_type_id', 1)->get();

        $this->assertCount(1, $marketingConsents);
        $this->assertTrue($marketingConsents->contains($marketingConsent));
        $this->assertFalse($marketingConsents->contains($analyticsConsent));
    }

    #[Test]
    public function it_can_filter_active_consents(): void
    {
        $user = DummyConsentUser::factory()->create();
        
        $activeConsent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
            'status' => 'consented',
            'revoked_at' => null,
        ]);

        $revokedConsent = Consent::factory()->revoked()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
        ]);

        $activeConsents = $user->consents()->where('status', 'consented')->whereNull('revoked_at')->get();

        $this->assertCount(1, $activeConsents);
        $this->assertTrue($activeConsents->contains($activeConsent));
        $this->assertFalse($activeConsents->contains($revokedConsent));
    }

    #[Test]
    public function it_only_returns_consents_for_specific_consentable(): void
    {
        $user1 = DummyConsentUser::factory()->create();
        $user2 = DummyConsentUser::factory()->create();
        
        $user1Consent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user1->id,
        ]);

        $user2Consent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user2->id,
        ]);

        $this->assertCount(1, $user1->consents);
        $this->assertTrue($user1->consents->contains($user1Consent));
        $this->assertFalse($user1->consents->contains($user2Consent));

        $this->assertCount(1, $user2->consents);
        $this->assertTrue($user2->consents->contains($user2Consent));
        $this->assertFalse($user2->consents->contains($user1Consent));
    }

    // ==========================================
    // createConsents() Method
    // ==========================================

    #[Test]
    public function it_can_create_consents_through_trait(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);
        $consentSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'web-form']);

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

        $results = $user->createConsents($attributes);

        $this->assertCount(1, $results);
        $this->assertInstanceOf(Consent::class, $results[0]);
        $this->assertEquals('consented', $results[0]->status);
        $this->assertEquals($user->id, $results[0]->consentable_id);
        $this->assertEquals(DummyConsentUser::class, $results[0]->consentable_type);
    }

    #[Test]
    public function it_can_create_multiple_consents_through_trait(): void
    {
        $user = DummyConsentUser::factory()->create();
        $emailType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);
        $smsType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'sms-marketing']);
        $source = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'web-form']);

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

        $results = $user->createConsents($attributes);

        $this->assertCount(2, $results);
        $this->assertEquals(2, $user->consents()->count());
    }

    #[Test]
    public function it_can_revoke_consents_through_trait(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);
        $consentSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'web-form']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Revoking consent',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'revoked',
                ],
            ],
        ];

        $results = $user->createConsents($attributes);

        $this->assertCount(1, $results);
        $this->assertEquals('revoked', $results[0]->status);
        $this->assertNotNull($results[0]->revoked_at);
    }

    #[Test]
    public function it_returns_empty_array_when_no_consents_provided(): void
    {
        $user = DummyConsentUser::factory()->create();

        $attributes = [
            'consents' => [],
        ];

        $results = $user->createConsents($attributes);

        $this->assertCount(0, $results);
    }

    #[Test]
    public function it_delegates_to_consent_service_for_create(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'test-consent']);
        $consentSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'test-source']);

        $attributes = [
            'consents' => [
                [
                    'text' => 'Test',
                    'source' => 'test-source',
                    'slug' => 'test-consent',
                    'state' => 'consented',
                ],
            ],
        ];

        // This tests that the trait properly delegates to ConsentService
        $results = $user->createConsents($attributes);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    // ==========================================
    // consentedTo() Method
    // ==========================================

    #[Test]
    public function it_returns_true_when_user_has_consented(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);

        Consent::factory()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'status' => 'consented',
        ]);

        $this->assertTrue($user->consentedTo('email-marketing'));
    }

    #[Test]
    public function it_returns_false_when_user_has_not_consented(): void
    {
        $user = DummyConsentUser::factory()->create();
        \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);

        $this->assertFalse($user->consentedTo('email-marketing'));
    }

    #[Test]
    public function it_returns_false_when_consent_was_revoked(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);

        Consent::factory()->revoked()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
        ]);

        $this->assertFalse($user->consentedTo('email-marketing'));
    }

    #[Test]
    public function it_returns_false_when_consent_type_does_not_exist(): void
    {
        $user = DummyConsentUser::factory()->create();

        $this->assertFalse($user->consentedTo('non-existent-consent'));
    }

    #[Test]
    public function it_checks_consent_for_specific_user_only(): void
    {
        $user1 = DummyConsentUser::factory()->create();
        $user2 = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);

        // User2 has consented
        Consent::factory()->create([
            'consentable_id' => $user2->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'status' => 'consented',
        ]);

        // User1 has not consented
        $this->assertFalse($user1->consentedTo('email-marketing'));
        $this->assertTrue($user2->consentedTo('email-marketing'));
    }

    #[Test]
    public function it_delegates_to_consent_service_for_consented_check(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'test-consent']);

        Consent::factory()->create([
            'consentable_id' => $user->id,
            'consentable_type' => DummyConsentUser::class,
            'consent_type_id' => $consentType->id,
            'status' => 'consented',
        ]);

        // This tests that the trait properly delegates to ConsentService
        $result = $user->consentedTo('test-consent');

        $this->assertIsBool($result);
        $this->assertTrue($result);
    }

    // ==========================================
    // Integration Tests
    // ==========================================

    #[Test]
    public function it_can_create_and_check_consent_in_workflow(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);
        $consentSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'web-form']);

        // User has not consented yet
        $this->assertFalse($user->consentedTo('email-marketing'));

        // User gives consent
        $attributes = [
            'consents' => [
                [
                    'text' => 'I agree',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ];
        $user->createConsents($attributes);

        // User has now consented
        $this->assertTrue($user->consentedTo('email-marketing'));
    }

    #[Test]
    public function it_can_revoke_and_check_consent_in_workflow(): void
    {
        $user = DummyConsentUser::factory()->create();
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);
        $consentSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'web-form']);

        // User gives consent
        $user->createConsents([
            'consents' => [
                [
                    'text' => 'I agree',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'consented',
                ],
            ],
        ]);

        $this->assertTrue($user->consentedTo('email-marketing'));

        // User revokes consent
        $user->createConsents([
            'consents' => [
                [
                    'text' => 'I no longer agree',
                    'source' => 'web-form',
                    'slug' => 'email-marketing',
                    'state' => 'revoked',
                ],
            ],
        ]);

        $this->assertFalse($user->consentedTo('email-marketing'));
    }

    // ==========================================
    // claimConsents() Method
    // ==========================================

    #[Test]
    public function it_can_claim_consents_from_transferable_model(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();
        
        $emailType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'email-marketing']);
        $dataType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'data-processing']);
        $webSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'web-form']);
        $mobileSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'mobile-app']);

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
                [
                    'slug' => 'data-processing',
                    'source' => 'mobile-app',
                    'text' => 'Data consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        // Act - Use the Consentable trait's claimConsents method
        $claimed = $targetUser->claimConsents($sourceUser);

        // Assert
        $this->assertCount(2, $claimed);
        $this->assertCount(0, $sourceUser->fresh()->consents);
        $this->assertCount(2, $targetUser->fresh()->consents);
        
        // Verify the consents now belong to target user
        foreach ($claimed as $consent) {
            $this->assertEquals($targetUser->id, $consent->consentable_id);
            $this->assertEquals($sourceUser->id, $consent->transferable_id);
            $this->assertNotNull($consent->transferred_at);
        }
    }

    #[Test]
    public function it_returns_empty_array_when_claiming_from_user_with_no_consents(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $claimed = $targetUser->claimConsents($sourceUser);

        $this->assertIsArray($claimed);
        $this->assertCount(0, $claimed);
    }

    #[Test]
    public function it_delegates_to_consent_service_for_claim(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();
        
        $consentType = \Blamodex\Consent\Models\ConsentType::factory()->create(['slug' => 'test-consent']);
        $consentSource = \Blamodex\Consent\Models\ConsentSource::factory()->create(['slug' => 'test-source']);

        $payload = [
            'consents' => [
                [
                    'slug' => 'test-consent',
                    'source' => 'test-source',
                    'text' => 'Test consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        // This tests that the trait properly delegates to ConsentService
        $claimed = $targetUser->claimConsents($sourceUser);

        $this->assertIsArray($claimed);
        $this->assertCount(1, $claimed);
    }
}
