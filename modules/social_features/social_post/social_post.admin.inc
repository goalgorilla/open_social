<?php

/**
 * @file
 * Helper methods for post administration actions.
 */

use Drupal\Core\Link;
use Drupal\social_post\Entity\PostInterface;

/**
 * Updates all posts in the passed-in array with the passed-in field values.
 *
 * IMPORTANT NOTE: This function is intended to work when called from a form
 * submission handler. Calling it outside of the form submission process may not
 * work correctly.
 *
 * @param array $posts
 *   Array of post ids or posts to update.
 * @param array $updates
 *   Array of key/value pairs with post field names and the value to update that
 *   field to.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 * @param bool $load
 *   (optional) TRUE if $posts contains an array of post IDs to be loaded, FALSE
 *   if it contains fully loaded posts. Defaults to FALSE.
 */
function social_post_mass_update(array $posts, array $updates, $langcode = NULL, $load = FALSE) {
  // We use batch processing to prevent timeout when updating a large number
  // of posts.
  if (count($posts) > 10) {
    $batch = [
      'operations' => [
        [
          '_social_post_mass_update_batch_process',
          [$posts, $updates, $langcode, $load],
        ],
      ],
      'finished' => '_social_post_mass_update_batch_finished',
      'title' => t('Processing'),
      // We use a single multi-pass operation, so the default
      // 'Remaining x of y operations' message will be confusing here.
      'progress_message' => '',
      'error_message' => t('The update has encountered an error.'),
      // The operations do not live in the .module file, so we need to
      // tell the batch engine which file to load before calling them.
      'file' => drupal_get_path('module', 'social_post') . '/social_post.admin.inc',
    ];
    batch_set($batch);
  }
  else {
    $storage = \Drupal::entityTypeManager()->getStorage('post');
    if ($load) {
      $posts = $storage->loadMultiple($posts);
    }
    foreach ($posts as $post) {
      _social_post_mass_update_helper($post, $updates, $langcode);
    }
    \Drupal::messenger()->addStatus(t('The update has been performed.'));
  }
}

/**
 * Updates individual posts when fewer than 10 are queued.
 *
 * @param \Drupal\social_post\Entity\PostInterface $post
 *   A post to update.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   (optional) The language updates should be applied to. If none is specified
 *   all available languages are processed.
 *
 * @return \Drupal\social_post\Entity\PostInterface
 *   An updated post object.
 *
 * @see social_post_mass_update()
 */
function _social_post_mass_update_helper(PostInterface $post, array $updates, $langcode = NULL) {
  $langcodes = isset($langcode) ? [$langcode] : array_keys($post->getTranslationLanguages());
  // For efficiency manually save the original post before applying any changes.
  $post->original = clone $post;
  foreach ($langcodes as $langcode) {
    foreach ($updates as $name => $value) {
      $post->getTranslation($langcode)->$name = $value;
    }
  }
  $post->save();
  return $post;
}

/**
 * Implements callback_batch_operation().
 *
 * Executes a batch operation for social_post_mass_update().
 *
 * @param array $posts
 *   An array of post IDs.
 * @param array $updates
 *   Associative array of updates.
 * @param string $langcode
 *   The language updates should be applied to. If none is specified all
 *   available languages are processed.
 * @param bool $load
 *   TRUE if $posts contains an array of post IDs to be loaded, FALSE if it
 *   contains fully loaded posts.
 * @param array|\ArrayAccess $context
 *   An array of contextual key/values.
 */
function _social_post_mass_update_batch_process(array $posts, array $updates, $langcode, $load, &$context) {
  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = count($posts);
    $context['sandbox']['posts'] = $posts;
  }

  // Process posts by groups of 5.
  $storage = \Drupal::entityTypeManager()->getStorage('post');
  $count = min(5, count($context['sandbox']['posts']));
  for ($i = 1; $i <= $count; $i++) {
    // For each nid, load the post, reset the values, and save it.
    $post = array_shift($context['sandbox']['posts']);
    if ($load) {
      $post = $storage->load($post);
    }
    $post = _social_post_mass_update_helper($post, $updates, $langcode);

    // Store result for post-processing in the finished callback.
    $context['results'][] = Link::fromTextAndUrl($post->label(), $post->toUrl());

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
 * Reports the 'finished' status of batch operation
 * for social_post_mass_update().
 *
 * @param bool $success
 *   A boolean indicating whether the batch mass update operation successfully
 *   concluded.
 * @param string[] $results
 *   An array of rendered links to posts updated via the batch mode process.
 * @param array $operations
 *   An array of function calls (not used in this function).
 *
 * @see _social_post_mass_update_batch_process()
 */
function _social_post_mass_update_batch_finished($success, array $results, array $operations) {
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
