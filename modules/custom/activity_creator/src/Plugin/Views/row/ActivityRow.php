<?php
/**
 * @file
 * Row style plugin for Views.
 */

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
      // TODO: Move all this logic to a service.
      // TODO: Change this to use dependency injection.
      /* @var $plugin \Drupal\activity_creator\Plugin\ActivityDestinationManager */
      $destination_plugin_manager = \Drupal::service('plugin.manager.activity_destination.processor');

      foreach ($result as $row) {
        $render_result = array();
        $render_result[] = $row;
        $entity = $row->_entity;

        foreach ($entity->field_activity_destinations as $destination) {
          /* @var $plugin \Drupal\activity_creator\Plugin\ActivityDestinationBase */
          $plugin = $destination_plugin_manager->createInstance($destination->value);
          if ($plugin->isActiveInView($this->view)) {
            $this->options['view_mode'] = $plugin->getViewMode($view_mode, $entity);
          }
        }
        $this->getEntityTranslationRenderer()->preRender($render_result);
      }
    }
    $this->options['view_mode'] = $view_mode;

  }

}
