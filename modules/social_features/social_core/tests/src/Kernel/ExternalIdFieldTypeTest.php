<?php

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\social_core_test_entity_provider\Entity\ConsumerATestProvider;
use Drupal\social_core_test_entity_provider\Entity\ConsumerBTestProvider;
use Drupal\social_core_test_entity_provider\Entity\ExternalOwnerEntityTestProvider;
use Drupal\user\Entity\User;

/**
 * Tests the social_external_identifier field type.
 *
 * @group social_external_identifier
 */
class ExternalIdFieldTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'user',
    'system',
    'text',
    'entity_test',
    'social_core_test_entity_provider',
    'social_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('test_external_owner_entity');
    $this->installEntitySchema('test_consumer_a');
    $this->installEntitySchema('test_consumer_b');
    $this->installConfig(['field', 'node', 'user']);

    // Create a node type.
    $node_type = NodeType::create([
      'type' => 'test',
      'name' => 'Test',
    ]);
    $node_type->save();

    // Create the field storage.
    $this->createSocialExternalIdentifierFieldStorage(
      'node',
      'test',
      'field_social_external_identifier',
      [
        'test_external_owner_entity' => 'test_external_owner_entity',
      ],
      'External Identifier example'
    );

    // Create the field storage for field without target types defined.
    $this->createSocialExternalIdentifierFieldStorage(
      'node',
      'test',
      'field_sei_no_target_types',
      [],
      'External Identifier example'
    );
  }

  // TOC
  //
  // Configuration:
  // Case 0: Test external identifier field settings.
  //
  // Field:
  // Case 1: Create a node with the external identifier field values.
  // Case 2: Create a node without the external identifier field values.
  // Case 3: Create a node with the external identifier field with NULL values.
  // Case 4: Create a node with the external identifier field with empty values.
  //
  // Constraints:
  // Case 5: Without the external_id subfield value provided.
  // Case 6: Without the external_owner_target_type subfield value provided.
  // Case 7: Without the external_owner_id subfield value provided.
  // Case 8: Not allowed target type.
  // Case 9: Invalid target type.
  // Case 10: Target types are not defined.
  // Case 11: External id uniqueness.

  /**
   * Case 0: Test external identifier field settings.
   *
   * Tests that the social_external_identifier settings are correctly applied.
   */
  public function testFieldSettings(): void {
    // Load the field storage configuration and verify settings.
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = FieldStorageConfig::loadByName('node', 'field_social_external_identifier');
    $this->assertEquals(['test_external_owner_entity' => 'test_external_owner_entity'], $field_storage->getSettings()['target_types']);
  }

  /**
   * Case 1: Create a node with the external identifier field.
   *
   * Tests that the external identifier field values can be added to a node and
   * that values are stored correctly.
   */
  public function testExternalIdentifierFieldType(): void {
    $test_owner_entity = ExternalOwnerEntityTestProvider::create([
      'name' => 'External Owner Entity Example',
    ]);
    $test_owner_entity->save();

    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => '123-456-789-abc-def',
        'external_owner_target_type' => 'test_external_owner_entity',
        'external_owner_id' => '1',
      ],
    ]);
    $node->save();

    // Reload the node and check the field values.
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::load($node->id());
    /** @var \Drupal\social_core\Plugin\Field\FieldType\ExternalIdentifierItem $field */
    $field = $node->get('field_social_external_identifier')->first();

    $this->assertEquals('123-456-789-abc-def', $field->get('external_id')->getValue());
    $this->assertEquals('test_external_owner_entity', $field->get('external_owner_target_type')->getValue());
    $this->assertEquals('1', $field->get('external_owner_id')->getValue());

    // Check that the entity can be loaded via magic method.
    $entity = $field->getExternalOwnerEntity();
    $this->assertInstanceOf('Drupal\Core\Entity\EntityInterface', $entity);
    $this->assertInstanceOf('Drupal\social_core_test_entity_provider\Entity\ExternalOwnerEntityTestProvider', $entity);
    $this->assertEquals('External Owner Entity Example', $entity->getName());
  }

  /**
   * Case 2: Create a node without the external identifier field.
   *
   * Tests that the external identifier field values can be omitted when
   * creating a node, and that not defining these values does not cause
   * any errors.
   */
  public function testNodeWithoutExternalIdentifierFieldType(): void {
    $node_without_external_identifier = Node::create([
      'type' => 'test',
      'title' => 'Test node',
    ]);
    $violations = $node_without_external_identifier->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Case 3: Create a node with the external identifier field with NULL values.
   *
   * Test that the external identifier field values with subfield values set to
   * NULL do not cause errors.
   */
  public function testNodeWithNullValuesOnExternalIdentifierFieldType(): void {
    $node_with_null_external_identifier = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => NULL,
        'external_owner_target_type' => NULL,
        'external_owner_id' => NULL,
      ],
    ]);
    $violations_null = $node_with_null_external_identifier->validate();
    $this->assertCount(0, $violations_null);
  }

  /**
   * Case 4: Create a node with the external identifier field with empty values.
   *
   * Verify that the external identifier field values with subfield values set
   * to empty do not cause errors.
   */
  public function testNodeWithEmptyValuesOnExternalIdentifierFieldType(): void {
    // Case 4: Create a node with the external identifier field with subfield
    // values set as empty string.
    $node_with_empty_external_identifier = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => '',
        'external_owner_target_type' => '',
        'external_owner_id' => '',
      ],
    ]);
    $violations_empty = $node_with_empty_external_identifier->validate();
    $this->assertCount(0, $violations_empty);
  }

  /**
   * Case 5: Without the external_id subfield value provided.
   *
   * Tests that constraints ExternalIdentifierEmptySubfieldsConstraint and
   * NotNullConstraint are triggered when external_id subfield value is not
   * provided.
   */
  public function testConstraintForExternalIdentifierFieldTypeWithoutExternalId(): void {
    $test_owner_entity = ExternalOwnerEntityTestProvider::create([
      'name' => 'External Owner Entity Example',
    ]);
    $test_owner_entity->save();

    // Create a node with external identifier field, but without the external_id
    // subfield.
    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_owner_target_type' => 'test_external_owner_entity',
        'external_owner_id' => '1',
      ],
    ]);
    $violations = $node->validate();
    $this->assertCount(2, $violations);
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierEmptySubfieldsConstraint', $violations->get(0)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_1 */
    $message_1 = $violations->get(0)->getMessage();
    $this->assertSame('Not all required subfields have been set. Please insert values for: <em class="placeholder">External ID (external_id)</em>.', $message_1->render());
    $this->assertInstanceOf('Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint', $violations->get(1)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_2 */
    $message_2 = $violations->get(1)->getMessage();
    $this->assertSame('This value should not be null.', $message_2->render());
  }

  /**
   * Case 6: Without the external_owner_target_type subfield value provided.
   *
   * Tests that constraint ExternalIdentifierEmptySubfieldsConstraint is
   * triggered when external_id subfield value is not provided.
   */
  public function testConstraintForExternalIdentifierFieldTypeWithoutExternalOwnerTargetType(): void {
    // Create a node with external identifier field, but without the
    // external_owner_target_type subfield.
    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => '123-456-789-abc-def',
        'external_owner_id' => '1',
      ],
    ]);
    $violations = $node->validate();
    $this->assertCount(1, $violations);
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierEmptySubfieldsConstraint', $violations->get(0)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message */
    $message = $violations->get(0)->getMessage();
    $this->assertSame('Not all required subfields have been set. Please insert values for: <em class="placeholder">Target Entity Type (external_owner_target_type)</em>.', $message->render());
  }

  /**
   * Case 7: Without the external_owner_id subfield value provided.
   *
   * Tests that constraints ExternalIdentifierEmptySubfieldsConstraint and
   * NotNullConstraint are triggered when external_owner_id subfield value is
   * not provided.
   */
  public function testConstraintForExternalIdentifierFieldTypeWithoutExternalOwnerId():void {
    // Create a node with external identifier field, but without the
    // external_owner_id subfield.
    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => '123-456-789-abc-def',
        'external_owner_target_type' => 'test_external_owner_entity',
      ],
    ]);
    $violations = $node->validate();
    $this->assertCount(2, $violations);
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierEmptySubfieldsConstraint', $violations->get(0)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_1 */
    $message_1 = $violations->get(0)->getMessage();
    $this->assertSame('Not all required subfields have been set. Please insert values for: <em class="placeholder">External Owner (external_owner_id)</em>.', $message_1->render());
    $this->assertInstanceOf('Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint', $violations->get(1)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_2 */
    $message_2 = $violations->get(1)->getMessage();
    $this->assertSame('This value should not be null.', $message_2->render());
  }

  /**
   * Case 8: Not allowed target type.
   *
   * Tests that constraints ExternalIdentifierExternalOwnerTargetTypeConstraint
   * and ExternalIdentifierExternalOwnerIdConstraint are triggered when not
   * allowed target type is provided on external_owner_target_type subfield.
   */
  public function testConstraintForExternalIdentifierFieldTypeWithNotAllowedTargetType():void {
    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => '123-456-789-abc-def',
        // Target type exist, but is not allowed.
        'external_owner_target_type' => 'node',
        'external_owner_id' => '1',
      ],
    ]);
    $violations = $node->validate();
    $this->assertCount(2, $violations);
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierExternalOwnerTargetTypeConstraint', $violations->get(0)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_1 */
    $message_1 = $violations->get(0)->getMessage();
    $this->assertSame('Target type "<em class="placeholder">node</em>" is not valid. Valid target types are: "<em class="placeholder">test_external_owner_entity</em>".', $message_1->render());
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierExternalOwnerIdConstraint', $violations->get(1)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_2 */
    $message_2 = $violations->get(1)->getMessage();
    $this->assertSame('The entity of type "<em class="placeholder">node</em>" and ID "<em class="placeholder">1</em>" does not exist.', $message_2->render());
  }

  /**
   * Case 9: Invalid target type.
   *
   * Tests that constraint ExternalIdentifierExternalOwnerTargetTypeConstraint
   * is triggered when invalid target type is provided on
   * external_owner_target_type subfield.
   */
  public function testConstraintForExternalIdentifierFieldTypeWithInvalidTargetType():void {
    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_social_external_identifier' => [
        'external_id' => '123-456-789-abc-def',
        // Target type does not exist.
        'external_owner_target_type' => 'wrong_target_type',
        'external_owner_id' => '1',
      ],
    ]);
    $violations = $node->validate();
    $this->assertCount(2, $violations);
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierExternalOwnerTargetTypeConstraint', $violations->get(0)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_1 */
    $message_1 = $violations->get(0)->getMessage();
    $this->assertSame('Target type "<em class="placeholder">wrong_target_type</em>" is not valid. Valid target types are: "<em class="placeholder">test_external_owner_entity</em>".', $message_1->render());
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierExternalOwnerTargetTypeConstraint', $violations->get(1)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_2 */
    $message_2 = $violations->get(1)->getMessage();
    $this->assertSame('The entity type "<em class="placeholder">wrong_target_type</em>" does not exist.', $message_2->render());
  }

  /**
   * Case 10: Target types are not defined.
   *
   * Tests that constraint ExternalIdentifierExternalOwnerTargetTypeConstraint
   * is triggered when target types are not defined in field storage
   * configuration.
   */
  public function testConstraintForExternalIdentifierFieldTypeWhenTargetTypesAreNotDefined():void {
    $node = Node::create([
      'type' => 'test',
      'title' => 'Test node',
      'field_sei_no_target_types' => [
        'external_id' => '123-456-789-abc-def',
        // Target types are not defined in field storage configuration.
        'external_owner_target_type' => 'test_external_owner_entity',
        'external_owner_id' => '1',
      ],
    ]);
    $violations = $node->validate();
    $this->assertCount(1, $violations);
    $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierExternalOwnerTargetTypeConstraint', $violations->get(0)->getConstraint());
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup; $message_1 */
    $message_1 = $violations->get(0)->getMessage();
    $this->assertSame('Currently, there are no available target types (allowed entity types). Please contact the system administrator to enable at least one target type.', $message_1->render());
  }

  /**
   * Case 11: External id uniqueness.
   *
   * Validates the ExternalIdentifierUniqueExternalIdConstraint constraint.
   *
   * @dataProvider provideTestExternalIdentifierUniqueExternalIdConstraintData
   */
  public function testExternalIdentifierUniqueExternalIdConstraint(
    int $expected_number_of_violations,
    string $expected_violation_message,
    string $new_entity_type,
    array $new_entity_data
  ): void {

    // Create entities to which all other scenarios will be validated against.
    // Create a node type article.
    $node_type_article = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type_article->save();

    // Create a node type event.
    $node_type_event = NodeType::create([
      'type' => 'event',
      'name' => 'Event',
    ]);
    $node_type_event->save();

    // test_consumer_a:1.
    $test_consumer_a = ConsumerATestProvider::create([
      'name' => 'Consumer A (1)',
    ]);
    $test_consumer_a->save();

    // test_consumer_a:2.
    $test_consumer_b = ConsumerATestProvider::create([
      'name' => 'Consumer A (2)',
    ]);
    $test_consumer_b->save();

    // test_consumer_b:1.
    $test_owner_entity = ConsumerBTestProvider::create([
      'name' => 'Consumer B (1)',
    ]);
    $test_owner_entity->save();

    // Create the field storage for field_ex_id_1 (article).
    $this->createSocialExternalIdentifierFieldStorage(
      'node',
      'article',
      'field_ex_id_1',
      [
        'test_consumer_a' => 'test_consumer_a',
        'test_consumer_b' => 'test_consumer_b',
      ],
      'External Identifier 1 (article)'
    );

    // Create the field storage for field_ex_id_2 (article).
    $this->createSocialExternalIdentifierFieldStorage(
      'node',
      'article',
      'field_ex_id_2',
      [
        'test_consumer_a' => 'test_consumer_a',
        'test_consumer_b' => 'test_consumer_b',
      ],
      'External Identifier 2 (article)'
    );

    // Create the field storage for field_ex_id_1 (event).
    $this->createSocialExternalIdentifierFieldStorage(
      'node',
      'event',
      'field_ex_id_1',
      [
        'test_consumer_a' => 'test_consumer_a',
        'test_consumer_b' => 'test_consumer_b',
      ],
      'External Identifier 1 (event)'
    );

    // Create the field storage for field_ex_id_1 (user).
    $this->createSocialExternalIdentifierFieldStorage(
      'user',
      'user',
      'field_ex_id_1',
      [
        'test_consumer_a' => 'test_consumer_a',
        'test_consumer_b' => 'test_consumer_b',
      ],
      'External Identifier 1 (user)'
    );

    // Node:1.
    $node = Node::create([
      'type' => 'article',
      'title' => 'Article 1',
      'field_ex_id_1' => [
        'external_id' => '123',
        'external_owner_target_type' => 'test_consumer_a',
        'external_owner_id' => '1',
      ],
    ]);
    $node->save();

    // Validation.
    $entity_storage = \Drupal::entityTypeManager()->getStorage($new_entity_type);
    $new_entity = $entity_storage->create($new_entity_data);

    if ($new_entity instanceof Node || $new_entity instanceof User) {
      $violations = $new_entity->validate();
      $this->assertCount($expected_number_of_violations, $violations);
      if ($violations->count() > 0) {
        $this->assertInstanceOf('Drupal\social_core\Plugin\Validation\Constraint\ExternalIdentifierUniqueExternalIdConstraint', $violations->get(0)->getConstraint());
        /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $message */
        $message = $violations->get(0)->getMessage();
        $constraint_message = $message->render();
      }
      else {
        $constraint_message = '';
      }
      $this->assertSame($expected_violation_message, $constraint_message);
    }
    else {
      throw new \Exception('Unsupported entity type');
    }

  }

  /**
   * Data set provider for testExternalIdentifierUniqueExternalIdConstraint.
   *
   * In the scenarios we are only validating second node creation (node:1 is
   * part of the setup and is the same per each data set).
   *
   * @return array[]
   *   Data provider array.
   */
  public function provideTestExternalIdentifierUniqueExternalIdConstraintData() {
    return [

      // Scenario 1:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | node:2 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      //
      // Expected result:
      // Scenario should trigger constraint error as External ID is not unique.
      [
        1,
        'External identifier id should be unique. External identifier id "<em class="placeholder">123</em>" is already used, with external owner "<em class="placeholder">test_consumer_a</em>" of id "<em class="placeholder">1</em>".',
        'node',
        [
          'type' => 'article',
          'title' => 'Article 2',
          'field_ex_id_1' => [
            'external_id' => '123',
            'external_owner_target_type' => 'test_consumer_a',
            'external_owner_id' => '1',
          ],
        ],
      ],

      // Scenario 2:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | node:2 | article | field_ex_id_2 | 123         | test_consumer_a:1 |
      //
      // Expected result:
      // Scenario should trigger constraint error as External ID is not unique
      // even withing two different fields within same entity type.
      [
        1,
        'External identifier id should be unique. External identifier id "<em class="placeholder">123</em>" is already used, with external owner "<em class="placeholder">test_consumer_a</em>" of id "<em class="placeholder">1</em>".',
        'node',
        [
          'type' => 'article',
          'title' => 'Article 2',
          'field_ex_id_2' => [
            'external_id' => '123',
            'external_owner_target_type' => 'test_consumer_a',
            'external_owner_id' => '1',
          ],
        ],
      ],

      // Scenario 3:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | node:2 | event   | field_ex_id_1 | 123         | test_consumer_a:1 |
      //
      // Expected result:
      // Scenario should trigger constraint error as External ID is not unique
      // even within two different entity bundles within same entity type.
      [
        1,
        'External identifier id should be unique. External identifier id "<em class="placeholder">123</em>" is already used, with external owner "<em class="placeholder">test_consumer_a</em>" of id "<em class="placeholder">1</em>".',
        'node',
        [
          'type' => 'event',
          'title' => 'Event 1',
          'field_ex_id_1' => [
            'external_id' => '123',
            'external_owner_target_type' => 'test_consumer_a',
            'external_owner_id' => '1',
          ],
        ],
      ],

      // Scenario 4:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | user:1 | /       | field_ex_id_1 | 123         | test_consumer_a:1 |
      //
      // Expected result:
      // This situation is allowed as External ID is unique per entity type.
      [
        0,
        '',
        'user',
        [
          'type' => 'user',
          'name' => 'user1',
          'mail' => 'user1@example.com',
          'field_ex_id_1' => [
            'external_id' => '123',
            'external_owner_target_type' => 'test_consumer_a',
            'external_owner_id' => '1',
          ],
        ],
      ],

      // Scenario 5:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | node:2 | article | field_ex_id_1 | 123         | test_consumer_a:2 |
      //
      // Expected result:
      // This situation is allowed as External ID is unique per external owner.
      [
        0,
        '',
        'node',
        [
          'type' => 'article',
          'title' => 'Article 2',
          'field_ex_id_1' => [
            'external_id' => '123',
            'external_owner_target_type' => 'test_consumer_a',
            'external_owner_id' => '2',
          ],
        ],
      ],

      // Scenario 6:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | node:2 | article | field_ex_id_1 | 123         | test_consumer_b:1 |
      //
      // Expected result:
      // This situation is allowed as External ID is unique per external owner.
      [
        0,
        '',
        'node',
        [
          'type' => 'article',
          'title' => 'Article 2',
          'field_ex_id_1' => [
            'external_id' => '123',
            'external_owner_target_type' => 'test_consumer_b',
            'external_owner_id' => '1',
          ],
        ],
      ],

      // Scenario 6:
      // | Entity | Bundle  | Field         | External ID | External owner    |
      // | ------ | ------- | ------------- | ----------- | ----------------- |
      // | node:1 | article | field_ex_id_1 | 123         | test_consumer_a:1 |
      // | node:2 | article | field_ex_id_1 | 1234        | test_consumer_a:1 |
      //
      // Expected result:
      // This situation is allowed as External ID is unique.
      [
        0,
        '',
        'node',
        [
          'type' => 'article',
          'title' => 'Article 2',
          'field_ex_id_1' => [
            'external_id' => '1234',
            'external_owner_target_type' => 'test_consumer_a',
            'external_owner_id' => '1',
          ],
        ],
      ],
    ];

  }

  /**
   * Create social_external_identifier field storage.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param string $field_machine_name
   *   Field machine name.
   * @param array $allowed_target_types
   *   Allowed target types for social_external_identifier field.
   *   Example: ['consumer_a' => 'consumer_a', 'consumer_b' => 'consumer_b'].
   * @param string $label
   *   Field label.
   *
   * @return void
   *   Return void.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createSocialExternalIdentifierFieldStorage(
    string $entity_type,
    string $bundle,
    string $field_machine_name,
    array $allowed_target_types,
    string $label
  ): void {

    if (!FieldStorageConfig::load($entity_type . '.' . $field_machine_name)) {
      // Create the field storage.
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_machine_name,
        'entity_type' => $entity_type,
        'type' => 'social_external_identifier',
        'settings' => [
          'target_types' => $allowed_target_types,
        ],
        'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      ]);
      $field_storage->save();
    }
    else {
      $field_storage = FieldStorageConfig::load($entity_type . '.' . $field_machine_name);
    }

    // Create the field.
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $label,
    ]);
    $field->save();

  }

}
