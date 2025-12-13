<?php

namespace Blamodex\Consent\Database\Factories;

use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Models\ConsentType;
use Blamodex\Consent\Models\ConsentSource;
use Blamodex\Consent\Tests\Fixtures\DummyConsentUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Blamodex\Consent\Models\Consent>
 */
class ConsentFactory extends Factory
{
    protected $model = Consent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'consent_type_id' => ConsentType::factory(),
            'consent_source_id' => ConsentSource::factory(),
            'consentable_type' => DummyConsentUser::class,
            'consentable_id' => DummyConsentUser::factory(),
            'consent_text' => $this->faker->paragraph(),
            'status' => 'consented',
            'consented_at' => now(),
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that the consent has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);
    }

    /**
     * Indicate that the consent is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'consented_at' => null,
        ]);
    }
}
