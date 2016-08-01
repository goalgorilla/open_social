<?php

/**
 * @file
 * Contains \Drupal\group\Plugin\views\relationship\GroupContentToEntityReverse.
 */

namespace Drupal\group\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler which reverses group content entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_content_to_entity_reverse")
 */
class GroupContentToEntityReverse extends GroupContentToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJoinFieldType() {
    return 'field';
  }

}
