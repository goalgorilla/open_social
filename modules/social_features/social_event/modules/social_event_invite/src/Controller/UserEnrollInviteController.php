<?php

namespace Drupal\social_event_invite\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Accepts or declines an event enrollment invite.
 *
 * @package Drupal\social_event_invite\Controller
 */
class UserEnrollInviteController extends CancelEnrollInviteController {

  /**
   * {@inheritdoc}
   */
  public function updateEnrollmentInvite(EventEnrollmentInterface $event_enrollment, $accept_decline) {
    // Just some sanity checks.
    if (!empty($event_enrollment)) {
      // When the user accepted the invite,
      // we set the field_request_or_invite_status to approved.
      if ($accept_decline === '1') {
        $event_enrollment->field_request_or_invite_status->value = EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED;
        $event_enrollment->field_enrollment_status->value = '1';
        $statusMessage = $this->getMessage($event_enrollment, $accept_decline);
        if (!empty($statusMessage)) {
          // Lets delete all messages to keep the messages clean.
          $this->messenger()->deleteAll();
          $this->messenger()->addStatus($statusMessage);
        }
      }
      // When the user declined,
      // we set the field_request_or_invite_status to decline.
      elseif ($accept_decline === '0') {
        $event_enrollment->field_request_or_invite_status->value = EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED;
        $statusMessage = $this->getMessage($event_enrollment, $accept_decline);
        if (!empty($statusMessage)) {
          // Lets delete all messages to keep the messages clean.
          $this->messenger()->deleteAll();
          $this->messenger()->addStatus($statusMessage);
        }
      }

      // And finally save (update) this updated $event_enrollment.
      // @todo maybe think of deleting approved/declined records from the db?
      $event_enrollment->save();

      // Invalidate cache.
      $tags = [];
      $tags[] = 'enrollment:' . $event_enrollment->field_event->value . '-' . $this->currentUser->id();
      $tags[] = 'event_content_list:entity:' . $this->currentUser->id();
      Cache::invalidateTags($tags);
    }

    // Get the redirect destination we're given in the request for the response.
    $destination = Url::fromRoute('view.user_event_invites.page_user_event_invites', ['user' => $this->currentUser->id()])->toString();

    return new RedirectResponse($destination);
  }

  /**
   * Generates a nice message for the user.
   *
   * @param \Drupal\social_event\EventEnrollmentInterface $event_enrollment
   *   The event enrollment.
   * @param string $accept_decline
   *   The approve (1) or decline (0) number.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The message.
   */
  public function getMessage(EventEnrollmentInterface $event_enrollment, $accept_decline) {
    $statusMessage = NULL;
    // Get the target event id.
    $target_event_id = $event_enrollment->get('field_event')->getValue();
    // Get the event node.
    $event = $this->entityTypeManager()->getStorage('node')->load($target_event_id[0]['target_id']);

    // Only if we have an event, we perform the rest of the logic.
    if (!empty($event)) {
      // Build the link to the event node.
      $link = Link::createFromRoute($this->t('@node', ['@node' => $event->get('title')->value]), 'entity.node.canonical', ['node' => $event->id()])
        ->toString();
      // Nice message with link to the event the user has enrolled in.
      if (!empty($event->get('title')->value) && $accept_decline === '1') {
        $statusMessage = $this->t('You have accepted the invitation for the @event event.', ['@event' => $link]);
      }
      // Nice message with link to the event the user has respectfully declined.
      elseif (!empty($event->get('title')->value) && $accept_decline === '0') {
        $statusMessage = $this->t('You have declined the invitation for the @event event.', ['@event' => $link]);
      }
    }

    return $statusMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    // Get the parameter from the request that has been done.
    $user_parameter = $this->requestStack->getCurrentRequest()->attributes->get('user');
    // Check if it's the same that is in the current session's account.
    if ($account->id() === $user_parameter) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
