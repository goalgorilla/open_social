<?php

namespace Drupal\Tests\dynamic_entity_reference\Kernel;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Ensures that Dynamic Entity References schema is correct.
 *
 * @group dynamic_entity_reference
 */
class DynamicEntityReferenceSchemaTest extends EntityKernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array(
    'dynamic_entity_reference',
  );

  /**
   * Tests dynamic entity reference schema.
   */
  public function testDynamicEntityReferenceSchema() {
    $typed_config = \Drupal::service('config.typed');
    // Test the field storage schema.
    /** @var \Drupal\field\Entity\FieldStorageConfig $field */
    $field_storage = FieldStorageConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'type' => 'dynamic_entity_reference',
      'settings' => array(
        'exclude_entity_types' => FALSE,
        'entity_type_ids' => [
          'entity_test',
        ],
      ),
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ));
    $field_storage->save();

    $this->assertConfigSchema($typed_config, 'field.storage.' . $field_storage->id(), $field_storage->toArray());

    // Test the field schema.
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::create(array(
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => array(
        'entity_test' => array(
          'handler' => 'default',
          'handler_settings' => array(
            'target_bundles' => array(
              'entity_test' => 'entity_test',
            ),
            'sort' => array(
              'field' => '_none',
            ),
            'auto_create' => FALSE,
          ),
        ),
      ),
    ));
    $entity_storage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $referenced_entity = $entity_storage->create(array());
    $referenced_entity->save();
    $field->setDefaultValue(array(
      array(
        'target_type' => $referenced_entity->getEntityTypeId(),
        'target_id' => $referenced_entity->id(),
      ),
    ));
    $field->save();

    $this->assertConfigSchema($typed_config, 'field.field.' . $field->id(), $field->toArray());

    // Test the field widget schema.
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $form_display */
    $form_display = EntityFormDisplay::create(array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ));
    $form_display->setComponent('field_test', array(
      'weight' => 0,
      'type' => 'dynamic_entity_reference_default',
      'settings' => array(
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'autocomplete_type' => 'tags',
        'placeholder' => '',
      ),
      'third_party_settings' => array(),
    ))->save();

    $this->assertConfigSchema($typed_config, 'core.entity_form_display.' . $form_display->id(), $form_display->toArray());

    // Test all the field formatters schema.
    /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
    $view_display = EntityViewDisplay::create(array(
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ));
    $view_display->setComponent('field_test', array(
      'weight' => 0,
      'type' => 'dynamic_entity_reference_label',
      'label' => 'above',
      'settings' => array(
        'link' => TRUE,
      ),
      'third_party_settings' => array(),
    ))->save();

    $this->assertConfigSchema($typed_config, 'core.entity_view_display.' . $view_display->id(), $view_display->toArray());

    $view_display->setComponent('field_test', array(
      'weight' => 0,
      'type' => 'dynamic_entity_reference_entity_id',
      'label' => 'above',
      'settings' => array(),
      'third_party_settings' => array(),
    ))->save();

    $this->assertConfigSchema($typed_config, 'core.entity_view_display.' . $view_display->id(), $view_display->toArray());

    $view_display->setComponent('field_test', array(
      'weight' => 0,
      'type' => 'dynamic_entity_reference_entity_view',
      'label' => 'above',
      'settings' => array(
        'view_mode' => 'default',
        'link' => FALSE,
      ),
      'third_party_settings' => array(),
    ))->save();

    $this->assertConfigSchema($typed_config, 'core.entity_view_display.' . $view_display->id(), $view_display->toArray());

  }

}
