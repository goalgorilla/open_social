<?php

namespace Drupal\social_profile_privacy\Service;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SocialProfilePrivacyBatchHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Get profile name.
   *
   * @param \Drupal\profile\Entity\ProfileInterface|null $profile
   *   The profile.
   *
   * @return string|void
   *   The generated profile name value.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProfileName(ProfileInterface $profile = NULL) {
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');

    // Do nothing if no profile.
    if ($profile == NULL) {
      return '';
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $user_storage->load($profile->getOwnerId());

    // Set default profile name.
    $account_name = $account->getAccountName();

    // Get profile private fields list.
    $uid = $account->id();
    $private_fields_list = social_profile_privacy_private_fields_list($uid);

    $account_name_fields = [
      'field_profile_first_name',
      'field_profile_last_name',
    ];

    foreach ($account_name_fields as $key => $account_name_field) {
      if (in_array($account_name_field, $private_fields_list)) {
        unset($account_name_fields[$key]);
      }
      else {
        $account_name_fields[$key] = $profile->get($account_name_field)->getString();
      }
    }

    if (!empty($account_name_fields) && !empty($full_name = implode(" ", $account_name_fields))) {
      $account_name = $full_name;
    }

    if ($this->moduleHandler->moduleExists('social_profile_fields') && !in_array('field_profile_nick_name', $private_fields_list) && !empty($profile->get('field_profile_nick_name')->getString())) {
      $account_name = $profile->get('field_profile_nick_name')->getString();
    }

    return $account_name;
  }

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
          SocialProfilePrivacyBatchHelper::updateProfile($profile);
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
  public static function updateProfile(ProfileInterface $profile) {
    if ($profile instanceof ProfileInterface) {
      // We just need to save on the profile. The profile name will be updated
      // by hook "presave".
      // @see social_profile_privacy_profile_presave()
      $profile->save();
    }
  }

  /**
   * Get the list of the fields which can contain in the Profile name field.
   *
   * @return string[]
   *   List of the names of the fields.
   */
  public static function getProfileNameFields(): array {
    $fields = [
      'field_profile_first_name',
      'field_profile_last_name',
    ];
    if (\Drupal::moduleHandler()->moduleExists('social_profile_fields')) {
      $fields[] = 'field_profile_nick_name';
    }
    return $fields;
  }

}
