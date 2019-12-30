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
    /** @var \Drupal\node\Entity\Node $node */
    $node = $variables['node'];

    $style = theme_get_setting('style');
    if ($style && $style === 'sky') {

      if (!empty($variables['topic_type'])) {
        $teaser_tag = $variables['topic_type'];
      }
      elseif (!empty($variables['event_type'])) {
        $teaser_tag = $variables['event_type'];
      }
      else {
        $teaser_tag = $node->type->entity->label();
      }

      $variables['title_prefix']['teaser_tag'] = [
        '#markup' => '<div class="teaser__tag">' . $teaser_tag . '</div>',
      ];
    }

  }

}
