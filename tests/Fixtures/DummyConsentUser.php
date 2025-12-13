<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Fixtures;

use Blamodex\Consent\Traits\Consentable;
use Blamodex\Consent\Contracts\ConsentableInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DummyConsentUser extends Model implements ConsentableInterface
{
    use Consentable;
    use HasFactory;

    protected $table = 'consent_users';
    protected $guarded = [];
    public $timestamps = false;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return new class extends Factory {
            protected $model = DummyConsentUser::class;

            public function definition(): array
            {
                return [
                    'id' => $this->faker->unique()->numberBetween(1, 100000),
                    'name' => $this->faker->name(),
                    'email' => $this->faker->unique()->safeEmail(),
                ];
            }
        };
    }
}
