<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeleteGroup {

  public static function deleteGroupAndContent($nids, &$context) {
    $results = [];

    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $message = t('Delete @type "@title"', array('@type' => $node->getType(), '@title' => $node->getTitle()));
      $results[] = $node->delete();
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
      // TODO log to the database
    }
    else {
      $message = t('There was an unexpected error.');
      drupal_set_message($message, 'error');
    }
    return new RedirectResponse(Url::fromRoute('view.groups.page_user_groups')->setRouteParameter('user', \Drupal::currentUser()->id())->toString());
  }

}
