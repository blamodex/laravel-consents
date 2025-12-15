<?php

declare(strict_types=1);

namespace Blamodex\Consent\Traits;

use Blamodex\Consent\Contracts\TransferableInterface;
use Blamodex\Consent\Models\Consent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Consentable
{
    /**
     * Get all of the consents for the model.
     *
     * @return MorphMany<Consent>
     */
    public function consents(): MorphMany
    {
        return $this->morphMany(Consent::class, 'consentable');
    }

    public function createConsents(array $attributes): array
    {
        $consentService = app()->make(\Blamodex\Consent\Services\ConsentService::class);

        return $consentService->createConsents($this, $attributes);
    }

    public function consentedTo(string $slug): bool
    {
        $consentService = app()->make(\Blamodex\Consent\Services\ConsentService::class);

        return $consentService->consentedToByConsentable($this, $slug);
    }

    public function claimConsents(TransferableInterface $fromTransferable): array
    {
        $consentService = app()->make(\Blamodex\Consent\Services\ConsentService::class);

        return $consentService->transferConsents($fromTransferable, $this);
    }
}
