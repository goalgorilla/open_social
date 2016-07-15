<?php

namespace Drupal\address;

use CommerceGuys\Addressing\Model\AddressInterface as ExternalAddressInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines the interface for addresses.
 */
interface AddressInterface extends ExternalAddressInterface, FieldItemInterface {
}
