<?php

namespace Drupal\social_comment\Event;

use Drupal\social_eda\Types\EntityReference;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;

/**
 * Comment entity data for CloudEvents.
 */
final class CommentEntityData {

  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly ?EntityReference $target,
    public readonly Thread $thread,
    public readonly User $author,
    public readonly Href $href,
  ) {}

}
