<?php

namespace Drupal\social_group_invite;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class SocialGroupInviteAccessHelper.
 *
 * @package Drupal\social_group_invite\Access
 */
class SocialGroupInviteAccessHelper {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * SocialGroupInvitesAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(RouteMatchInterface $routeMatch, ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser) {
    $this->routeMatch = $routeMatch;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
  }

  /**
   * Custom access check for the user invite overview.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns the access result.
   */
  public function userInviteAccess() {
    // @todo At the moment, we allow user to access only own group invites.
    // Additional permissions will be added later.
    // $config = $this->configFactory->get('social_group_invite.settings');
    // $enabled_global = $config->get('invite_enroll');
    //
    // // If it's globally disabled, we don't want to show the block.
    // if (!$enabled_global) {
    // return AccessResult::forbidden();
    // }
    // Get the user.
    $account = $this->routeMatch->getRawParameter('user');
    if (!empty($account)) {
      $account = User::load($account);
      if ($account instanceof UserInterface) {
        return AccessResult::allowedIf($account->id() === $this->currentUser->id());
      }
    }

    return AccessResult::neutral();
  }

}
