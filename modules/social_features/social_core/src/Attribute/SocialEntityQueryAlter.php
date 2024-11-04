<?php

declare(strict_types=1);

namespace Drupal\social_core\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;

/**
 * The SocialEntityQueryAlter attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SocialEntityQueryAlter extends Plugin {

  /**
   * Constructs a new SocialEntityQueryAlter instance.
   *
   * @param string $id
   *   The plugin ID.
   * @param array $search_api_query_tags
   *   Search api query tags the current plugin wants to alter.
   * @param array $apply_on
   *   The associative array with entity type and fields that plugin
   *   should process. It can contain multiple entity types, but we suggest
   *   implementing one plugin per one entity type.
   */
  public function __construct(
    public readonly string $id,
    public readonly array $search_api_query_tags = [],
    public readonly array $apply_on = [],
  ) {}

}
