<?php

namespace Drupal\social_group\Controller;

use Drupal\node\Entity\Node;

class DeleteNode {

  public static function deleteNodeExample($nids, &$context) {
    drupal_set_message(t('Scraping all that stuff...', 'status'));
    $message = 'Deleting THE SHIT OUT OF IT...';
    $results = [];
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      $results[] = $node->delete();
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  function deleteNodeExampleFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One post processed.', '@count posts processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
