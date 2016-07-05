<?php

namespace Drupal\dynamic_entity_reference;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager as CoreSelectionPluginManager;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin type manager for Dynamic Entity Reference Selection plugins.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see plugin_api
 */
class SelectionPluginManager extends CoreSelectionPluginManager {

  /**
   * {@inheritdoc}
   */
  public function getSelectionHandler(FieldDefinitionInterface $field_definition, EntityInterface $entity = NULL, $target_type = NULL) {
    if ($target_type === NULL) {
      return parent::getSelectionHandler($field_definition, $entity);
    }
    $settings = $field_definition->getSettings();
    $options = array(
      'target_type' => $target_type,
      'handler' => $settings[$target_type]['handler'],
      'handler_settings' => $settings[$target_type]['handler_settings'],
      'entity' => $entity,
    );
    return $this->getInstance($options);
  }

}
