<?php

declare(strict_types=1);

namespace Drupal\social_post\Event;

use Drupal\social_post\Types\PostContentVisibility;
use Drupal\social_eda\Types\Href;
use Drupal\social_eda\Types\User;
use Drupal\social_post\Types\Stream;

/**
 * Contains data about an Open Social post.
 */
final class PostEntityData {

  /**
   * Constructs the PostEntityData type.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $created,
    public readonly string $updated,
    public readonly string $status,
    public readonly ?PostContentVisibility $visibility,
    public readonly Stream $stream,
    public readonly User $author,
    public readonly Href $href,
  ) {}

}
