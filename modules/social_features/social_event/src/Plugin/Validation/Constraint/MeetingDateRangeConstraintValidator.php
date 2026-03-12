<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the MeetingDateRange constraint.
 */
class MeetingDateRangeConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof MeetingDateRangeConstraint);

    if ($value === NULL) {
      return;
    }

    assert($value instanceof FieldItemListInterface);

    $datetime_field = $value->first();
    if (!$datetime_field) {
      return;
    }

    $time_start = $datetime_field->value ?? NULL;
    $time_end = $datetime_field->end_value ?? NULL;
    if (!$time_start || !$time_end) {
      return;
    }

    $timezone = $datetime_field->timezone ?? 'UTC';

    $start_date = new DrupalDateTime($time_start, $timezone);
    $end_date = new DrupalDateTime($time_end, $timezone);

    if ($start_date->getTimestamp() === $end_date->getTimestamp()) {
      return;
    }

    $interval = $start_date->diff($end_date);
    if ($interval->invert === 1) {
      $this->context->buildViolation($constraint->endDateBeforeStartDate)
        ->atPath('end_value')
        ->addViolation();
    }
  }

}
