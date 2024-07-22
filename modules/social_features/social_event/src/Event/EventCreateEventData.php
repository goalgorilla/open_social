<?php

namespace Drupal\social_event\Event;

use Drupal\social_eda\Types\Address;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;

/**
 * Contains data about the creation of an Open Social event.
 */
class EventCreateEventData {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly bool $status,
    public readonly string $label,
    public readonly string $visibility,
    public readonly Entity|null $group,
    public readonly User $author,
    public readonly bool $allDay,
    public readonly string $start,
    public readonly string $end,
    public readonly string $timezone,
    public readonly Address $address,
    public readonly array $enrollment,
    public readonly Href $href,
    public readonly ?string $type,
  ) {}

}
