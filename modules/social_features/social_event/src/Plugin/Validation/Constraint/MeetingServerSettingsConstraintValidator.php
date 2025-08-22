<?php

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\meeting_api\MeetingInterface;
use Drupal\meeting_api\ServerInterface;
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
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
    if (!$meeting instanceof MeetingInterface) {
      return;
    }

    try {
      $server_id = $meeting->getServerId();

      // Load the server entity.
      $server = $this->entityTypeManager
        ->getStorage('meeting_api_server')
        ->load($server_id);

      if (!$server) {
        return;
      }

      assert($server instanceof ServerInterface);

      // We want to check the BigBlueButton server only.
      if ($server->get('backend') !== 'bigbluebutton') {
        return;
      }

      // Validate that the server has the proper configuration.
      $backend_config = $server->get('backend_config');

      if (
        empty($backend_config) ||
        empty($backend_config['url']) ||
        empty($backend_config['key'])
      ) {
        $this->context->addViolation($constraint->bigBlueButtonServerNotConfigured);
      }
    }
    catch (\Exception $e) {
      // If we can't validate, fail gracefully without adding violations.
      // This prevents errors during entity creation
      // or when a server is missing.
      return;
    }
  }

}
