<?php

declare(strict_types=1);

namespace Drupal\Tests\social_group\Unit\GraphQL;

use Drupal\social_group\GraphQL\HubTypeMapperInterface;
use Drupal\social_group\GraphQL\HubTypeResolver;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Hub type collector behaviour.
 *
 * @group social_group
 */
class HubTypeResolverTest extends UnitTestCase {

  /**
   * The first matching mapper wins.
   */
  public function testResolvesFromFirstMatchingMapper(): void {
    $resolver = new HubTypeResolver([
      new class implements HubTypeMapperInterface {

        /**
         * {@inheritdoc}
         */
        public function resolve(mixed $object): ?string {
          return $object === 'hub' ? 'Group' : NULL;
        }

      },
    ]);

    $this->assertSame('Group', $resolver('hub'));
  }

  /**
   * The collector rejects values no mapper handles.
   */
  public function testUnknownValueThrows(): void {
    $resolver = new HubTypeResolver([]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Can not map instance of 'stdClass' to Hub.");
    $resolver(new \stdClass());
  }

}
