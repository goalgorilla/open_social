<?php

namespace Drupal\social_event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\node\NodeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Event enrollment entities.
 *
 * @ingroup social_event
 */
interface EventEnrollmentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Event enrollment method where users can directly enroll.
   */
  const ENROLL_METHOD_JOIN = 1;

  /**
   * Event enrollment method where users need to request enrollment.
   */
  const ENROLL_METHOD_REQUEST = 2;

  /**
   * Event enrollment method where users need to get invited.
   */
  const ENROLL_METHOD_INVITE = 3;

  /**
   * Request created and waiting for event owners or managers response.
   */
  const REQUEST_PENDING = 0;

  /**
   * Request approved by event owner or manager.
   */
  const REQUEST_APPROVED = 1;

  /**
   * Request or invite declined by event owner, manager or user.
   */
  const REQUEST_OR_INVITE_DECLINED = 2;

  /**
   * Invited, a status to check if a user has been invited.
   */
  const INVITE_INVITED = 3;

  /**
   * Invite is pending by invited user.
   */
  const INVITE_PENDING_REPLY = 4;

  /**
   * Invite has been accepted and the user joined.
   */
  const INVITE_ACCEPTED_AND_JOINED = 5;

  /**
   * Invite is invalid or has been expired.
   */
  const INVITE_INVALID_OR_EXPIRED = 6;

  /**
   * Gets the Event enrollment name.
   *
   * @return string
   *   Name of the Event enrollment.
   */
  public function getName();

  /**
   * Sets the Event enrollment name.
   *
   * @param string $name
   *   The Event enrollment name.
   *
   * @return \Drupal\social_event\EventEnrollmentInterface
   *   The called Event enrollment entity.
   */
  public function setName($name);

  /**
   * Gets the Event enrollment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Event enrollment.
   */
  public function getCreatedTime();

  /**
   * Sets the Event enrollment creation timestamp.
   *
   * @param int $timestamp
   *   The Event enrollment creation timestamp.
   *
   * @return \Drupal\social_event\EventEnrollmentInterface
   *   The called Event enrollment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Event node entity that enrollment belongs.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Event node entity.
   */
  public function getEvent(): ?NodeInterface;

  /**
   * Gets sending confirmation email status after standalone enroll to event.
   *
   * @return bool
   *   Confirmation status.
   */
  public function getEventStandaloneEnrollConfirmationStatus(): bool;

  /**
   * Returns the Event enrollment published status indicator.
   *
   * Unpublished Event enrollment are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Event enrollment is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Event enrollment.
   *
   * @param bool $published
   *   TRUE to set this Event enrollment to published, FALSE for unpublished.
   *
   * @return \Drupal\social_event\EventEnrollmentInterface
   *   The called Event enrollment entity.
   */
  public function setPublished($published);

  /**
   * Gets enroller id.
   *
   * @return string|null
   *   The user entity id.
   */
  public function getAccount(): ?string;

}
