<?php

declare(strict_types=1);

namespace Blamodex\Consent\Tests\Fixtures;

use Blamodex\Consent\Contracts\ConsentableInterface;
use Blamodex\Consent\Contracts\TransferableInterface;
use Blamodex\Consent\Traits\Transferable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A fixture model for testing the Transferable trait.
 */
class DummyTransferableUser extends Model implements ConsentableInterface, TransferableInterface
{
    use Transferable;
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
            protected $model = DummyTransferableUser::class;

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
