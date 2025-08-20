<?php

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

#[Constraint(
  id: 'MeetingCapacity',
  label: new TranslatableMarkup('Meeting Capacity', [], ['context' => 'Validation']),
  type: 'entity:meeting_api_meeting'
)]
class MeetingCapacityConstraint extends SymfonyConstraint {

  /**
   * Error message for when BigBlueButton meeting capacity limit is exceeded.
   *
   * @var string
   */
  public string $bbbCapacityLimitExceeded = 'BigBlueButton meeting capacity limit reached. At selected time the @capacity attendees number is allowed. Change the meeting time or decrease the number of attendees.';

}
