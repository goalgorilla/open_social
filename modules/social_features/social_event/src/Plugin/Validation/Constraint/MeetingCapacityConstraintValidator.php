<?php

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\meeting_api\MeetingInterface;
use Drupal\social_event\Service\EventOnline;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the MeetingCapacity constraint.
 */
class MeetingCapacityConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a new MeetingCapacityConstraintValidator object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')->get('social_event')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof MeetingCapacityConstraint);

    if ($value === NULL) {
      return;
    }

    assert($value instanceof FieldItemListInterface);

    // The value should be the Meeting entity itself.
    $meeting = $value->getEntity();
    if (!$meeting instanceof MeetingInterface) {
      return;
    }

    try {
      // Only validate BigBlueButton meetings.
      if ($meeting->bundle() === 'big_blue_button') {
        // Get the meeting date and time.
        $datetime_field = $meeting->get('datetime')->first();
        if (!$datetime_field) {
          return;
        }

        $time_start = $datetime_field->value ?? NULL;
        $time_end = $datetime_field->end_value ?? NULL;
        if (!$time_start || !$time_end) {
          return;
        }

        // Set the timezone for the date and time fields. Then we need
        // to convert them to UTC as database queries are done in UTC.
        $timezone = $datetime_field->timezone ?? 'UTC';
        $start_date = new DrupalDateTime($time_start, $timezone);
        $end_date = new DrupalDateTime($time_end, $timezone);

        // Count the total capacity of overlapping BigBlueButton meetings.
        $other_attendees = $this->countOverlappingMeetingsAttendees($start_date, $end_date);

        // Add the current meeting's capacity to the total.
        $current_capacity = (int) ($meeting->get('max_attendees')->value ?? 0);
        $total_capacity = $other_attendees + $current_capacity;

        // Check if the capacity limit is exceeded.
        if ($total_capacity > EventOnline::MAX_CONCURRENT_BBB_ATTENDEES) {
          $this->context->buildViolation($constraint->bbbCapacityLimitExceeded, [
            '@capacity' => max(EventOnline::MAX_CONCURRENT_BBB_ATTENDEES - $other_attendees, 0),
          ])
            ->atPath('max_attendees')
            ->addViolation();
        }
      }
    }
    catch (\Exception $e) {
      // Log the error but don't fail validation to avoid blocking form
      // submission.
      $this->logger->error('Error validating meeting capacity: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Counts the total max_attendees capacity of overlapping meetings.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $event_start_date
   *   The start date and time of the event.
   * @param \Drupal\Core\Datetime\DrupalDateTime $event_end_date
   *   The end date and time of the event.
   * @param int|null $exclude_meeting_id
   *   Optional meeting ID to exclude from the count (for editing existing
   *   meetings).
   *
   * @return int
   *   The total max_attendees capacity of overlapping meetings.
   *
   * @throws \Exception
   */
  protected function countOverlappingMeetingsAttendees(DrupalDateTime $event_start_date, DrupalDateTime $event_end_date, ?int $exclude_meeting_id = NULL): int {
    // For database queries, convert the date and time to UTC.
    $event_start = $event_start_date->format(
      format: DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      settings: ['timezone' => 'UTC']
    );
    $event_end = $event_end_date->format(
      format: DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
      settings: ['timezone' => 'UTC']
    );

    // Build the query to find overlapping BigBlueButton meetings.
    $query = $this->database->select('meeting_api_meeting', 'm')
      ->condition('bundle', 'big_blue_button')
      ->condition('status', 1);
    $query->addExpression('COALESCE(SUM(m.max_attendees), 0)', 'total_capacity');

    // Exclude current meeting if editing.
    if ($exclude_meeting_id) {
      $query->condition('id', $exclude_meeting_id, '<>');
    }

    // Meeting entity table columns names.
    $meeting_start = 'datetime__value';
    $meeting_end = 'datetime__end_value';
    // Add overlap conditions using the "OR" group.
    $overlap_conditions = $query->orConditionGroup()
      // Case 1: Meeting starts during an event.
      ->condition(
        $query->andConditionGroup()
          ->condition($meeting_start, $event_start, '>=')
          ->condition($meeting_start, $event_end, '<')
      )
      // Case 2: Meeting ends during an event.
      ->condition(
        $query->andConditionGroup()
          ->condition($meeting_end, $event_start, '>')
          ->condition($meeting_end, $event_end, '<=')
      )
      // Case 3: Meeting encompasses the entire event.
      ->condition(
        $query->andConditionGroup()
          ->condition($meeting_start, $event_start, '<=')
          ->condition($meeting_end, $event_end, '>=')
      );

    $query->condition($overlap_conditions);

    return (int) $query->execute()?->fetchField();
  }

}
