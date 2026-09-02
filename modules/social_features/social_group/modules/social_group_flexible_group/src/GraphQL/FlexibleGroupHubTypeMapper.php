<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\GraphQL;

use Drupal\group\Entity\GroupInterface;
use Drupal\social_group\GraphQL\HubTypeMapperInterface;

/**
 * Maps flexible groups to the Group GraphQL type.
 */
final class FlexibleGroupHubTypeMapper implements HubTypeMapperInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(mixed $object): ?string {
    return $object instanceof GroupInterface && $object->bundle() === 'flexible_group'
      ? 'Group'
      : NULL;
  }

}
