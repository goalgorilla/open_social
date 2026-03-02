<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\meeting_api\MeetingInterface;
use Drupal\meeting_api_scheduler\Service\TimeConstraintManager;
use Drupal\meeting_api_scheduler\ValueObject\MeetingRequest;
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('module_handler'),
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

    if (!$this->moduleHandler->moduleExists('meeting_api_scheduler')) {
      return;
    }

    try {
      if ($meeting->bundle() !== 'big_blue_button') {
        return;
      }

      $datetime_field = $meeting->get('datetime')->first();
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

      $start_immutable = \DateTimeImmutable::createFromFormat(
        format: \DateTimeInterface::ATOM,
        datetime: $start_date->format(\DateTimeInterface::ATOM)
      );

      $end_immutable = \DateTimeImmutable::createFromFormat(
        format: \DateTimeInterface::ATOM,
        datetime: $end_date->format(\DateTimeInterface::ATOM)
      );

      assert($start_immutable instanceof \DateTimeImmutable);
      assert($end_immutable instanceof \DateTimeImmutable);

      $attendees_count = $meeting->get('max_attendees')->value ?? 0;
      $meeting_request = new MeetingRequest(
        startTime: $start_immutable,
        endTime: $end_immutable,
        attendeesCount: (int) $attendees_count,
        serverId: $meeting->getServerId(),
        meetingId: !$meeting->isNew() ? $meeting->id() : NULL,
      );

      // Make sure there are no cross-lined meetings.
      if (
        \Drupal::service(TimeConstraintManager::class)
          ->collect($meeting_request)
          ->isEmpty()
      ) {
        return;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error validating meeting capacity: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $this->context->buildViolation($constraint->bbbCapacityLimitExceeded)
      ->atPath('max_attendees')
      ->addViolation();
  }

}
