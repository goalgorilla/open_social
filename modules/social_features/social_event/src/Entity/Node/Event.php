<?php

namespace Drupal\social_event\Entity\Node;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Drupal\meeting_api\MeetingAttendee;
use Drupal\meeting_api\MeetingAttendeeInterface;
use Drupal\meeting_api\MeetingInterface;
use Drupal\meeting_api\MeetingManagerInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_node\Entity\Node as Node;
use Drupal\user\UserInterface;

/**
 * Defines bundle class for the "event" node type.
 */
class Event extends Node implements EventInterface {

  /**
   * {@inheritdoc}
   */
  public function isEnrollmentEnabled(): bool {
    // Get global event settings.
    $settings = \Drupal::config('social_event.settings');
    if (
      $settings->get('disable_event_enroll') ||
      !$this->hasField('field_event_enroll') ||
      (!$this->get('field_event_enroll')->isEmpty() && !$this->get('field_event_enroll')->getString())
    ) {
      return FALSE;
    }

    // When field was added to the event, the value becomes `false` which
    // creates some inconsistent.
    $was_not_changed = $this->get('field_event_enroll')->isEmpty();
    $is_enabled = (bool) $this->get('field_event_enroll')->getString();

    // Make an exception for the invite enroll method.
    // This doesn't allow people to enroll themselves, but get invited.
    if (
      !$this->get('field_enroll_method')->isEmpty() &&
      (int) $this->get('field_enroll_method')->getString() === EventEnrollmentInterface::ENROLL_METHOD_INVITE
    ) {
      $is_enabled = TRUE;
    }

    return $was_not_changed || $is_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function showEnrollments(): bool {
    // Enrollments can be show only in case when enrolls are enabled and
    // show status as well.
    return $this->isEnrollmentEnabled() &&
      !$this->get('field_hide_enrollments')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function isStarted(): bool {
    $current_time = new DrupalDateTime();

    // Get the event start date.
    if ($this->get('field_event_date')->isEmpty()) {
      // Cannot determine if started without a start date.
      return FALSE;
    }

    $start_date = $this->get('field_event_date')->date;
    if (!$start_date instanceof DrupalDateTime) {
      return FALSE;
    }

    // Check if the all-day checkbox is activated.
    $is_all_day = !$this->get('field_event_all_day')
      ->isEmpty() && $this->get('field_event_all_day')->getString();

    // For all-day events, consider started if current date >= start date.
    if ($is_all_day) {
      return $current_time->format('Y-m-d') >= $start_date->format('Y-m-d');
    }

    // For timed events, compare exact timestamps.
    return $current_time->getTimestamp() >= $start_date->getTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnded(): bool {
    $current_time = new DrupalDateTime();

    // Use the start date when the end date is not set to determine if the event
    // is closed.
    $check_end_date = $this->get('field_event_date_end')->isEmpty()
      ? $this->get('field_event_date')->date
      : $this->get('field_event_date_end')->date;

    if (!$check_end_date instanceof DrupalDateTime) {
      // Not possible to detect the end date.
      return FALSE;
    }

    // Check if the all-day checkbox is activated.
    $is_all_day = !$this->get('field_event_all_day')
      ->isEmpty() && $this->get('field_event_all_day')->getString();

    // The event has finished if the end date is smaller than the current date,
    // and if the all-day checkbox isn't activated,
    // and the end date is not equal to the current date.
    return $current_time->getTimestamp() > $check_end_date->getTimestamp() &&
      !($is_all_day && $check_end_date->format('Y-m-d') === $current_time->format('Y-m-d'));
  }

  /**
   * {@inheritdoc}
   */
  public function isHappeningNow(): bool {
    // Event is happening now if it has started but not ended.
    return $this->isStarted() && !$this->isEnded();
  }

  /**
   * {@inheritdoc}
   */
  public function startsIn(): int {
    $current_time = new DrupalDateTime();

    // Get the event start date.
    if ($this->get('field_event_date')->isEmpty()) {
      // Cannot determine time until start without a start date.
      return 0;
    }

    $start_date = $this->get('field_event_date')->date;

    if (!$start_date instanceof DrupalDateTime) {
      return 0;
    }

    // If the event has already started, return 0.
    if ($this->isStarted()) {
      return 0;
    }

    // Return the difference in seconds.
    // Wrap to max function to prevent negative values.
    return max($start_date->getTimestamp() - $current_time->getTimestamp(), 0);
  }

  /**
   * {@inheritdoc}
   */
  public function isOnline(): bool {
    // An event is considered online if it has a meeting configured.
    return $this->hasField('field_event_meeting') &&
      !$this->get('field_event_meeting')->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function joiningMeetingIsOpen(): bool {
    // Check if the event starts within 10 minutes (600 seconds) or
    // is happening now.
    $starts_in = $this->startsIn();
    return $starts_in && $starts_in <= 600 || $this->isHappeningNow();
  }

  /**
   * {@inheritdoc}
   */
  public function getMeeting(): ?MeetingInterface {
    // Return the meeting entity if the event is online.
    if (!$this->isOnline()) {
      return NULL;
    }

    $meeting = $this->get('field_event_meeting')->entity;

    return $meeting instanceof MeetingInterface ? $meeting : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipation(?AccountInterface $account = NULL): bool {
    if ($account === NULL) {
      $account = \Drupal::currentUser();
    }

    $enrollments_storage = \Drupal::entityTypeManager()
      ->getStorage('event_enrollment');

    $eid = (array) $enrollments_storage->getQuery()
      // @todo Not sure if we need to check the access here.
      ->accessCheck()
      ->condition('field_account', $account->id())
      ->condition('field_event', $this->id())
      ->execute();

    // We need the last one.
    $eid = array_pop($eid);
    if (!$eid) {
      return FALSE;
    }

    /** @var \Drupal\social_event\EventEnrollmentInterface $enrollment */
    $enrollment = $enrollments_storage->load($eid);

    return $enrollment->isEnrolled();
  }

  /**
   * {@inheritdoc}
   */
  public function getMeetingLink(AccountInterface|UserInterface|null $account = NULL): ?string {
    if (!$this->isOnline()) {
      return NULL;
    }

    if (!$account instanceof UserInterface) {
      $account = $account instanceof AccountInterface
        ? $account
        : \Drupal::currentUser();

      $account = \Drupal::entityTypeManager()->getStorage('user')
        ->load($account->id());
    }

    if (!$account instanceof UserInterface) {
      return NULL;
    }

    // @todo This check could be redundant check with meeting entity
    //   will be able to detect the attendees list.
    if (!$this->getParticipation($account)) {
      return NULL;
    }

    if (!$meeting = $this->getMeeting()) {
      return NULL;
    }

    // @todo Probably, this would be a part of meeting_api module
    //   but for the moment we need to wrap the user to attendee class.
    $attendee = new MeetingAttendee($account, MeetingAttendeeInterface::ATTENDEE_ROLE);

    /** @var \Drupal\meeting_api\MeetingManagerInterface $meeting_api_manager */
    $meeting_api_manager = \Drupal::service(MeetingManagerInterface::class);

    return $meeting_api_manager->joinMeeting($meeting, $attendee);
  }

}
