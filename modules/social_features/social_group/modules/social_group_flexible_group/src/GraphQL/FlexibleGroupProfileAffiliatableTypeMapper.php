<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\GraphQL;

use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_profile\GraphQL\ProfileAffiliatableTypeMapperInterface;

/**
 * Maps flexible group memberships to GroupMembership.
 */
final class FlexibleGroupProfileAffiliatableTypeMapper implements ProfileAffiliatableTypeMapperInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(mixed $object): ?string {
    return $object instanceof GroupRelationshipInterface && $object->bundle() === 'flexible_group-group_membership'
      ? 'UserGroupAffiliation'
      : NULL;
  }

}
