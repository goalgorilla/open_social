<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;

/**
 * Defines the 'social_external_identifier' field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "social_external_identifier",
 *   label = @Translation("External Identifier"),
 *   description = @Translation("Define external Identifier and external Owner/Consumer."),
 *   default_widget = "social_external_identifier_default_widget",
 *   default_formatter = "dynamic_entity_reference_entity_view",
 *   list_class = "\Drupal\social_core\Plugin\Field\FieldType\ExternalIdentifierItemList",
 *   constraints = {
 *     "ExternalIdentifierEmptySubfieldsConstraint" = {},
 *     "ExternalIdentifierExternalOwnerTargetTypeConstraint" = {},
 *     "ExternalIdentifierExternalOwnerIdConstraint" = {},
 *     "ComplexData" = {
 *       "external_id" = {
 *         "Length" = {"max" = 225}
 *        }
 *      }
 *   }
 * )
 */
final class ExternalIdentifierItem extends DynamicEntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Add your custom subfield.
    $properties['external_id'] = DataDefinition::create('string')
      ->setLabel(t('External ID'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    // Define the schema for the custom subfield.
    $schema['columns']['external_id'] = [
      'type' => 'varchar',
      'description' => 'External ID',
      'length' => 255,
      'not null' => TRUE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values, $notify);

    if (is_array($values) && isset($values['external_id'])) {
      $this->set('external_id', $values['external_id']);
    }
  }

}
