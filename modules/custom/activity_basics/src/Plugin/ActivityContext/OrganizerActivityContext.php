<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OrganizerActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "organizer_activity_context",
 *   label = @Translation("Organizer activity context"),
 * )
 */
class OrganizerActivityContext extends ActivityContextBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a OrganizerActivityContext object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\Sql\QueryFactory $entity_query
   *   The query factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\activity_creator\ActivityFactory $activity_factory
   *   The activity factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    QueryFactory $entity_query,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_query, $entity_type_manager, $activity_factory);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.query.sql'),
      $container->get('entity_type.manager'),
      $container->get('activity_creator.activity_factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, int $last_id, int $limit): array {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = $this->activityFactory->getActivityRelatedEntity($data);
      if ($data['related_object'][0]['target_type'] === 'event_enrollment') {
        /** @var \Drupal\social_event\EventEnrollmentInterface $event_enrollment */
        $event_enrollment = $this->entityTypeManager->getStorage('event_enrollment')
          ->load($data['related_object'][0]['target_id']);
        // Send out enrollments notifications when people actually enrolled.
        if ($event_enrollment instanceof EventEnrollmentInterface && !$event_enrollment->get('field_enrollment_status')->isEmpty() && $event_enrollment->get('field_enrollment_status')->value !== '0') {
          $recipients = $this->getRecipientOrganizerFromEntity($related_entity, $data);
        }
      }
    }

    // Remove the actor (user performing action) from recipients list.
    if (!empty($data['actor'])) {
      $key = array_search($data['actor'], array_column($recipients, 'target_id'), FALSE);
      if ($key !== FALSE) {
        unset($recipients[$key]);
      }
    }

    return $recipients;
  }

  /**
   * Returns Organizer recipient from Events.
   *
   * @param array $related_entity
   *   The related entity.
   * @param array $data
   *   The data.
   *
   * @return array
   *   An associative array of recipients, containing the following key-value
   *   pairs:
   *   - target_type: The entity type ID.
   *   - target_id: The entity ID.
   */
  public function getRecipientOrganizerFromEntity(array $related_entity, array $data) {
    $recipients = [];

    // Don't return recipients if user enrolls to own Event.
    $original_related_object = $data['related_object'][0];
    if (isset($original_related_object['target_type'])
      && $original_related_object['target_type'] === 'event_enrollment'
      && $related_entity !== NULL) {
      $storage = $this->entityTypeManager->getStorage($related_entity['target_type']);
      $event = $storage->load($related_entity['target_id']);

      if ($event === NULL) {
        return $recipients;
      }

      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $event->getOwnerId(),
      ];
    }

    // If there are any others we should add. Make them also part of the
    // recipients array.
    $this->moduleHandler->alter('activity_recipient_organizer', $recipients, $event, $original_related_object);

    return $recipients;
  }

}
