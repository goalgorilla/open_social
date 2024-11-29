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
    $count = parent::populateQueue($data, $context);

    // Check if action has validation callback that checks if user is subscribed
    // for receiving emails. If not, then remove the item from processed queue.
    if (!isset($this->bulkFormData['validate_email_subscriptions_callback'])) {
      return $count;
    }

    if (!method_exists($this->action, $method = $this->bulkFormData['validate_email_subscriptions_callback'])) {
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
