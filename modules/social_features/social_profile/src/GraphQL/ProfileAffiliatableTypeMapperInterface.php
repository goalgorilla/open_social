<?php

declare(strict_types=1);

namespace Drupal\social_profile\GraphQL;

/**
 * Maps a runtime value to a concrete ProfileAffiliatable GraphQL type.
 */
interface ProfileAffiliatableTypeMapperInterface {

  /**
   * Resolves the ProfileAffiliatable GraphQL type for the given value.
   *
   * @param mixed $object
   *   The value returned by a field resolver.
   *
   * @return string|null
   *   The GraphQL type name, or NULL if this mapper does not handle the value.
   */
  public function resolve(mixed $object): ?string;

}
