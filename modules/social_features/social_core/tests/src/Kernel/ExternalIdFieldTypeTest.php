<?php

namespace Drupal\Tests\social_core\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\social_core_test_entity_provider\Entity\ExternalOwnerEntityTestProvider;

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
    $this->installConfig(['field', 'node', 'user']);

    // Create a node type.
    $node_type = NodeType::create([
      'type' => 'test',
      'name' => 'Test',
    ]);
    $node_type->save();

    // Create the field storage.
    $field_storage_field_social_external_identifier = FieldStorageConfig::create([
      'field_name' => 'field_social_external_identifier',
      'entity_type' => 'node',
      'type' => 'social_external_identifier',
      'settings' => [
        'target_types' => [
          'test_external_owner_entity' => 'test_external_owner_entity',
        ],
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage_field_social_external_identifier->save();
    // Create the field.
    $field_field_social_external_identifier = FieldConfig::create([
      'field_storage' => $field_storage_field_social_external_identifier,
      'bundle' => 'test',
      'label' => 'External Identifier example',
    ]);
    $field_field_social_external_identifier->save();

    // Create the field storage for field without target types defined.
    $field_storage_field_sei_no_target_types = FieldStorageConfig::create([
      // `sei` stands for social_external_identifier.
      'field_name' => 'field_sei_no_target_types',
      'entity_type' => 'node',
      'type' => 'social_external_identifier',
      'settings' => [
        'target_types' => [],
      ],
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $field_storage_field_sei_no_target_types->save();
    // Create the field.
    $field_field_storage_field_sei_no_target_types = FieldConfig::create([
      'field_storage' => $field_storage_field_sei_no_target_types,
      'bundle' => 'test',
      'label' => 'External Identifier example',
    ]);
    $field_field_storage_field_sei_no_target_types->save();
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

}
