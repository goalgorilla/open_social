<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\MenuLocalAction;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "menu_local_action" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("menu_local_action")
 */
class SocialBaseMenuLocalAction extends MenuLocalAction {

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables) {

    parent::preprocessElement($element, $variables);

    if (\Drupal::routeMatch()->getRouteName() === 'entity.group_content.collection') {

      $variables['link']['#options']['attributes']['class'] = 'btn waves-effect btn-primary btn-raised';
      $variables['attributes']['class'][] = 'margin-bottom-l';

    }

  }

}
