<?php

namespace Drupal\social_topic\Event;

use Drupal\social_eda\Types\ContentVisibility;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;

/**
 * Contains data about the creation of an Open Social topic.
 */
class TopicEntityData {

  /**
   * {@inheritDoc}
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly string $label,
    public readonly ContentVisibility|null $visibility,
    public readonly ?string $type,
    public readonly Entity|null $group,
    public readonly User $author,
    public readonly Href $href,
  ) {}

}
