<?php

namespace Drupal\activity_viewer\Plugin\views\row;

use Drupal\views\Plugin\views\row\EntityRow;
use Drupal\user\Entity\User;

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
        $render_result = [];
        $entity = $row->_entity;
        $target_type = $entity->get('field_activity_entity')->getValue()[0]["target_type"];

        if ($target_type === "comment") {
          $comment_id = $entity->get('field_activity_entity')->getValue()[0]["target_id"];
          $query = \Drupal::database()->select('comment_field_data', 'c');
          $query->addField('c', 'entity_id');
          $query->condition('c.cid', $comment_id);
          $post_id = $query->execute()->fetchField();

          $query = \Drupal::database()->select('post_field_data', 'p');
          $query->fields('p');
          $query->condition('p.id', $post_id);
          $post = $query->execute()->fetchAll();

          $current_user_id = \Drupal::currentUser()->id();
          $current_user = User::load($current_user_id);

          if ($post->status =! "0" || $post->user_id === $current_user_id || $current_user->hasRole('administrator') ) {
            $render_result[] = $row;
          }

        }
        else {
          $render_result[] = $row;
        }

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
