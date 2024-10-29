<?php

namespace Drupal\social_user\Event;

use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\Href;

/**
 * Contains data about an Open Social user.
 */
class UserEventData {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly string $displayName,
    public readonly ?string $firstName,
    public readonly ?string $lastName,
    public readonly string $email,
    public readonly array $roles,
    public readonly string $timezone,
    public readonly string $language,
    public readonly Address $address,
    public readonly ?string $phone,
    public readonly ?string $function,
    public readonly ?string $organization,
    public readonly Href $href,
  ) {}

}
