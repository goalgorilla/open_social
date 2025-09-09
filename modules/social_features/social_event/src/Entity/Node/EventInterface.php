<?php

namespace Drupal\social_event\Entity\Node;

use Drupal\Core\Session\AccountInterface;
use Drupal\meeting_api\Entity\Meeting;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface for the "Event" node bundle class.
 *
 * @ingroup social_event
 */
interface EventInterface extends NodeInterface {

  /**
   * Check if enrollment is allowed for the event.
   *
   * @return bool
   *   TRUE if enrollment is allowed.
   */
  public function isEnrollmentEnabled(): bool;

  /**
   * Check if access to enrollments is open.
   *
   * @return bool
   *   The access status.
   */
  public function showEnrollments(): bool;

  /**
   * Determine if the event has been started.
   *
   * @return bool
   *   TRUE if the event is started, FALSE otherwise.
   */
  public function isStarted(): bool;

  /**
   * Determine if the event has been finished.
   *
   * @return bool
   *   TRUE if the event is finished, FALSE otherwise.
   */
  public function isEnded(): bool;

  /**
   * Determine if the event is happening now.
   *
   * @return bool
   *   TRUE if the event is continuing now, FALSE otherwise.
   */
  public function isHappeningNow(): bool;

  /**
   * Get the time in timestamp remaining until the event starts.
   *
   * @return int
   *   The timestamp before the event starts.
   */
  public function startsIn(): int;

  /**
   * Determine if the event is online.
   *
   * @return bool
   *   TRUE if the event is online, FALSE otherwise.
   */
  public function isOnline(): bool;

  /**
   * Determines if users can start joining the meeting.
   *
   * @return bool
   *   Returns true if the event starts within 10 minutes or
   *   is currently happening, false otherwise.
   */
  public function joiningMeetingIsOpen(): bool;

  /**
   * Returns the meeting entity if the event is online.
   *
   * @return \Drupal\meeting_api\Entity\Meeting|null
   *   The meeting entity or NULL.
   */
  public function getMeeting(): ?Meeting;

  /**
   * Retrieve the meeting link associated with an online event.
   *
   * @return string|null
   *   The meeting link as a string if available, or NULL if no link is set.
   */
  public function getMeetingLink(AccountInterface|UserInterface|null $account = NULL): ?string;

  /**
   * Check if the given account has participation.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to check for participation.
   *   If NULL, the current account will be used.
   *
   * @return bool
   *   TRUE if the account has participation, FALSE otherwise.
   */
  public function getParticipation(?AccountInterface $account = NULL): bool;

}
