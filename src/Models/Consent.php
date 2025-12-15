<?php

declare(strict_types=1);

namespace Blamodex\Consent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * The Consent model.
 *
 * @property int $id
 * @property string $uuid
 * @property int $consent_type_id
 * @property int $consent_source_id
 * @property int $consentable_id
 * @property string $consentable_type
 * @property int|null $transferable_id
 * @property string|null $transferable_type
 * @property string|null $consent_text
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property \Illuminate\Support\Carbon|null $transferred_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read ConsentType $consentType
 * @property-read ConsentSource $consentSource
 * @property-read Model $consentable
 * @property-read Model|null $transferable
 */
class Consent extends Model
{
    use SoftDeletes;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'consent_type_id',
        'consent_source_id',
        'consentable_id',
        'consentable_type',
        'transferable_id',
        'transferable_type',
        'consent_text',
        'status',
        'consented_at',
        'revoked_at',
        'transferred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'revoked_at' => 'datetime',
            'transferred_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->consented_at)) {
                $model->consented_at = now();
            }
        });
    }

    /**
     * Get the consent type.
     *
     * @return BelongsTo<ConsentType, Consent>
     */
    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    /**
     * Get the consent source.
     *
     * @return BelongsTo<ConsentSource, Consent>
     */
    public function consentSource(): BelongsTo
    {
        return $this->belongsTo(ConsentSource::class);
    }

    /**
     * Get the consentable entity.
     *
     * @return MorphTo<Model, Consent>
     */
    public function consentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the transferable entity (original owner before transfer).
     *
     * @return MorphTo<Model, Consent>
     */
    public function transferable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if consent is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'consented' && is_null($this->revoked_at);
    }

    /**
     * Check if consent has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked' || !is_null($this->revoked_at);
    }

    /**
     * Revoke this consent.
     */
    public function revoke(): bool
    {
        $this->status = 'revoked';
        $this->revoked_at = now();
        return $this->save();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Blamodex\Consent\Database\Factories\ConsentFactory
     */
    protected static function newFactory(): \Blamodex\Consent\Database\Factories\ConsentFactory
    {
        return \Blamodex\Consent\Database\Factories\ConsentFactory::new();
    }
}
