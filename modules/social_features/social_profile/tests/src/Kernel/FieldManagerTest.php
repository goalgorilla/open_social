<?php

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\Core\Field\FieldDefinition;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\social_profile\FieldManager;

/**
 * Test the FieldManager service and the visibility field creation logic.
 *
 * This primarily tests that the FieldManager works as expected. However it does
 * it in such a way that it relies on the creation of the visibility fields
 * themselves. This properly indicates that how we manage the visibility fields
 * are not part of the external API (with the exception of the `manage_access`
 * third-party setting).
 */
class FieldManagerTest extends ProfileKernelTestBase {

  /**
   * The field manager as implemented.
   */
  private FieldManager $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() : void {
    parent::setUp();

    /** @var \Drupal\social_profile\FieldManager $fieldManager */
    $fieldManager = $this->container->get('social_profile.field_manager');
    $this->fieldManager = $fieldManager;
  }

  /**
   * Smoke test to check that visibility fields are actually created.
   *
   * Helps distinguish broken field management functionality from issues in the
   * FieldManager itself.
   */
  public function testVisibilityFieldIsCreated() : void {
    /** @var \Drupal\field\FieldStorageConfigInterface $fieldStorage */
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'profile',
      'required' => TRUE,
    ])->save();

    /** @var \Drupal\field\FieldConfigInterface|NULL $visibilityField */
    $visibilityField = FieldConfig::loadByName("profile", "profile", "visibility_test_field");

    self::assertNotNull($visibilityField);
  }

  /**
   * Test that the FieldManager ignores non fieldable fields.
   */
  public function testItIgnoresNonFieldableFields() : void {
    $nonFieldableField = FieldDefinition::create('string');

    self::assertTrue($this->fieldManager::isOptedOutOfFieldAccessManagement($nonFieldableField));
  }

  /**
   * Test that developers can opt out of the visibility management.
   */
  public function testItAllowsActiveOptOut() : void {
    /** @var \Drupal\field\FieldStorageConfigInterface $fieldStorage */
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'profile',
    ]);
    $field->save();

    self::assertFalse(
      $this->fieldManager::isOptedOutOfFieldAccessManagement($field),
      "Expected field to be opted in to visibility configuration by default."
    );

    $fieldStorage->setThirdPartySetting("social_profile", "managed_access", FALSE);
    $fieldStorage->save();

    self::assertTrue(
      $this->fieldManager::isOptedOutOfFieldAccessManagement($field),
      "Expected field to be opted out to visibility configuration."
    );
  }

  /**
   * Test that we can get the name of a visibility field for a value field.
   */
  public function testGetVisibilityFieldName() : void {
    /** @var \Drupal\field\FieldStorageConfigInterface $fieldStorage */
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'profile',
    ]);
    $field->save();

    self::assertEquals("visibility_test_field", $this->fieldManager::getVisibilityFieldName($field));
  }

  /**
   * Test that we can easily retrieve the name of the value field we manage.
   */
  public function testGetManagedValueFieldName() : void {
    /** @var \Drupal\field\FieldStorageConfigInterface $fieldStorage */
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'profile',
    ])->save();

    /** @var \Drupal\field\FieldConfigInterface|NULL $visibilityField */
    $visibilityField = FieldConfig::loadByName("profile", "profile", "visibility_test_field");

    self::assertNotNull($visibilityField, "Could not load visibility field for 'test_field'.");
    self::assertEquals("test_field", $this->fieldManager::getManagedValueFieldName($visibilityField));
  }

  /**
   * Test that we can easily check whether something is a visibility field.
   */
  public function testIsVisibilityField() : void {
    /** @var \Drupal\field\FieldStorageConfigInterface $fieldStorage */
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'profile',
      'type' => 'string',
    ]);
    $fieldStorage->save();

    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'profile',
    ]);
    $field->save();

    /** @var \Drupal\field\FieldConfigInterface|NULL $visibilityField */
    $visibilityField = FieldConfig::loadByName("profile", "profile", "visibility_test_field");

    self::assertNotNull($visibilityField, "Could not load visibility field for 'test_field'.");
    self::assertFalse($this->fieldManager::isVisibilityField($field), "Expected 'test_field' not to be a visibility field.");
    self::assertTrue($this->fieldManager::isVisibilityField($visibilityField), "Expected 'visibility_test_field' to be a visibility field.");
  }

}
