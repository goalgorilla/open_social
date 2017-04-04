<?php

use Drupal\node\Entity\Node;

/**
 * Implements hook_preprocess_page().
 */
function socialbase_preprocess_page(&$variables) {
  $variables['display_page_title'] = TRUE;

  // If we have the admin toolbar on screen we need overrides for our styles.
  if ($variables['is_admin']) {
    $variables['#attached']['library'][] = 'socialbase/admin-toolbar';
  }

  // Add plain title for node preview page templates.
  if (!empty($variables['page']['#title'])) {
    $variables['plain_title'] = strip_tags($variables['page']['#title']);
  }

  // Hide page title for pages where we want to display it in the Hero instead, like event, topic, basic page.

  // Determine if we are looking at a node

  $nid = \Drupal::routeMatch()->getRawParameter('node');
  $node = FALSE;

  if (!is_null($nid) && !is_object($nid)) {
    $node = Node::load($nid);
  }

  if ($node instanceof Node) {
    // list pages where we want to hide the default page title
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

/**
 * Implements hook_preprocess_page_title().
 */
function socialbase_preprocess_page_title(&$variables) {
  // Get the current path and if is it stream return a variable.
  $current_path = \Drupal::service('path.current')->getPath();
  if (strpos($current_path, 'stream') !== FALSE || strpos($current_path, 'explore') !== FALSE) {
    $variables['stream'] = TRUE;
  }

  // check if we are a node
  if (strpos($current_path, 'node') !== FALSE) {
    $variables['node'] = TRUE;
  }

  // check if we are on the node/edit or node/add
  if (strpos($current_path, 'edit') !== FALSE || strpos($current_path, 'add') !== FALSE) {
    $variables['edit'] = TRUE;
  }

}