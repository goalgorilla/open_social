<?php

namespace Drupal\socialblue\Plugin\Preprocess;

use Drupal\Core\Url;
use Drupal\socialbase\Plugin\Preprocess\Node as NodeBase;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\views\Views;

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

    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];

    $view_modes = [
      'teaser',
      'activity',
      'activity_comment',
      'featured',
      'hero',
    ];

    // Add teaser tag as title prefix to node teasers and hero view modes.
    if (in_array($variables['view_mode'], $view_modes)) {
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
        '#type' => 'inline_template',
        '#template' => '<div class="teaser__tag">{{ teaser_tag }}</div>',
        '#context' => ['teaser_tag' => $teaser_tag],
      ];
    }
    elseif (
      $variables['view_mode'] === 'full' &&
      $node->bundle() === 'album'
    ) {
      $view = Views::getView('albums');
      $view->execute('embed_album');

      if (empty($view->result)) {
        $variables['link'] = Url::fromRoute(
          'entity.post.add_form',
          ['post_type' => 'photo']
        );
      }
    }
  }

}
