<?php

namespace Drupal\Tests\social_graphql\Kernel;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * @coversDefaultClass \Drupal\social_graphql\Plugin\GraphQL\DataProducer\Field\Field
 * @group social_graphql
 */
class FieldDataProducerAccessTest extends SocialGraphQLTestBase {

  /**
   * Verify that access check is performed for supporting fields.
   *
   * @covers ::resolve
   */
  public function testFieldDataProducerAccessCheck() {
    /** @var \Drupal\social_graphql\Plugin\GraphQL\DataProducer\Field\Field $data_producer */
    $data_producer = $this->container
      ->get('plugin.manager.graphql.data_producer')
      ->createInstance('field');

    $allowed_item_list = $this->createMock(FieldItemListInterface::class);
    $allowed_item_list->method('access')
      ->willReturn(TRUE);
    $allowed_entity = $this->createMock(FieldableEntityInterface::class);
    $allowed_entity->method('hasField')
      ->willReturn(TRUE);
    $allowed_entity->method('get')
      ->willReturn($allowed_item_list);

    self::assertEquals($allowed_item_list, $data_producer->resolve($allowed_entity, 'allowed_field'));

    $disallowed_item_list = $this->createMock(FieldItemListInterface::class);
    $disallowed_item_list->method('access')
      ->willReturn(FALSE);
    $disallowed_entity = $this->createMock(FieldableEntityInterface::class);
    $disallowed_entity->method('hasField')
      ->willReturn(TRUE);
    $disallowed_entity->method('get')
      ->willReturn($disallowed_item_list);

    self::assertEquals(NULL, $data_producer->resolve($disallowed_entity, 'disallowed_field'));
  }

}
