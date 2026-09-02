<?php

declare(strict_types=1);

namespace Drupal\social_group\GraphQL;

/**
 * Resolves Hub using tagged type mappers.
 */
final class HubTypeResolver {

  /**
   * Constructs a HubTypeResolver.
   *
   * @param iterable<HubTypeMapperInterface> $mappers
   *   Type mappers collected from tagged services.
   */
  public function __construct(
    private readonly iterable $mappers,
  ) {}

  /**
   * Resolves the concrete Hub GraphQL type.
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
    foreach ($this->mappers as $mapper) {
      $type = $mapper->resolve($object);
      if ($type !== NULL) {
        return $type;
      }
    }

    $class = is_object($object) ? $object::class : get_debug_type($object);
    throw new \RuntimeException("Can not map instance of '$class' to Hub.");
  }

}
