<?php

namespace Drupal\search_api\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;

/**
 * Views relationship plugin for datasources.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("search_api")
 */
class SearchApiRelationship extends RelationshipPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['required']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->alias = $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = array();

    if (!empty($this->definition['entity type'])) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($this->definition['entity type']);
      if ($entity_type) {
        $dependencies['module'][] = $entity_type->getProvider();
      }
    }

    return $dependencies;
  }

}
