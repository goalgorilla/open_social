<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\Core\Template\Attribute;
use Drupal\node\Entity\Node;

/**
 * Pre-processes variables for the "page" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends PreprocessBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info) {
    parent::preprocess($variables, $hook, $info);

    // Add needed attributes so later in template we can manipulate with them.
    $attributes = new Attribute();
    // Default classes.
    $attributes->addClass('row', 'container');
    // If page has title.
    if ($variables['page']['title']) {
      $attributes->addClass('with-title-region');
      $variables['display_page_title'] = TRUE;
    }

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

      // If there is a title and node type is excluded remove class.
      if (in_array($node->bundle(), $page_to_exclude, TRUE)) {
        $attributes->removeClass('with-title-region');
        $variables['display_page_title'] = FALSE;
      }

    }

    // Check complementary_top and complementary_bottom variables.
    if ($variables['page']['complementary_top'] || $variables['page']['complementary_bottom']) {
      $attributes->addClass('layout--with-complementary');
    }
    // Check if sidebars are empty.
    if (empty($variables['page']['sidebar_first']) && empty($variables['page']['sidebar_second'])) {
      $attributes->addClass('layout--with-complementary');
    }
    // Sidebars logic.
    if (empty($variables['page']['complementary_top']) && empty($variables['page']['complementary_bottom'])) {
      if ($variables['page']['sidebar_first'] && $variables['page']['sidebar_second']) {
        $attributes->addClass('layout--with-three-columns');
      }
      if (!empty($variables['page']['sidebar_second']) xor !empty($variables['page']['sidebar_first'])) {
        $attributes->addClass('layout--with-two-columns');
      }
    }

    $variables['content_attributes'] = $attributes;

  }

}
