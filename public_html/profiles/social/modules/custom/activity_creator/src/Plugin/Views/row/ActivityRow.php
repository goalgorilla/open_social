<?php

namespace Drupal\activity_creator\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Plugin which performs a activity_view on the resulting object.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "entity:activity",
 * )
 */
class ActivityRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {

    if ($result) {
      foreach ($result as $row) {
        $render_result = array();
        $render_result[] = $row;
        $entity = $row->_entity;
        $target_entity_type = $entity->field_activity_entity->target_type;

        if ($target_entity_type === 'post') {
          $this->options['view_mode'] = 'render_entity';
        }
        else {
          $this->options['view_mode'] = 'default';
        }
        $this->getEntityTranslationRenderer()->preRender($render_result);
      }
    }
  }
}
