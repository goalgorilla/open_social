<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\social_post\Entity\Post;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DeleteGroup.
 *
 * @package Drupal\social_group\Controller
 */
class DeleteGroup {

  /**
   * Get the group and all of its content that needs to be deleted.
   */
  public static function deleteGroupAndContent($nids, $posts, &$context) {
    $results = [];
    // Load all nodes and delete them.
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $message = t('Delete @type "@title"', ['@type' => $node->getType(), '@title' => $node->getTitle()]);
      $results[] = $node->delete();
    }
    // Load each post and delete it.
    $posts = Post::loadMultiple($posts);
    foreach ($posts as $post) {
      $message = t("Deleting @type's", ['@type' => $post->bundle()]);
      $results[] = $post->delete();
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * Callback when the batch for group and content deletion is done.
   */
  public static function deleteGroupAndContentFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One item deleted.', '@count items deleted.'
      );
      // Provide some feedback when its a success.
      \Drupal::messenger()->addStatus(t('Your group and all of its topics, events and posts have been deleted.'));
      // @todo log to the database.
    }
    else {
      $message = t('There was an unexpected error.');
      \Drupal::messenger()->addError($message);
    }
    // Redirect the user back to their groups overview once the batch is done.
    return new RedirectResponse(Url::fromRoute('view.groups.page_user_groups')->setRouteParameter('user', \Drupal::currentUser()->id())->toString());
  }

}
