<?php

declare(strict_types=1);

namespace Blamodex\Consent\Contracts;

/**
 * Interface TransferableInterface
 *
 * Represents a model that can transfer consents to another model.
 * Extends ConsentableInterface as transferable models must also be consentable.
 */
interface TransferableInterface extends ConsentableInterface
{
}
