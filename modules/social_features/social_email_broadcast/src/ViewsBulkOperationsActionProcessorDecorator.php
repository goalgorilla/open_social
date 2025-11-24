<?php

namespace Drupal\social_email_broadcast;

use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessor as Inner;

/**
 * Decorates VBO action processor service.
 */
class ViewsBulkOperationsActionProcessorDecorator extends Inner {

  /**
   * {@inheritdoc}
   */
  public function populateQueue(array $data, array &$context = []): int {
    // Ensure batch_size is an integer to prevent TypeError in array_slice()
    // when PHP 8.3+ strict typing is enforced. The value may come as a string
    // from Drupal's configuration system.
    if (isset($data['batch_size'])) {
      $data['batch_size'] = (int) $data['batch_size'];
    }

    $count = parent::populateQueue($data, $context);

    // Check if action has validation callback that checks if user is subscribed
    // for receiving emails. If not, then remove the item from processed queue.
    if (!isset($this->bulkFormData['validate_email_subscriptions_callback'])) {
      return $count;
    }

    if ($this->action === NULL) {
      return $count;
    }

    $method = $this->bulkFormData['validate_email_subscriptions_callback'];
    if (!method_exists($this->action, $method)) {
      return $count;
    }

    foreach ($this->queue as $key => $entity) {
      $is_valid = $this->action->{$method}($entity);
      if (!$is_valid) {
        unset($this->queue[$key]);

        if (isset($context['results']['removed_selections']['count'])) {
          $context['results']['removed_selections']['count']++;
        }
      }
    }

    return \count($this->queue);
  }

}
