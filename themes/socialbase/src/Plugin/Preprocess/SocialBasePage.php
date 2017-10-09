<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\node\Entity\Node;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class SocialBasePage extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    $variables['display_page_title'] = TRUE;

    // If we have the admin toolbar permission.
    $user = \Drupal::currentUser();

    // Check for permission.
    if ($user->hasPermission('access toolbar')) {
      $variables['#attached']['library'][] = 'socialbase/admin-toolbar';
    }

    // Add plain title for node preview page templates.
    if (!empty($variables['page']['#title'])) {
      $variables['plain_title'] = strip_tags($variables['page']['#title']);
    }

    // Hide page title for pages where we want to
    // display it in the Hero instead, like event, topic, basic page.
    // First determine if we are looking at a node.
    $nid = \Drupal::routeMatch()->getRawParameter('node');
    $node = FALSE;

    if (!is_null($nid) && !is_object($nid)) {
      $node = Node::load($nid);
    }

    if ($node instanceof Node) {

      // List pages where we want to hide the default page title.
      $page_to_exclude = [
        'event',
        'topic',
        'page',
        'book',
      ];

      if (in_array($node->bundle(), $page_to_exclude)) {
        $variables['display_page_title'] = FALSE;
      }

    }

  }

}
