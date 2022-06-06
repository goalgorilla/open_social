<?php

/**
 * @file
 * Contains batch callbacks for the social_follow_user module.
 */

/**
 * Implements callback_batch_operation().
 *
 * @param array $uids
 *   An array of users IDs.
 * @param mixed $context
 *   Batch context information.
 */
function _social_follow_user_enable_following_status_batch_process(array $uids, &$context): void {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($uids);
    $context['sandbox']['uids'] = $uids;
  }

  $count = min(50, count($context['sandbox']['uids']));
  for ($i = 1; $i <= $count; $i++) {
    $uid = array_shift($context['sandbox']['uids']);
    \Drupal::service('social_follow_user.helper')
      ->setFollowingStatus((int) $uid);

    // Update our progress information.
    $context['sandbox']['progress']++;
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
}

/**
 * Implements callback_batch_finished().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param array $results
 *   An array of rendered links to questions imported via the batch mode
 *   process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 */
function _social_follow_user_enable_following_status_batch_finished(bool $success, array $results, array $operations): void {
  if ($success) {
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
  else {
    \Drupal::messenger()->addError(t('An error occurred and processing did not complete.'));
    $message = \Drupal::translation()->formatPlural(count($results), '1 item successfully processed:', '@count items successfully processed:');
    $item_list = [
      '#theme' => 'item_list',
      '#items' => $results,
    ];
    $message .= \Drupal::service('renderer')->render($item_list);
    \Drupal::messenger()->addStatus($message);
  }
}
