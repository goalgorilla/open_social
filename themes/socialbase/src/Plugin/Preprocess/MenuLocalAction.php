<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\MenuLocalAction as BaseMenuLocalAction;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "menu_local_action" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("menu_local_action")
 */
class MenuLocalAction extends BaseMenuLocalAction {

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
