<?php

declare(strict_types=1);

namespace Blamodex\Consent\Database\Factories;

use Blamodex\Consent\Models\ConsentSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Blamodex\Consent\Models\ConsentSource>
 */
class ConsentSourceFactory extends Factory
{
    protected $model = ConsentSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        
        return [
            'uuid' => (string) Str::uuid(),
            'slug' => Str::slug($name),
            'name' => ucfirst($name),
            'description' => $this->faker->sentence(),
        ];
    }
}
