<?php

namespace Drupal\social_event_invite;

use Drupal\Core\Cache\Cache;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\user\UserInterface;

/**
 * Class SocialEventBulkInvite.
 *
 * @package Drupal\social_event_invite
 */
class SocialEventInviteBulkHelper {

  /**
   * Send the invites to existing users in a batch.
   *
   * @param array $users
   *   Array containing users.
   * @param string $nid
   *   The node id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function bulkInviteUsers(array $users, $nid) {
    $results = [];

    foreach ($users as $uid => $target_id) {
      // Default values.
      $fields = [
        'field_event' => $nid,
        'field_enrollment_status' => '0',
        'field_request_or_invite_status' => EventEnrollmentInterface::INVITE_PENDING_REPLY,
        'user_id' => $uid,
        'field_account' => $uid,
      ];

      // Check if this user has been invited before. It might be that the user
      // declined the invite, or that the invite is now invalid and expired.
      // We simply delete the outdated invite and create a new one.
      $conditions = [
        'field_account' => $uid,
        'field_event' => $nid,
      ];
      $eventEnrollmentStorage = \Drupal::entityTypeManager()->getStorage('event_enrollment');
      $existing_enrollment = $eventEnrollmentStorage->loadByProperties($conditions);

      if (!empty($existing_enrollment)) {
        /** @var EventEnrollment $enrollment */
        $enrollment = end($existing_enrollment);
        // Of course, only delete the previous invite if it was declined
        // or if it was invalid or expired.
        $status_checks = [
          EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED,
          EventEnrollmentInterface::INVITE_INVALID_OR_EXPIRED,
        ];
        if (in_array($enrollment->field_request_or_invite_status->value, $status_checks)) {
          $enrollment->delete();
        }
      }

      // Clear the cache.
      $tags = [];
      $tags[] = 'enrollment:' . $nid . '-' . $uid;
      $tags[] = 'event_content_list:entity:' . $uid;
      Cache::invalidateTags($tags);

      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create($fields);
      // In order for the notifications to be sent correctly we're updating the
      // owner here. The account is still linked to the actual enrollee.
      // The owner is always used as the actor.
      // @see activity_creator_message_insert().
      $enrollment->setOwnerId(\Drupal::currentUser()->id());
      $results[] = $enrollment->save();
    }

    $context['results'] = $results;
  }

  /**
   * Send the invites to emails in a batch.
   *
   * @param array $emails
   *   Array containing emails.
   * @param string $nid
   *   The node id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function bulkInviteEmails(array $emails, $nid) {
    $results = [];

    foreach ($emails as $email) {
      $user = user_load_by_mail($email);

      // Default values.
      $fields = [
        'field_event' => $nid,
        'field_enrollment_status' => '0',
        'field_request_or_invite_status' => EventEnrollmentInterface::INVITE_PENDING_REPLY,
      ];

      if ($user instanceof UserInterface) {
        // Add user information.
        $fields['user_id'] = $user->id();
        $fields['field_account'] = $user->id();

        // Clear the cache.
        $tags = [];
        $tags[] = 'enrollment:' . $nid . '-' . $user->id();
        $tags[] = 'event_content_list:entity:' . $user->id();
        Cache::invalidateTags($tags);
      }
      else {
        // Add email address.
        $fields['field_email'] = $email;
      }

      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create($fields);
      // In order for the notifications to be sent correctly we're updating the
      // owner here. The account is still linked to the actual enrollee.
      // The owner is always used as the actor.
      // @see activity_creator_message_insert().
      $enrollment->setOwnerId(\Drupal::currentUser()->id());
      $results[] = $enrollment->save();
    }

    $context['results'] = $results;
  }

}
