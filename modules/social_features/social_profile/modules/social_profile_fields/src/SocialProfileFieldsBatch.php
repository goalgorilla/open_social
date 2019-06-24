<?php

namespace Drupal\social_profile_fields;

use Drupal\Core\Cache\Cache;
use Drupal\profile\Entity\Profile;
use Drupal\search_api\Entity\Index;

/**
 * Class SocialProfileFieldsBatch.
 *
 * Empty profile fields in batch.
 *
 * @package Drupal\social_profile_fields
 */
class SocialProfileFieldsBatch {

  /**
   * Perform the flush.
   *
   * @param array $pids
   *   Profile id's.
   * @param array $fields
   *   An array of fields to empty.
   * @param array $context
   *   The context of the flush.
   */
  public static function performFlush(array $pids, array $fields, array &$context) {
    $message = 'Flushing profile data...';

    $results = [];

    foreach ($pids as $pid) {
      $profile = Profile::load($pid);

      foreach ($fields as $field_name) {
        // Check if the field exists.
        if ($profile->hasField($field_name)) {
          // Empty the field.
          $profile->set($field_name, '');
        }
      }
      // Save the profile.
      $results[] = $profile->save();
      // Oh and also clear the profile cache while we're at it.
      Cache::invalidateTags(['profile:' . $profile->id()]);
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  /**
   * Message when done.
   *
   * @param bool $success
   *   If the operation was a success.
   * @param array $results
   *   The amount of items done.
   * @param string $operations
   *   The operation performed.
   */
  public static function performFlushFinishedCallback($success, array $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One profile flushed.', '@count profiles flushed.'
      );

      $indexes = Index::loadMultiple(['social_all', 'social_users']);
      /** @var \Drupal\search_api\Entity\Index $index */
      foreach ($indexes as $index) {
        // If the search index is on and items are not indexed immediately, the
        // index also needs to be flushed and re-indexed.
        if ($index !== NULL && $index->status() && !$index->getOption('index_directly')) {
          $index->clear();
          $index->reindex();
        }
      }
    }
    else {
      $message = t('Whoops... something went wrong!');
    }

    drupal_set_message($message);
  }

}
