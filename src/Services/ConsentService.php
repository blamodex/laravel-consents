<?php

declare(strict_types=1);

namespace Blamodex\Consent\Services;

use Blamodex\Consent\Contracts\ConsentableInterface;
use Blamodex\Consent\Models\Consent;
use Blamodex\Consent\Models\ConsentType;
use Blamodex\Consent\Models\ConsentSource;
use Illuminate\Support\Carbon;

/**
 * Service responsible for managing consents
 * for models that implement ConsentInterface.
 */
class ConsentService
{
    /**
     * Create or update consent records from an attributes payload.
     *
     * Expected shape:
     * [
     *   'consents' => [
     *     [
     *       'text' => 'Please send me email updates.',
     *       'source' => 'checkbox',
     *       'slug' => 'email-marketing',
     *       'state' => 'consented'|'revoked'
     *     ]
     *   ]
     * ]
     *
     * Returns an array of Consent models that were created or updated.
     *
     * @param ConsentableInterface $consentable
     * @param array<string,mixed> $attributes
     *
     * @return Consent[]
     */
    public function createConsents(ConsentableInterface $consentable, array $attributes): array
    {
        $consentsPayload = $attributes['consents'] ?? [];

        $results = [];

        foreach ($consentsPayload as $consentKey => $action) {
            $consentText = $action['text'] ?? '';
            $consentState = $action['state'] ?? '';
            $consentType = isset($action['slug'])
                ? ConsentType::where('slug', (string) $action['slug'])->first()
                : null;
            $consentSource = isset($action['source'])
                ? ConsentSource::where('slug', $action['source'])->first()
                : null;

            // if either the type or source don't exist, skip this record
            if (! $consentType || ! $consentSource || !$consentState) {
                continue;
            }

            if ($consentState === 'consented') {
                $existingConsent = Consent::where('consentable_id', $consentable->getKey())
                    ->where('consentable_type', $consentable->getMorphClass())
                    ->where('consent_type_id', $consentType->id)
                    ->first();
                
                if (! $existingConsent) {
                    $consent = Consent::create([
                        'consentable_id' => $consentable->getKey(),
                        'consentable_type' => $consentable->getMorphClass(),
                        'consent_type_id' => $consentType->id,
                        'consent_source_id' => $consentSource->id,
                        'consent_text' => $consentText,
                        'status' => 'consented',
                        'consented_at' => Carbon::now()
                    ]);

                    $results[] = $consent;
                } else {
                    // If existing consent was revoked, soft-delete it for audit trail
                    if ($existingConsent->status === 'revoked') {
                        $existingConsent->delete();
                        
                        // Create a new consented record
                        $consent = Consent::create([
                            'consentable_id' => $consentable->getKey(),
                            'consentable_type' => $consentable->getMorphClass(),
                            'consent_type_id' => $consentType->id,
                            'consent_source_id' => $consentSource->id,
                            'consent_text' => $consentText,
                            'status' => 'consented',
                            'consented_at' => Carbon::now()
                        ]);

                        $results[] = $consent;
                    } else {
                        // Existing consent is already 'consented' - update source if needed
                        if ($existingConsent->consent_source_id !== $consentSource->id) {
                            $existingConsent->consent_source_id = $consentSource->id;
                            $existingConsent->consent_text = $consentText;
                            $existingConsent->consented_at = Carbon::now();
                            $existingConsent->save();
                        }

                        $results[] = $existingConsent;
                    }
                }
            }

            if ($consentState === 'revoked') {
                $existingConsent = Consent::where('consentable_id', $consentable->getKey())
                    ->where('consentable_type', $consentable->getMorphClass())
                    ->where('consent_type_id', $consentType->id)
                    ->first();
                
                if ($existingConsent) {
                    //Don't update source on revocation, more important we know where the original consent was given
                    $existingConsent->status = 'revoked';
                    $existingConsent->revoked_at = Carbon::now();
                    $existingConsent->save();
                    $results[] = $existingConsent;
                } else {
                    // If no existing consent exists, create a revoked record so
                    // revocation actions are auditable and can be queried later.
                    $consent = Consent::create([
                        'consentable_id' => $consentable->getKey(),
                        'consentable_type' => $consentable->getMorphClass(),
                        'consent_type_id' => $consentType->id,
                        'consent_source_id' => $consentSource->id,
                        'consent_text' => $consentText,
                        'status' => 'revoked',
                        'consented_at' => null,
                        'revoked_at' => Carbon::now()
                    ]);

                    $results[] = $consent;
                }
            }
        }
        
        return $results;
    }

    /**
     * Format Consent models into web-ready structure.
     *
     * @param Consent[] $consents
     * @return array<string,mixed>
     */
    public function formatConsentsFrontend(array $consents): array
    {
        $formatted = [];
        $formatted['consents'] = [];

        foreach ($consents as $consent) {
            $formatted['consents'][] = [
                'type' => $consent->consentType->name,
                'status' => strtoupper($consent->status),
            ];
        }

        return $formatted;
    }

    public function consentedToByConsentable(ConsentableInterface $consentable, string $consentTypeSlug): bool
    {
        $consentType = ConsentType::where('slug', $consentTypeSlug)->first();
        if (!$consentType) {
            return false;
        }

        $existingConsent = Consent::where('consentable_id', $consentable->getKey())
            ->where('consentable_type', $consentable->getMorphClass())
            ->where('consent_type_id', $consentType->id)
            ->where('status', 'consented')
            ->first();

        return $existingConsent !== null;
    }
}
