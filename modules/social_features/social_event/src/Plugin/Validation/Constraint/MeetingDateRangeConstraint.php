<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates that a meeting's end date is not before the start date.
 */
#[Constraint(
  id: 'MeetingDateRange',
  label: new TranslatableMarkup('Meeting Date Range', [], ['context' => 'Validation']),
  type: 'entity:meeting_api_meeting'
)]
class MeetingDateRangeConstraint extends SymfonyConstraint {

  /**
   * Error message when the end date is before the start date.
   *
   * @var string
   */
  public string $endDateBeforeStartDate = 'The end date cannot be before the start date.';

}
