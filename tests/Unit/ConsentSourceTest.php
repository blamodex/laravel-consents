<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Models\ConsentSource;
use Blamodex\Consent\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConsentSourceTest extends TestCase
{
    // ==========================================
    // Basic CRUD Operations
    // ==========================================

    #[Test]
    public function it_can_create_a_consent_source(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Website Form',
            'description' => 'Consent collected via website form',
        ]);

        $this->assertDatabaseHas('consent_sources', [
            'id' => $source->id,
            'name' => 'Website Form',
            'description' => 'Consent collected via website form',
        ]);
    }

    // ==========================================
    // Auto-Generation: UUID and Slug
    // ==========================================

    #[Test]
    public function it_auto_generates_uuid_on_create(): void
    {
        $source = ConsentSource::factory()->create(['uuid' => null]);

        $this->assertNotNull($source->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $source->uuid
        );
    }

    #[Test]
    public function it_auto_generates_slug_from_name(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Website Form',
            'slug' => null,
        ]);

        $this->assertEquals('website-form', $source->slug);
    }

    #[Test]
    public function it_can_use_custom_slug(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Website Form',
            'slug' => 'custom-slug',
        ]);

        $this->assertEquals('custom-slug', $source->slug);
    }

    // ==========================================
    // Relationships
    // ==========================================

    #[Test]
    public function it_has_many_consents(): void
    {
        $source = ConsentSource::factory()->create();
        $consent1 = Consent::factory()->create(['consent_source_id' => $source->id]);
        $consent2 = Consent::factory()->create(['consent_source_id' => $source->id]);

        $this->assertCount(2, $source->consents);
        $this->assertTrue($source->consents->contains($consent1));
        $this->assertTrue($source->consents->contains($consent2));
    }

    // ==========================================
    // Soft Deletion
    // ==========================================

    #[Test]
    public function it_can_soft_delete(): void
    {
        $source = ConsentSource::factory()->create();
        
        $source->delete();

        $this->assertSoftDeleted('consent_sources', ['id' => $source->id]);
    }

    // ==========================================
    // Validation and Constraints
    // ==========================================

    #[Test]
    public function slug_is_unique(): void
    {
        $source1 = ConsentSource::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        ConsentSource::factory()->create(['slug' => 'unique-slug']);
    }

    #[Test]
    public function description_can_be_null(): void
    {
        $source = ConsentSource::factory()->create(['description' => null]);

        $this->assertNull($source->description);
    }

    // ==========================================
    // Timestamps
    // ==========================================

    #[Test]
    public function it_has_timestamps(): void
    {
        $source = ConsentSource::factory()->create();

        $this->assertNotNull($source->created_at);
        $this->assertNotNull($source->updated_at);
    }

    // ==========================================
    // Edge Cases and Special Scenarios
    // ==========================================

    #[Test]
    public function it_preserves_explicitly_provided_uuid(): void
    {
        $customUuid = '12345678-1234-1234-1234-123456789012';
        $source = ConsentSource::factory()->create([
            'uuid' => $customUuid,
        ]);

        $this->assertEquals($customUuid, $source->uuid);
    }

    #[Test]
    public function it_generates_slug_with_special_characters(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Email & SMS Marketing!',
            'slug' => null,
        ]);

        $this->assertEquals('email-sms-marketing', $source->slug);
    }

    #[Test]
    public function it_generates_slug_with_multiple_spaces(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Web    Form    Input',
            'slug' => null,
        ]);

        $this->assertEquals('web-form-input', $source->slug);
    }

    #[Test]
    public function it_handles_unicode_characters_in_slug_generation(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Móbile Äpp',
            'slug' => null,
        ]);

        // Laravel's Str::slug handles unicode conversion
        $this->assertNotEmpty($source->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $source->slug);
    }

    #[Test]
    public function it_can_restore_soft_deleted_source(): void
    {
        $source = ConsentSource::factory()->create();
        $sourceId = $source->id;
        
        $source->delete();
        $this->assertSoftDeleted('consent_sources', ['id' => $sourceId]);

        $source->restore();
        
        $this->assertDatabaseHas('consent_sources', [
            'id' => $sourceId,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function it_can_query_consents_through_relationship(): void
    {
        $source = ConsentSource::factory()->create();
        Consent::factory()->count(3)->create(['consent_source_id' => $source->id]);

        $consents = $source->consents()->where('status', 'consented')->get();

        $this->assertCount(3, $consents);
    }

    // ==========================================
    // Update Behavior
    // ==========================================

    #[Test]
    public function updating_name_does_not_change_existing_slug(): void
    {
        $source = ConsentSource::factory()->create([
            'name' => 'Original Name',
            'slug' => null,
        ]);

        $originalSlug = $source->slug;
        $this->assertEquals('original-name', $originalSlug);

        $source->name = 'Updated Name';
        $source->save();

        // Slug should remain unchanged after initial creation
        $this->assertEquals($originalSlug, $source->slug);
    }

    #[Test]
    public function it_can_manually_update_slug(): void
    {
        $source = ConsentSource::factory()->create([
            'slug' => 'original-slug',
        ]);

        $source->slug = 'updated-slug';
        $source->save();

        $this->assertEquals('updated-slug', $source->slug);
        $this->assertDatabaseHas('consent_sources', [
            'id' => $source->id,
            'slug' => 'updated-slug',
        ]);
    }

    #[Test]
    public function it_counts_related_consents_correctly(): void
    {
        $source = ConsentSource::factory()->create();
        Consent::factory()->count(5)->create(['consent_source_id' => $source->id]);

        $this->assertEquals(5, $source->consents()->count());
    }

    #[Test]
    public function it_returns_empty_collection_when_no_consents_exist(): void
    {
        $source = ConsentSource::factory()->create();

        $this->assertCount(0, $source->consents);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $source->consents);
    }

    #[Test]
    public function multiple_sources_can_have_different_slugs_for_same_name(): void
    {
        $source1 = ConsentSource::factory()->create([
            'name' => 'Web Form',
            'slug' => 'web-form-v1',
        ]);

        $source2 = ConsentSource::factory()->create([
            'name' => 'Web Form',
            'slug' => 'web-form-v2',
        ]);

        $this->assertNotEquals($source1->slug, $source2->slug);
        $this->assertEquals('web-form-v1', $source1->slug);
        $this->assertEquals('web-form-v2', $source2->slug);
    }

    #[Test]
    public function soft_deleted_sources_are_excluded_from_default_queries(): void
    {
        ConsentSource::factory()->count(3)->create();
        $sourceToDelete = ConsentSource::factory()->create();
        
        $sourceToDelete->delete();

        $this->assertEquals(3, ConsentSource::count());
        $this->assertEquals(4, ConsentSource::withTrashed()->count());
    }

    #[Test]
    public function it_cascades_soft_delete_relationship_queries(): void
    {
        $source = ConsentSource::factory()->create();
        Consent::factory()->count(2)->create(['consent_source_id' => $source->id]);

        $this->assertEquals(2, $source->consents()->count());

        $source->delete();

        // After soft delete, source should still have consents in database
        $deletedSource = ConsentSource::withTrashed()->find($source->id);
        $this->assertEquals(2, $deletedSource->consents()->count());
    }

    #[Test]
    public function it_updates_updated_at_timestamp_on_modification(): void
    {
        $source = ConsentSource::factory()->create();
        $originalUpdatedAt = $source->updated_at;

        sleep(1);
        
        $source->description = 'Updated description';
        $source->save();

        $this->assertNotEquals($originalUpdatedAt->timestamp, $source->updated_at->timestamp);
        $this->assertGreaterThan($originalUpdatedAt->timestamp, $source->updated_at->timestamp);
    }
}
