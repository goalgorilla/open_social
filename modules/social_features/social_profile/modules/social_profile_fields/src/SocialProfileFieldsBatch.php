<?php
/**
 * Created by PhpStorm.
 * User: jochem
 * Date: 20/02/2018
 * Time: 12:17
 */

namespace Drupal\social_profile_fields;

use Drupal\Core\Cache\Cache;
use Drupal\profile\Entity\Profile;

class SocialProfileFieldsBatch {

  public static function performFlush($pids, $fields, &$context) {
    $message = 'Flushing profile data...';

    $results = array();

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
      Cache::invalidateTags(['profile:'.$profile->id()]);
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  function performFlushFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One profile flushed.', '@count profiles flushed.'
      );
    }
    else {
      $message = t('Whoops... something went wrong!');
    }

    drupal_set_message($message);
  }
}
