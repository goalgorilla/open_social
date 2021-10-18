<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel\GraphQL\DataProducer\Field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;

/**
 * Test the field data producer.
 *
 * Uses the `entity_test` module to create entities in various states for
 * testing.
 */
class FieldTest extends SocialGraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    "text",
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installConfig(['field', 'system']);

    // Create the test field.
    $this->container->get('module_handler')->loadInclude('entity_test', 'install');
    // @phpstan-ignore-next-line
    entity_test_install();
  }

  /**
   * Test that the data producer handles missing fields gracefully.
   */
  public function testReturnsNullForMissingField() : void {
    $result = $this->executeDataProducer(
      'field',
      [
        'entity' => EntityTest::create(),
        'field' => 'nonexistant_field',
      ]
    );
    self::assertNull($result);
  }

  /**
   * Test that the data producer does not expose disallowed fields.
   */
  public function testReturnsNullForAccessDenied() : void {
    $result = $this->executeDataProducer(
      'field',
      [
        'entity' => EntityTest::create(['field_test_text' => 'no access value']),
        'field' => 'field_test_text',
      ]
    );
    self::assertNull($result);
  }

  /**
   * Test that the data producer returns null when a field is empty.
   */
  public function testReturnsNullForEmptyField() : void {
    $result = $this->executeDataProducer(
      'field',
      [
        'entity' => EntityTest::create([]),
        'field' => 'field_test_text',
      ]
    );
    self::assertNull($result);
  }

  /**
   * Test that the data producer returns the field in other circumstances.
   */
  public function testReturnsFieldForAccessibleFilledField() : void {
    $result = $this->executeDataProducer(
      'field',
      [
        'entity' => EntityTest::create(['field_test_text' => 'custom_value']),
        'field' => 'field_test_text',
      ]
    );

    self::assertInstanceOf(FieldItemListInterface::class, $result);
    self::assertEquals([0 => ['value' => "custom_value"]], $result->getValue());
  }

}
