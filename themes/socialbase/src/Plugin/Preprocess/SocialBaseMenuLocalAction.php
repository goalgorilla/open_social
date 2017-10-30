<?php

namespace Drupal\socialbase\Plugin\Preprocess;

/**
 * Pre-processes variables for the "menu_local_action" theme hook.
 *
 * @ingroup plugins_preprocess
 * @deprecated
 * @see \Drupal\socialbase\Plugin\Preprocess\MenuLocalAction
 */
class SocialBaseMenuLocalAction extends MenuLocalAction {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    parent::preprocessElement($element, $variables);

    // Identify the following buttons:
    // `Add member` on the manage members page of a group;
    // `New message` on the private message page.
    if (\Drupal::routeMatch()->getRouteName() === 'entity.group_content.collection' || \Drupal::routeMatch()->getRouteName() === 'entity.private_message_thread.canonical') {

      $variables['link']['#options']['attributes']['class'] = 'btn btn-primary btn-raised';
      $variables['attributes']['class'][] = 'margin-bottom-l';

    }

  }

}
