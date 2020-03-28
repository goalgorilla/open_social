<?php

namespace Drupal\social_event_invite;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class SocialEventInviteStatusHelper
 */
class SocialEventInviteStatusHelper {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * EventInvitesAccess constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
  }

  /**
   * Custom check to see if a user has enrollments
   *
   * @param $user string
   *   The email or userid you want to check on.
   * @param $event integer
   *   The event id you want to check on, use 0 for all.
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed
   */
  public function userEnrollments($user, $event) {
    $current_user = $this->currentUser;
    $uid = $current_user->id();
    $nid = $this->routeMatch->getRawParameter('node');
    
    if ($event) {
      $nid = $event;
    }

    // If there is no trigger get the enrollment for the current user.
    $conditions = [
      'field_account' => $uid,
      'field_event' => $nid,
    ];

    if($user) {
      // Always assume the trigger is emails unless the ID is a user.
      $conditions = [
        'field_email' => $user,
        'field_event' => $nid,
      ];

      /** @var \Drupal\user\Entity\User $user */
      $account = User::load($user);
      if ($account InstanceOf UserInterface) {
        $conditions = [
          'field_account' => $account->id(),
          'field_event' => $nid,
        ];
      }
    }

    // If the event id is 0 then search for all invites.
    if ($event === 0) {
      unset($conditions['field_event']);
    }

    $enrollments = $this->entityTypeManager->getStorage('event_enrollment')->loadByProperties($conditions);

    return $enrollments;
  }
}
