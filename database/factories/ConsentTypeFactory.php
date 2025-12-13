<?php

namespace Blamodex\Consent\Database\Factories;

use Blamodex\Consent\Models\ConsentType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Blamodex\Consent\Models\ConsentType>
 */
class ConsentTypeFactory extends Factory
{
    protected $model = ConsentType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        
        return [
            'uuid' => (string) Str::uuid(),
            'slug' => Str::slug($name),
            'name' => ucfirst($name),
            'description' => $this->faker->sentence(),
        ];
    }
}
