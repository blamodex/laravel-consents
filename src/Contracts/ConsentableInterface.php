<?php

declare(strict_types=1);

namespace Blamodex\Consent\Contracts;

/**
 * Interface ConsentableInterface
 *
 * Represents a model that can be assigned a consent.
 */
interface ConsentableInterface
{
    /**
     * Get the unique identifier for the model.
     *
     * Typically the primary key.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Get the morph class name for the model.
     *
     * Used in polymorphic relationships.
     *
     * @return string
     */
    public function getMorphClass();
}
