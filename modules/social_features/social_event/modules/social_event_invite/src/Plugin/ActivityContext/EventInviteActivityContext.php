<?php

namespace Drupal\social_event_invite\Plugin\ActivityContext;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'EventInviteActivityContext' activity context.
 *
 * @ActivityContext(
 *   id = "event_invite_activity_context",
 *   label = @Translation("Event invite activity context"),
 * )
 */
class EventInviteActivityContext extends ActivityContextBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a EventInviteAnonymousActivityContext object.
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
    ModuleHandlerInterface $module_handler
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
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      if ($data['related_object'][0]['target_type'] === 'event_enrollment') {
        // Get the enrollment id.
        $enrollment_id = $data['related_object'][0]['target_id'];

        /** @var \Drupal\social_event\EventEnrollmentInterface $event_enrollment */
        $event_enrollment = $this->entityTypeManager->getStorage('event_enrollment')
          ->load($enrollment_id);

        // Send out the notification if the user is pending.
        if (!empty($event_enrollment)) {
          if (!$event_enrollment->get('field_enrollment_status')->isEmpty()
            && $event_enrollment->get('field_enrollment_status')->value === '0'
            && !$event_enrollment->get('field_request_or_invite_status')->isEmpty()
            && (int) $event_enrollment->get('field_request_or_invite_status')->value === EventEnrollmentInterface::INVITE_PENDING_REPLY
            && !$event_enrollment->get('field_account')->isEmpty()) {
            $recipients[] = [
              'target_type' => 'user',
              'target_id' => $event_enrollment->get('field_account')
                ->getString(),
            ];
          }
        }
      }
    }

    return $recipients;
  }

}
