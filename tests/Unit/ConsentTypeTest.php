<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Unit;

use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Models\ConsentType;
use Blamodex\Consent\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConsentTypeTest extends TestCase
{
    #[Test]
    public function it_can_create_a_consent_type(): void
    {
        $type = ConsentType::factory()->create([
            'name' => 'Marketing Consent',
            'description' => 'Consent for marketing communications',
        ]);

        $this->assertDatabaseHas('consent_types', [
            'id' => $type->id,
            'name' => 'Marketing Consent',
            'description' => 'Consent for marketing communications',
        ]);
    }

    #[Test]
    public function it_auto_generates_uuid_on_create(): void
    {
        $type = ConsentType::factory()->create(['uuid' => null]);

        $this->assertNotNull($type->uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $type->uuid
        );
    }

    #[Test]
    public function it_auto_generates_slug_from_name(): void
    {
        $type = ConsentType::factory()->create([
            'name' => 'Marketing Consent',
            'slug' => null,
        ]);

        $this->assertEquals('marketing-consent', $type->slug);
    }

    #[Test]
    public function it_can_use_custom_slug(): void
    {
        $type = ConsentType::factory()->create([
            'name' => 'Marketing Consent',
            'slug' => 'custom-slug',
        ]);

        $this->assertEquals('custom-slug', $type->slug);
    }

    #[Test]
    public function it_has_many_consents(): void
    {
        $type = ConsentType::factory()->create();
        $consent1 = Consent::factory()->create(['consent_type_id' => $type->id]);
        $consent2 = Consent::factory()->create(['consent_type_id' => $type->id]);

        $this->assertCount(2, $type->consents);
        $this->assertTrue($type->consents->contains($consent1));
        $this->assertTrue($type->consents->contains($consent2));
    }

    #[Test]
    public function it_can_soft_delete(): void
    {
        $type = ConsentType::factory()->create();
        
        $type->delete();

        $this->assertSoftDeleted('consent_types', ['id' => $type->id]);
    }

    #[Test]
    public function slug_is_unique(): void
    {
        $type1 = ConsentType::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        ConsentType::factory()->create(['slug' => 'unique-slug']);
    }

    #[Test]
    public function description_can_be_null(): void
    {
        $type = ConsentType::factory()->create(['description' => null]);

        $this->assertNull($type->description);
    }

    #[Test]
    public function it_has_timestamps(): void
    {
        $type = ConsentType::factory()->create();

        $this->assertNotNull($type->created_at);
        $this->assertNotNull($type->updated_at);
    }
}
