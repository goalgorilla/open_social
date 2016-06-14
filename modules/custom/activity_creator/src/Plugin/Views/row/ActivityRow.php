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

    $view_mode = $this->options['view_mode'];

    if ($result) {
      foreach ($result as $row) {
        $render_result = array();
        $render_result[] = $row;
        $entity = $row->_entity;
        $target_entity_type = $entity->field_activity_entity->target_type;

        // TODO: discriminate on view and / or destinations.
        // Do not change the view mode if is for notifications.
        if ($target_entity_type === 'post' && $this->options['view_mode'] !== 'notification') {
          $this->options['view_mode'] = 'render_entity';
        }
        else {
          $this->options['view_mode'] = $view_mode;
        }
        $this->getEntityTranslationRenderer()->preRender($render_result);
      }
    }
    $this->options['view_mode'] = $view_mode;

  }
}
