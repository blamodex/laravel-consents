<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Models\ConsentType;
use Blamodex\Consent\Models\ConsentSource;
use Blamodex\Consent\Tests\TestCase;
use Blamodex\Consent\Tests\Fixtures\DummyConsentUser;
use PHPUnit\Framework\Attributes\Test;

class ConsentTest extends TestCase
{
    // ==========================================
    // Basic CRUD and Relationship Tests
    // ==========================================

    #[Test]
    public function it_can_create_a_consent(): void
    {
        $user = DummyConsentUser::factory()->create();
        $type = ConsentType::factory()->create();
        $source = ConsentSource::factory()->create();

        $consent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
            'consent_type_id' => $type->id,
            'consent_source_id' => $source->id,
        ]);

        $this->assertDatabaseHas('consents', [
            'id' => $consent->id,
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
            'consent_type_id' => $type->id,
            'consent_source_id' => $source->id,
        ]);
    }

    #[Test]
    public function it_belongs_to_consentable(): void
    {
        $user = DummyConsentUser::factory()->create();

        $consent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
        ]);

        $this->assertInstanceOf(DummyConsentUser::class, $consent->consentable);
        $this->assertEquals($user->id, $consent->consentable->id);
    }

    #[Test]
    public function it_belongs_to_consent_type(): void
    {
        $type = ConsentType::factory()->create();
        $consent = Consent::factory()->create([
            'consent_type_id' => $type->id,
        ]);

        $this->assertInstanceOf(ConsentType::class, $consent->consentType);
        $this->assertEquals($type->id, $consent->consentType->id);
    }

    #[Test]
    public function it_belongs_to_consent_source(): void
    {
        $source = ConsentSource::factory()->create();
        $consent = Consent::factory()->create([
            'consent_source_id' => $source->id,
        ]);

        $this->assertInstanceOf(ConsentSource::class, $consent->consentSource);
        $this->assertEquals($source->id, $consent->consentSource->id);
    }

    // ==========================================
    // Auto-Generation and Default Values
    // ==========================================

    #[Test]
    public function it_auto_generates_uuid_on_create(): void
    {
        $consent = Consent::factory()->create(['uuid' => null]);

        $this->assertNotNull($consent->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $consent->uuid
        );
    }

    #[Test]
    public function it_auto_sets_consented_at_on_create(): void
    {
        $consent = Consent::factory()->create(['consented_at' => null]);

        $this->assertNotNull($consent->consented_at);
    }

    #[Test]
    public function it_has_default_status_of_consented(): void
    {
        $consent = Consent::factory()->create();

        $this->assertEquals('consented', $consent->status);
    }

    // ==========================================
    // Status and State Management
    // ==========================================

    #[Test]
    public function it_can_check_if_consent_is_active(): void
    {
        $activeConsent = Consent::factory()->create([
            'status' => 'consented',
            'revoked_at' => null,
        ]);

        $this->assertTrue($activeConsent->isActive());
    }

    #[Test]
    public function it_can_check_if_consent_is_not_active_when_revoked(): void
    {
        $revokedConsent = Consent::factory()->revoked()->create();

        $this->assertFalse($revokedConsent->isActive());
    }

    #[Test]
    public function it_can_check_if_consent_is_revoked(): void
    {
        $revokedConsent = Consent::factory()->revoked()->create();

        $this->assertTrue($revokedConsent->isRevoked());
    }

    #[Test]
    public function it_can_check_if_consent_is_not_revoked(): void
    {
        $activeConsent = Consent::factory()->create();

        $this->assertFalse($activeConsent->isRevoked());
    }

    #[Test]
    public function it_can_revoke_a_consent(): void
    {
        $consent = Consent::factory()->create([
            'status' => 'consented',
            'revoked_at' => null,
        ]);

        $result = $consent->revoke();

        $this->assertTrue($result);
        $this->assertEquals('revoked', $consent->status);
        $this->assertNotNull($consent->revoked_at);
    }

    // ==========================================
    // Attribute Casting and Data Types
    // ==========================================

    #[Test]
    public function it_casts_consented_at_to_datetime(): void
    {
        $consent = Consent::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $consent->consented_at);
    }

    #[Test]
    public function it_casts_revoked_at_to_datetime(): void
    {
        $consent = Consent::factory()->revoked()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $consent->revoked_at);
    }

    // ==========================================
    // Soft Deletion
    // ==========================================

    #[Test]
    public function it_can_soft_delete(): void
    {
        $consent = Consent::factory()->create();
        
        $consent->delete();

        $this->assertSoftDeleted('consents', ['id' => $consent->id]);
    }

    // ==========================================
    // Consent Text Handling
    // ==========================================

    #[Test]
    public function it_stores_consent_text(): void
    {
        $consentText = 'I agree to the terms and conditions.';
        $consent = Consent::factory()->create([
            'consent_text' => $consentText,
        ]);

        $this->assertEquals($consentText, $consent->consent_text);
    }

    #[Test]
    public function consent_text_can_be_null(): void
    {
        $consent = Consent::factory()->create([
            'consent_text' => null,
        ]);

        $this->assertNull($consent->consent_text);
    }

    // ==========================================
    // Edge Cases and Special Scenarios
    // ==========================================

    #[Test]
    public function it_preserves_explicitly_provided_uuid(): void
    {
        $customUuid = '12345678-1234-1234-1234-123456789012';
        $consent = Consent::factory()->create([
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $consent->uuid);
    }

    #[Test]
    public function it_preserves_explicitly_provided_consented_at(): void
    {
        $customDate = now()->subDays(30);
        $consent = Consent::factory()->create([
            'consented_at' => $customDate,
        ]);

        $this->assertEquals($customDate->toDateTimeString(), $consent->consented_at->toDateTimeString());
    }

    #[Test]
    public function is_active_returns_false_for_pending_status(): void
    {
        $consent = Consent::factory()->pending()->create();

        $this->assertFalse($consent->isActive());
    }

    #[Test]
    public function is_active_returns_false_when_revoked_at_is_set_even_if_status_is_consented(): void
    {
        $consent = Consent::factory()->create([
            'status' => 'consented',
            'revoked_at' => now(),
        ]);

        $this->assertFalse($consent->isActive());
    }

    #[Test]
    public function is_revoked_returns_true_when_revoked_at_is_set_even_if_status_is_not_revoked(): void
    {
        $consent = Consent::factory()->create([
            'status' => 'pending',
            'revoked_at' => now(),
        ]);

        $this->assertTrue($consent->isRevoked());
    }

    #[Test]
    public function is_revoked_returns_true_when_status_is_revoked_even_if_revoked_at_is_null(): void
    {
        $consent = Consent::factory()->create([
            'status' => 'revoked',
            'revoked_at' => null,
        ]);

        $this->assertTrue($consent->isRevoked());
    }

    #[Test]
    public function revoking_already_revoked_consent_returns_true_and_updates_timestamp(): void
    {
        $consent = Consent::factory()->revoked()->create();
        $originalRevokedAt = $consent->revoked_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        $result = $consent->revoke();

        $this->assertTrue($result);
        $this->assertEquals('revoked', $consent->status);
        $this->assertNotEquals($originalRevokedAt->timestamp, $consent->revoked_at->timestamp);
    }

    #[Test]
    public function can_restore_soft_deleted_consent(): void
    {
        $consent = Consent::factory()->create();
        $consentId = $consent->id;
        
        $consent->delete();
        $this->assertSoftDeleted('consents', ['id' => $consentId]);

        $consent->restore();
        
        $this->assertDatabaseHas('consents', [
            'id' => $consentId,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function multiple_consents_can_be_soft_deleted_independently(): void
    {
        $consent1 = Consent::factory()->create();
        $consent2 = Consent::factory()->create();
        
        $consent1->delete();

        $this->assertSoftDeleted('consents', ['id' => $consent1->id]);
        $this->assertDatabaseHas('consents', [
            'id' => $consent2->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function it_maintains_all_attributes_after_revocation(): void
    {
        $user = DummyConsentUser::factory()->create();
        $type = ConsentType::factory()->create();
        $source = ConsentSource::factory()->create();
        
        $consent = Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user->id,
            'consent_type_id' => $type->id,
            'consent_source_id' => $source->id,
            'consent_text' => 'Original consent text',
        ]);

        $originalConsentedAt = $consent->consented_at;
        $consent->revoke();

        // Verify all original attributes are preserved
        $this->assertEquals($user->id, $consent->consentable_id);
        $this->assertEquals(DummyConsentUser::class, $consent->consentable_type);
        $this->assertEquals($type->id, $consent->consent_type_id);
        $this->assertEquals($source->id, $consent->consent_source_id);
        $this->assertEquals('Original consent text', $consent->consent_text);
        $this->assertEquals($originalConsentedAt->timestamp, $consent->consented_at->timestamp);
    }

    // ==========================================
    // Querying and Filtering
    // ==========================================

    #[Test]
    public function it_can_query_consents_by_status(): void
    {
        Consent::factory()->create(['status' => 'consented']);
        Consent::factory()->create(['status' => 'consented']);
        Consent::factory()->revoked()->create();

        $consentedCount = Consent::where('status', 'consented')->count();
        $revokedCount = Consent::where('status', 'revoked')->count();

        $this->assertEquals(2, $consentedCount);
        $this->assertEquals(1, $revokedCount);
    }

    #[Test]
    public function it_can_query_consents_by_consentable_type(): void
    {
        $user1 = DummyConsentUser::factory()->create();
        $user2 = DummyConsentUser::factory()->create();

        Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user1->id,
        ]);

        Consent::factory()->create([
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => $user2->id,
        ]);

        $count = Consent::where('consentable_type', DummyConsentUser::class)->count();

        $this->assertEquals(2, $count);
    }
}
