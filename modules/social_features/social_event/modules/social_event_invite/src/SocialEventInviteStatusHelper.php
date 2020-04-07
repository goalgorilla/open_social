<?php

namespace Drupal\social_event_invite;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Class SocialEventInviteStatusHelper.
 *
 * Providers service to get the enrollments for a user.
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
   * Custom check to see if a user has enrollments.
   *
   * @param string $user
   *   The email or userid you want to check on.
   * @param int $event
   *   The event id you want to check on, use 0 for all.
   *
   * @return array
   *   Returns the conditions for which to search event enrollments on.
   */
  public function userEnrollments($user, $event, $invite_status = NULL) {
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
      'field_request_or_invite_status' => EventEnrollmentInterface::INVITE_PENDING_REPLY,
    ];

    if ($user) {
      // Always assume the trigger is emails unless the ID is a user.
      $conditions = [
        'field_email' => $user,
        'field_event' => $nid,
      ];

      /** @var \Drupal\user\Entity\User $user */
      $account = User::load($user);
      if ($account instanceof UserInterface) {
        $conditions = [
          'field_account' => $account->id(),
          'field_event' => $nid,
          'field_request_or_invite_status' => EventEnrollmentInterface::INVITE_PENDING_REPLY,
        ];
      }
    }

    return $conditions;
  }

  /**
   * Custom check to get all enrollments for an event
   *
   * @param int $event
   *   The event id you want to check on.
   *
   * @return array
   *   Returns the conditions for which to search event enrollments on.
   */
  public function eventEnrollments($event, $invite_status = NULL) {
    $nid = $this->routeMatch->getRawParameter('node');

    if ($event) {
      $nid = $event;
    }

    // If there is no trigger get the enrollment for the current user.
    $conditions = [
      'field_event' => $nid,
      'field_request_or_invite_status' => EventEnrollmentInterface::INVITE_PENDING_REPLY,
    ];

    return $conditions;
  }

  /**
   * Custom check to see if a user has enrollments.
   *
   * @param string $user
   *   The email or userid you want to check on.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|mixed
   *   Returns all the enrollments for a user.
   */
  public function getAllUserEventEnrollments($user) {
    $conditions = $this->userEnrollments($user, NULL);

    unset($conditions['field_event']);

    return $this->entityTypeManager->getStorage('event_enrollment')
      ->loadByProperties($conditions);
  }

  /**
   * Custom check to see if a user has enrollments.
   *
   * @param string $user
   *   The email or userid you want to check on.
   * @param int $event
   *   The event id you want to check on.
   * @param bool $ignore_all_status
   *   Default FALSE, if set to TRUE then ignore any request_or_invite status.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Returns a specific event enrollment for a user.
   */
  public function getEventEnrollments($user, $event, $ignore_all_status = FALSE) {
    $conditions = $this->userEnrollments($user, $event);

    // If the $ignore_all_status parameter is TRUE, and we have the field
    // field_request_or_invite_status in our $conditions, unset this field.
    if ($ignore_all_status === TRUE && isset($conditions['field_request_or_invite_status'])) {
      unset($conditions['field_request_or_invite_status']);
    }

    return $this->entityTypeManager->getStorage('event_enrollment')
      ->loadByProperties($conditions);
  }


  /**
   * Custom check to get all enrollments for an event
   *
   * @param int $event
   *   The event id you want to check on.
   * @param bool $ignore_all_status
   *   Default FALSE, if set to TRUE then ignore any request_or_invite status.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Returns all enrollments for an event.
   */
  public function getAllEventEnrollments($event, $ignore_all_status = FALSE) {
    $conditions = $this->eventEnrollments( $event);

    // If the $ignore_all_status parameter is TRUE, and we have the field
    // field_request_or_invite_status in our $conditions, unset this field.
    if ($ignore_all_status === TRUE && isset($conditions['field_request_or_invite_status'])) {
      unset($conditions['field_request_or_invite_status']);
    }

    return $this->entityTypeManager->getStorage('event_enrollment')
      ->loadByProperties($conditions);
  }

}
