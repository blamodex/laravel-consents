<?php

declare(strict_types=1);

namespace Blamodex\Consent\Traits;

use Blamodex\Consent\Contracts\ConsentableInterface;
use Blamodex\Consent\Models\Consent;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Transferable
{
    /**
     * Get all of the consents that were transferred from this model.
     *
     * @return MorphMany<Consent>
     */
    public function transferredConsents(): MorphMany
    {
        return $this->morphMany(Consent::class, 'transferable');
    }

    /**
     * Transfer all consents from this model to another consentable model.
     *
     * @param ConsentableInterface $toConsentable
     * @return array<Consent>
     */
    public function transferConsentsTo(ConsentableInterface $toConsentable): array
    {
        $consentService = app()->make(\Blamodex\Consent\Services\ConsentService::class);

        return $consentService->transferConsents($this, $toConsentable);
    }
}
