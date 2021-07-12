<?php

declare(strict_types=1);

namespace Drupal\Tests\social_user\Traits;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Helper trait for tests handling entity and field access.
 *
 * Provides shortcut methods to aid in proper test coverage for the different
 * types of access checks that exist in Drupal around entities.
 */
trait EntityAccessAssertionTrait {

  /**
   * Assert that the entity access for the given operation is as expected.
   *
   * Tests that the metadata of the actual result also matches the expected
   * result so that cacheability can be tested.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test access for.
   * @param string $operation
   *   The operation to test access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to test access against.
   * @param \Drupal\Core\Access\AccessResult $expected
   *   The expected access result.
   */
  protected function assertEntityAccess(EntityInterface $entity, string $operation, AccountInterface $account, AccessResult $expected) : void {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $entity->access($operation, $account, TRUE);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert that the field access for the given operation is as expected.
   *
   * Tests that the metadata of the actual result also matches the expected
   * result so that cacheability can be tested.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The fieldable entity to test access for.
   * @param string $field_name
   *   The name of the field to test access for.
   * @param string $operation
   *   The operation ot test access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to test access against.
   * @param \Drupal\Core\Access\AccessResult $expected
   *   The expected access result.
   */
  protected function assertFieldAccess(FieldableEntityInterface $entity, string $field_name, string $operation, AccountInterface $account, AccessResult $expected) : void {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $entity->get($field_name)->access($operation, $account, TRUE);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert that the entity create access is as expected.
   *
   * Tests that the metadata of the actual result also matches the expected
   * result so that cacheability can be tested.
   *
   * @param string $entity_type
   *   The entity type to test access for.
   * @param string|null $bundle
   *   The bundle to test access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to test access against.
   * @param array $context
   *   Any context that may be provided to the `createAccess` call to determine
   *   the entity create access.
   * @param \Drupal\Core\Access\AccessResult $expected
   *   The expected access result.
   */
  protected function assertEntityCreateAccess(string $entity_type, ?string $bundle, AccountInterface $account, array $context, AccessResult $expected) : void {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = $this->container->get('entity_type.manager')
      ->getAccessControlHandler($entity_type)
      ->createAccess($bundle, $account, $context, TRUE);

    static::assertInstanceOf(get_class($expected), $result, "Unexpected access result type.");
    $this->assertAccessMetadata($expected, $result);
  }

  /**
   * Assert a certain set of result metadata on a query result.
   *
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $expected
   *   The expected metadata object.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $result
   *   The access result object.
   *
   * @internal
   */
  private function assertAccessMetadata(RefinableCacheableDependencyInterface $expected, RefinableCacheableDependencyInterface $result): void {
    static::assertEquals($expected->getCacheMaxAge(), $result->getCacheMaxAge(), 'Unexpected cache max age.');

    $missingContexts = array_diff($expected->getCacheContexts(), $result->getCacheContexts());
    static::assertEmpty($missingContexts, 'Missing cache contexts: ' . implode(', ', $missingContexts));

    $unexpectedContexts = array_diff($result->getCacheContexts(), $expected->getCacheContexts());
    static::assertEmpty($unexpectedContexts, 'Unexpected cache contexts: ' . implode(', ', $unexpectedContexts));

    $missingTags = array_diff($expected->getCacheTags(), $result->getCacheTags());
    static::assertEmpty($missingTags, 'Missing cache tags: ' . implode(', ', $missingTags));

    $unexpectedTags = array_diff($result->getCacheTags(), $expected->getCacheTags());
    static::assertEmpty($unexpectedTags, 'Unexpected cache tags: ' . implode(', ', $unexpectedTags));
  }

  /**
   * Asserts that two variables are equal.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
   */
  abstract public static function assertEquals($expected, $actual, string $message = ''): void;

  /**
   * Asserts that a variable is empty.
   *
   * @throws \PHPUnit\Framework\ExpectationFailedException
   * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
   */
  abstract public static function assertEmpty($actual, string $message = ''): void;

}
