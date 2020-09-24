<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\socialbase\Plugin\Preprocess\Node as NodeBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "node" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("node")
 */
class Node extends NodeBase {

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {
    parent::preprocessElement($element, $variables);

    if (theme_get_setting('style') !== 'sky') {
      return;
    }

    $view_modes = [
      'teaser',
      'activity',
      'activity_comment',
      'featured',
      'hero',
    ];

    if (!in_array($variables['view_mode'], $view_modes)) {
      return;
    }

    // Add teaser tag as title prefix to node teasers and hero view modes.
    if (!empty($variables['topic_type'])) {
      $teaser_tag = $variables['topic_type'];
    }
    elseif (!empty($variables['event_type'])) {
      $teaser_tag = $variables['event_type'];
    }
    else {
      $teaser_tag = $variables['node']->type->entity->label();
    }

    $variables['title_prefix']['teaser_tag'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="teaser__tag">{{ teaser_tag }}</div>',
      '#context' => ['teaser_tag' => $teaser_tag],
    ];
  }

}
