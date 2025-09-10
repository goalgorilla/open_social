<?php

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\meeting_api\Entity\Meeting;
use Drupal\social_event\Service\EventOnline;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the MeetingServerSettingsConstraint constraint.
 */
class MeetingServerSettingsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a MeetingServerSettingsConstraintValidator object.
   *
   * @param \Drupal\social_event\Service\EventOnline $eventOnline
   *   The event online service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    private readonly EventOnline $eventOnline,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(EventOnline::class),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof MeetingServerSettingsConstraint);

    if ($value === NULL) {
      return;
    }

    // The value should be the Meeting entity itself.
    $meeting = $value;
    if (!$meeting instanceof Meeting) {
      return;
    }

    try {
      $server_backend_id = $this->eventOnline->getMeetingBackendId($meeting);

      // We want to check the BigBlueButton server only.
      if ($server_backend_id !== 'bigbluebutton') {
        return;
      }

      // Get the meeting type from the meeting bundle.
      $meeting_type_id = $meeting->bundle();

      // Use the EventOnline service to validate server configuration.
      if (!$this->eventOnline->validateMeetingTypeUsage($meeting_type_id)) {
        $this->context->addViolation($constraint->bigBlueButtonServerNotConfigured);
      }
    }
    catch (\Exception $e) {
      // If we can't validate, fail gracefully without adding violations.
      // This prevents errors during entity creation
      // or when a server is missing.
      $this->loggerFactory->get('social_event')->error($e->getMessage());
      return;
    }
  }

}
