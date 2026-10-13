<?php

declare(strict_types=1);

namespace Drupal\social_event\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\message\MessageInterface;
use Drupal\social_event\Entity\Node\Event;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks for per-event external registration.
 */
final class ExternalRegistrationHooks implements ContainerInjectionInterface {

  public function __construct(
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('logger.factory'),
    );
  }

  /**
   * Adds the external registration constraint to the event registration URL.
   *
   * @param array<string, \Drupal\Core\Field\FieldDefinitionInterface> $fields
   *   The bundle field definitions, keyed by field name.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   */
  #[Alter('entity_bundle_field_info')]
  public function addConstraint(array &$fields, EntityTypeInterface $entity_type, string $bundle): void {
    if ($entity_type->id() !== 'node' || $bundle !== 'event' || !isset($fields['field_event_registration_url'])) {
      return;
    }

    $field = $fields['field_event_registration_url'];
    if ($field instanceof FieldConfigInterface) {
      $field->addConstraint('ExternalEventRegistration');
    }
  }

  /**
   * Logs new Open Social enrolments for externally registered events.
   *
   * The save is not blocked: enrolments can be created by flows that have
   * already completed outside the enrolment UI, such as a paid ticket webhook.
   */
  #[Hook('event_enrollment_insert')]
  public function logNativeEnrollment(EventEnrollmentInterface $enrollment): void {
    $event = $enrollment->get('field_event')->entity;
    if (!$event instanceof Event || !$event->isExternalRegistration()) {
      return;
    }

    $this->loggerFactory->get('social_event')->warning('Enrollment @enrollment was created for event @event, which uses external registration.', [
      '@enrollment' => $enrollment->id(),
      '@event' => $event->id(),
    ]);
  }

  /**
   * Stops activities about enrolments on externally registered events.
   *
   * Activities are only created for messages that have a destination, so
   * clearing it prevents stream items, notifications and emails.
   */
  #[Hook('message_presave')]
  public function skipEnrollmentActivities(MessageInterface $message): void {
    if (!$message->hasField('field_message_related_object') || !$message->hasField('field_message_destination')) {
      return;
    }

    $enrollment = $message->get('field_message_related_object')->entity;
    if (!$enrollment instanceof EventEnrollmentInterface) {
      return;
    }

    $event = $enrollment->getEvent();
    if ($event instanceof Event && $event->isExternalRegistration()) {
      $message->set('field_message_destination', []);
    }
  }

}
