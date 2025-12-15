<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Models\ConsentSource;
use Blamodex\Consent\Models\ConsentType;
use Blamodex\Consent\Tests\Fixtures\DummyConsentUser;
use Blamodex\Consent\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TransferableTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed consent types and sources
        ConsentType::factory()->create(['slug' => 'email-marketing', 'name' => 'Email Marketing']);
        ConsentType::factory()->create(['slug' => 'sms-marketing', 'name' => 'SMS Marketing']);
        ConsentType::factory()->create(['slug' => 'data-processing', 'name' => 'Data Processing']);
        
        ConsentSource::factory()->create(['slug' => 'web-form', 'name' => 'Web Form']);
        ConsentSource::factory()->create(['slug' => 'mobile-app', 'name' => 'Mobile App']);
    }

    // ==========================================
    // transferConsentsTo() Method
    // ==========================================

    #[Test]
    public function it_can_transfer_consents_to_another_user(): void
    {
        $sourceUser = DummyConsentUser::factory()->create(['name' => 'Source User']);
        $targetUser = DummyConsentUser::factory()->create(['name' => 'Target User']);

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
                [
                    'slug' => 'sms-marketing',
                    'source' => 'web-form',
                    'text' => 'SMS consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        // Assert source user has 2 consents before transfer
        $this->assertCount(2, $sourceUser->consents);

        // Act - Use the trait method
        $transferred = $sourceUser->transferConsentsTo($targetUser);

        // Assert
        $this->assertCount(2, $transferred);
        $this->assertCount(0, $sourceUser->fresh()->consents);
        $this->assertCount(2, $targetUser->fresh()->consents);

        foreach ($transferred as $consent) {
            $this->assertEquals($targetUser->id, $consent->consentable_id);
            $this->assertEquals($targetUser->getMorphClass(), $consent->consentable_type);
            $this->assertEquals($sourceUser->id, $consent->transferable_id);
            $this->assertEquals($sourceUser->getMorphClass(), $consent->transferable_type);
            $this->assertNotNull($consent->transferred_at);
        }
    }

    #[Test]
    public function it_returns_empty_array_when_no_consents_to_transfer(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $transferred = $sourceUser->transferConsentsTo($targetUser);

        $this->assertIsArray($transferred);
        $this->assertCount(0, $transferred);
    }

    #[Test]
    public function it_preserves_consent_status_during_transfer(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
                [
                    'slug' => 'sms-marketing',
                    'source' => 'web-form',
                    'text' => 'SMS consent',
                    'state' => 'revoked',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        $transferred = $sourceUser->transferConsentsTo($targetUser);

        $consentedConsent = $transferred[0];
        $revokedConsent = $transferred[1];

        $this->assertEquals('consented', $consentedConsent->status);
        $this->assertNull($consentedConsent->revoked_at);

        $this->assertEquals('revoked', $revokedConsent->status);
        $this->assertNotNull($revokedConsent->revoked_at);
    }

    #[Test]
    public function it_preserves_consent_text_during_transfer(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $consentText = 'I agree to receive marketing emails about products and services.';
        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => $consentText,
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        $transferred = $sourceUser->transferConsentsTo($targetUser);

        $this->assertEquals($consentText, $transferred[0]->consent_text);
    }

    #[Test]
    public function it_preserves_consent_source_during_transfer(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'mobile-app',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);
        $originalSource = $sourceUser->consents->first()->consentSource;

        $transferred = $sourceUser->transferConsentsTo($targetUser);

        $this->assertEquals($originalSource->id, $transferred[0]->consent_source_id);
        $this->assertEquals('mobile-app', $transferred[0]->consentSource->slug);
    }

    #[Test]
    public function it_preserves_original_timestamps_during_transfer(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);
        $originalConsent = $sourceUser->consents->first();
        $originalConsentedAt = $originalConsent->consented_at;
        $originalCreatedAt = $originalConsent->created_at;

        // Wait a moment to ensure timestamps would differ if they changed
        sleep(1);

        $transferred = $sourceUser->transferConsentsTo($targetUser);

        $transferredConsent = $transferred[0];
        $this->assertEquals($originalConsentedAt->timestamp, $transferredConsent->consented_at->timestamp);
        $this->assertEquals($originalCreatedAt->timestamp, $transferredConsent->created_at->timestamp);
        $this->assertNotNull($transferredConsent->transferred_at);
    }

    #[Test]
    public function it_allows_multiple_sequential_transfers(): void
    {
        $user1 = DummyConsentUser::factory()->create(['name' => 'User 1']);
        $user2 = DummyConsentUser::factory()->create(['name' => 'User 2']);
        $user3 = DummyConsentUser::factory()->create(['name' => 'User 3']);

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $user1->createConsents($payload);

        // First transfer
        $user1->transferConsentsTo($user2);
        $consentAfterFirstTransfer = $user2->fresh()->consents->first();

        $this->assertEquals($user2->id, $consentAfterFirstTransfer->consentable_id);
        $this->assertEquals($user1->id, $consentAfterFirstTransfer->transferable_id);
        $this->assertNotNull($consentAfterFirstTransfer->transferred_at);

        // Second transfer
        $user2->transferConsentsTo($user3);
        $consentAfterSecondTransfer = $user3->fresh()->consents->first();

        // transferable tracks the IMMEDIATE previous owner, not the original
        $this->assertEquals($user3->id, $consentAfterSecondTransfer->consentable_id);
        $this->assertEquals($user2->id, $consentAfterSecondTransfer->transferable_id);
        $this->assertNotNull($consentAfterSecondTransfer->transferred_at);
    }

    #[Test]
    public function it_does_not_transfer_soft_deleted_consents(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);
        $consent = $sourceUser->consents->first();
        $consent->delete(); // Soft delete

        $transferred = $sourceUser->transferConsentsTo($targetUser);

        // Should not transfer soft-deleted consents
        $this->assertCount(0, $transferred);
        $this->assertCount(0, $targetUser->fresh()->consents);
    }

    #[Test]
    public function it_makes_target_user_able_to_check_consent_status(): void
    {
        $sourceUser = DummyConsentUser::factory()->create();
        $targetUser = DummyConsentUser::factory()->create();

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        $sourceUser->transferConsentsTo($targetUser);

        $this->assertFalse($sourceUser->consentedTo('email-marketing'));
        $this->assertTrue($targetUser->consentedTo('email-marketing'));
    }

    // ==========================================
    // transferredConsents() Relationship
    // ==========================================

    #[Test]
    public function it_has_transferred_consents_relationship(): void
    {
        $sourceUser = DummyConsentUser::factory()->create(['name' => 'Original Owner']);
        $targetUser = DummyConsentUser::factory()->create(['name' => 'New Owner']);

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        $sourceUser->transferConsentsTo($targetUser);

        // Check the relationship
        $transferredConsents = $sourceUser->transferredConsents;

        $this->assertCount(1, $transferredConsents);
        $this->assertEquals($sourceUser->id, $transferredConsents->first()->transferable_id);
    }

    #[Test]
    public function it_can_access_transferable_relationship_on_transferred_consent(): void
    {
        $sourceUser = DummyConsentUser::factory()->create(['name' => 'Original Owner']);
        $targetUser = DummyConsentUser::factory()->create(['name' => 'New Owner']);

        $payload = [
            'consents' => [
                [
                    'slug' => 'email-marketing',
                    'source' => 'web-form',
                    'text' => 'Email consent',
                    'state' => 'consented',
                ],
            ],
        ];

        $sourceUser->createConsents($payload);

        $sourceUser->transferConsentsTo($targetUser);
        $consent = $targetUser->fresh()->consents->first();

        $this->assertNotNull($consent->transferable);
        $this->assertEquals($sourceUser->id, $consent->transferable->id);
        $this->assertEquals('Original Owner', $consent->transferable->name);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_consents_transferred(): void
    {
        $user = DummyConsentUser::factory()->create();

        $transferredConsents = $user->transferredConsents;

        $this->assertCount(0, $transferredConsents);
    }
}
