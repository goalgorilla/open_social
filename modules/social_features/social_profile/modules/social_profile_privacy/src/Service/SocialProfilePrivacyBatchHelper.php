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

  public function getProfileName(ProfileInterface $profile = NULL) {
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager->getStorage('user');
    /** @var \Drupal\user\UserInterface $account */
    $account = $user_storage->load($profile->getOwnerId());

    // Do nothing if no account.
    if ($account == NULL) {
      return;
    }

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
    // Define batch process to update profile names.
    $batch_builder = (new BatchBuilder())
      ->setTitle(t('Updating profile names...'))
      ->setFinishCallback([SocialProfilePrivacyBatchHelper::class, 'finishProcess'])
      ->addOperation([SocialProfilePrivacyBatchHelper::class, 'initOperation'], [
          ['limit' => 50],
        ]
      )
      ->addOperation([SocialProfilePrivacyBatchHelper::class, 'updateProcess'], [
        ['limit' => 50],
      ]);

    batch_set($batch_builder->toArray());
  }

  /**
   * Init operation task by retrieving all content to be updated.
   *
   * @param array $args
   *   Arguments.
   * @param array $context
   *   An array that may or may not contain placeholder variables.
   */
  public static function initOperation(array $args, array &$context) {
    // Init variables.
    $limit = $args['limit'];
    $offset = (!empty($context['sandbox']['offset'])) ?
      $context['sandbox']['offset'] : 0;

    $userStorage = \Drupal::entityTypeManager()->getStorage('user');

    $uids = $userStorage->getQuery()
      ->condition('status', '1')
      ->execute();
    /** @var array $results */
    $results = $userStorage->loadMultiple($uids);

    // Define total on first call.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = count($results);
    }

    // Setup results based on retrieved objects.
    $context['results'] = array_reduce($results,
      function ($carry, $object) {
        // Map object results extracted from previous query.
        $carry[$object->id()] = $object;
        return $carry;
      }, $context['results']
    );

    // Redefine offset value.
    $context['sandbox']['offset'] = $offset + $limit;

    // Set current step as unfinished until offset is greater than total.
    $context['finished'] = 0;
    if ($context['sandbox']['offset'] >= $context['sandbox']['total']) {
      $context['finished'] = 1;
    }

    // Setup info message to notify about current progress.
    $context['message'] = t(
      'Retrieved @consumed of @total available profiles',
      [
        '@consumed' => $context['sandbox']['offset'],
        '@total' => $context['sandbox']['total'],
      ]
    );
  }
  /**
   * Process operation to update content retrieved from init operation.
   *
   * @param array $args
   *   Arguments.
   * @param array $context
   *   An array that may or may not contain placeholder variables.
   */
  public static function updateProcess(array $args, array &$context) {
    // Define total on first call.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = count($context['results']);
    }

    // Init limit variable.
    $limit = $args['limit'];

    // Walk-through all results in order to update them.
    $count = 0;

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

    foreach ($context['results'] as $key => $account) {
      if (!empty($profile_storage)) {
        $profile = $profile_storage->loadByUser($account, 'profile');
        if ($profile instanceof ProfileInterface) {
          // We need just save the profile. The profile name will be updated by
          // hook "presave".
          // @see social_profile_privacy_profile_presave()
          $profile->save();
        }
      }

      // Increment count at one.
      $count++;

      // Remove current result.
      unset($context['results'][$key]);
      if ($count >= $limit) {
        break;
      }
    }

    // Setup message to notify how many remaining profiles.
    $context['message'] = t(
      'Updating profile names... @total pending...',
      ['@total' => count($context['results'])]
    );

    // Set current step as unfinished until there's not results.
    $context['finished'] = (empty($context['results']));

    // When it is completed, then setup result as total amount updated.
    if ($context['finished']) {
      $context['results'] = $context['sandbox']['total'];
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
  public static function finishProcess($success, $results, $operations) {
    // Setup final message after process is done.
    $message = ($success) ?
      t('Update process of @count profiles was completed.',
        ['@count' => $results]) :
      t('Finished with an error.');
    \Drupal::messenger()->addMessage($message);
  }

}
