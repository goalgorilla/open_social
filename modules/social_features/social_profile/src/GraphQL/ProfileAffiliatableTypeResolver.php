<?php

declare(strict_types=1);

namespace Drupal\social_profile\GraphQL;

/**
 * Resolves ProfileAffiliatable using tagged type mappers.
 */
final class ProfileAffiliatableTypeResolver {

  /**
   * Constructs a ProfileAffiliatableTypeResolver.
   *
   * @param iterable<ProfileAffiliatableTypeMapperInterface> $mappers
   *   Type mappers collected from tagged services.
   */
  public function __construct(
    private readonly iterable $mappers,
  ) {}

  /**
   * Resolves the concrete ProfileAffiliatable GraphQL type, if any.
   *
   * @param mixed $object
   *   The value returned by a field resolver.
   *
   * @return string|null
   *   The GraphQL type name, or NULL when no mapper handles the value.
   */
  public function resolveType(mixed $object): ?string {
    foreach ($this->mappers as $mapper) {
      $type = $mapper->resolve($object);
      if ($type !== NULL) {
        return $type;
      }
    }

    return NULL;
  }

  /**
   * Resolves the concrete ProfileAffiliatable GraphQL type.
   *
   * @param mixed $object
   *   The value returned by a field resolver.
   *
   * @return string
   *   The GraphQL type name.
   *
   * @throws \RuntimeException
   *   When no mapper can resolve the value.
   */
  public function __invoke(mixed $object): string {
    $type = $this->resolveType($object);
    if ($type !== NULL) {
      return $type;
    }

    $class = is_object($object) ? $object::class : get_debug_type($object);
    throw new \RuntimeException("Can not map instance of '$class' to ProfileAffiliatable.");
  }

}
