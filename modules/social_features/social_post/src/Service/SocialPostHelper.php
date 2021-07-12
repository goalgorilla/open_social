<?php

namespace Drupal\social_post\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class SocialPostHelper.
 *
 * @package Drupal\social_post\Service
 */
class SocialPostHelper implements SocialPostHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * SocialPostHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCurrentUserImage() {
    $storage = $this->entityTypeManager->getStorage('profile');

    if (!empty($storage)) {
      // Load current user.
      $account = $this->currentUser->getAccount();

      if ($user_profile = $storage->loadByUser($account, 'profile')) {
        // Load compact notification view mode of the attached profile.
        return $this->entityTypeManager->getViewBuilder('profile')
          ->view($user_profile, 'compact_notification');
      }
    }

    return NULL;
  }

}
