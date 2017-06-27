<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\social_post\Entity\Post;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeleteGroup {

  public static function deleteGroupAndContent($nids, $posts, &$context) {
    $results = [];
    // Load each node and delete it.
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $message = t('Delete @type "@title"', array('@type' => $node->getType(), '@title' => $node->getTitle()));
      $results[] = $node->delete();
    }
    // Load each post and delete it.
    foreach ($posts as $post_id) {
      $post = Post::load($post_id);
      $message = t('Deleting @type\'s', array('@type' => $post->bundle()));
      $results[] = $post->delete();
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  function deleteGroupAndContentFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One item deleted.', '@count items deleted.'
      );
      // Provide some feedback when its a success
      drupal_set_message(t('Your group and all of it\'s topic\'s, event\'s and post\'s have been deleted.'));
      // TODO log to the database
    }
    else {
      $message = t('There was an unexpected error.');
      drupal_set_message($message, 'error');
    }
    // Redirect the user back to their groups overview once the batch is done.
    return new RedirectResponse(Url::fromRoute('view.groups.page_user_groups')->setRouteParameter('user', \Drupal::currentUser()->id())->toString());
  }

}
