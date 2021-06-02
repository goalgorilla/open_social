<?php

namespace Drupal\social_profile_privacy\Service;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Class SocialProfilePrivacyBatchHelper.
 *
 * Update profile names in batch.
 *
 * @package Drupal\social_profile_privacy
 */
class SocialProfilePrivacyBatchHelper {

  /**
   * Update profile names in a batch.
   */
  public static function bulkUpdateProfileNames() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

    $pids = $profile_storage->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    // Define batch process to update profile names.
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Updating profile names...'))
      ->setFinishCallback([
        SocialProfilePrivacyBatchHelper::class,
        'finishProcess',
      ])
      ->addOperation([SocialProfilePrivacyBatchHelper::class, 'updateProcess'], [$pids]);

    batch_set($batch_builder->toArray());
  }

  /**
   * Process operation to update content retrieved from init operation.
   *
   * @param array $items
   *   Items.
   * @param array $context
   *   An array that may or may not contain placeholder variables.
   */
  public static function updateProcess(array $items, array &$context) {
    // Elements per operation.
    $limit = 50;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($items);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $items;
    }

    if (!empty($context['sandbox']['items'])) {
      /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

      // Get items for processing.
      $current_pids = array_splice($context['sandbox']['items'], 0, $limit);

      // Load profiles by profiles IDs.
      $profiles = $profile_storage->loadMultiple($current_pids);

      foreach ($profiles as $profile) {
        if ($profile instanceof ProfileInterface) {
          SocialProfilePrivacyBatchHelper::updateProfileName($profile);
        }

        $context['sandbox']['progress']++;

        $context['message'] = t('Now processing profile :progress of :count', [
          ':progress' => $context['sandbox']['progress'],
          ':count' => $context['sandbox']['max'],
        ]);

        // Increment total processed item values. Will be used in finished
        // callback.
        $context['results']['processed'] = $context['sandbox']['progress'];
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Callback for finished batch events.
   *
   * @param bool $success
   *   TRUE if the update was fully succeeded.
   * @param array $results
   *   Contains individual results per operation.
   * @param array $operations
   *   Contains the unprocessed operations that failed or weren't touched yet.
   */
  public static function finishProcess($success, array $results, array $operations) {
    $message = t('Number of profiles affected by batch: @count', [
      '@count' => $results['processed'],
    ]);

    \Drupal::messenger()
      ->addStatus($message);
  }

  /**
   * Update single Profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   */
  public static function updateProfileName(ProfileInterface $profile) {
    if ($profile instanceof ProfileInterface) {
      /** @var \Drupal\social_profile\SocialProfileNameService $profile_name_service */
      $profile_name_service = \Drupal::service('social_profile.name_service');

      // Get generated profile name.
      $profile_name = $profile_name_service->getProfileName($profile);
      // Update profile name and save.
      $profile->set('profile_name', $profile_name);
      $profile->save();
    }
  }

}
