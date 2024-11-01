<?php

namespace Drupal\social_user\Event;

use Drupal\social_eda\Types\Href;

/**
 * Contains data about an Open Social user.
 */
class UserEventDataLite {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly string $displayName,
    public readonly array $roles,
    public readonly string $timezone,
    public readonly string $language,
    public readonly Href $href,
  ) {}

}
