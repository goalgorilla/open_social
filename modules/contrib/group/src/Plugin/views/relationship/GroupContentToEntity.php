<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\views\relationship\GroupContentToEntity.
 */

namespace Drupal\group\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler for group content entity references.
 *
 * Definition items:
 * - target_entity_type: The ID of the entity type this relationship maps to.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_content_to_entity")
 */
class GroupContentToEntity extends GroupContentToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['target_entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJoinFieldType() {
    return 'left_field';
  }

}
