<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_event\Wrappers\Input\CreateEventInput;
use Drupal\social_event\Wrappers\Payload\CreateEventPayload;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_group\SetGroupsForNodeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a new event.
 *
 * @DataProducer(
 *   id = "create_event",
 *   name = @Translation("Create Event"),
 *   description = @Translation("Creates a new event."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("CreateEventPayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "CreateEventInput",
 *     ),
 *   }
 * )
 */
class CreateEvent extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;
  use VisibilityTrait;

  /**
   * CreateEvent constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\social_group\SetGroupsForNodeService|null $setGroupsForNodeService
   *   The set groups for node service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $logger_factory,
    protected Connection $database,
    protected ?SetGroupsForNodeService $setGroupsForNodeService = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setLoggerFactory($logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    $set_groups_for_node_service = NULL;
    if ($container->get('module_handler')->moduleExists('social_group')) {
      $set_groups_for_node_service = $container->get('social_group.set_groups_for_node_service');
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('database'),
      $set_groups_for_node_service,
    );
  }

  /**
   * Creates a new event.
   *
   * @param \Drupal\social_event\Wrappers\Input\CreateEventInput $input
   *   The information for the event.
   *
   * @return \Drupal\social_event\Wrappers\Payload\CreateEventPayload
   *   The GraphQL event response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(CreateEventInput $input): CreateEventPayload {
    $payload = new CreateEventPayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    // Map visibility from GraphQL to Drupal field values.
    $visibility_value = $this->convertVisibilityUserInputToConstant($input->getVisibility());

    // Convert timestamps to Drupal datetime storage format (UTC).
    $start_date = DrupalDateTime::createFromTimestamp($input->getStartDate(), DateTimeItemInterface::STORAGE_TIMEZONE);
    $end_date = DrupalDateTime::createFromTimestamp($input->getEndDate(), DateTimeItemInterface::STORAGE_TIMEZONE);

    // Create the event node.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_values = [
      'type' => 'event',
      'title' => $input->getTitle(),
      'body' => $input->getBody(),
      'field_content_visibility' => $visibility_value,
      'field_event_date' => $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      'field_event_date_end' => $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      'field_event_enroll' => 0,
      'uid' => $input->getAuthor()->id(),
      'status' => 1,
    ];

    // Add event type if provided.
    if ($input->hasEventType()) {
      $node_values['field_event_type'] = $input->getEventType();
    }

    // Add location if provided.
    $location = $input->getLocation();
    if ($location !== NULL) {
      $node_values['field_event_location'] = $location;
    }

    // Add address if provided (field uses default langcode when omitted).
    $address = $input->getAddress();
    if ($address !== NULL) {
      $node_values['field_event_address'] = [0 => $address];
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create($node_values);

    // Validate the entity before saving.
    // This ensures that field-level constraints are checked
    // (e.g., title length, field types, required fields)
    // before the entity is persisted.
    $violations = $node->validate();
    if ($violations->count() > 0) {
      $payload->addViolations($input->convertConstraintViolations($violations));
      return $payload;
    }

    // Wrap both saves in a single transaction so that a failed group
    // validation rolls back the first save (no orphan event).
    $transaction = $this->database->startTransaction();
    try {
      // Persist the event node so it has an ID for group membership.
      $node->save();

      // Set groups after saving the node, as groups require a saved entity.
      if ($input->getPrimaryGroup() !== NULL) {
        $primary_group = $input->getPrimaryGroup();
        $crossposted_groups = $input->getCrosspostedGroups();

        // Build the list of group IDs to add (primary + crossposted).
        $groups_to_add = [];
        $groups_to_add[$primary_group->id()] = $primary_group->id();
        foreach ($crossposted_groups as $group) {
          $groups_to_add[$group->id()] = $group->id();
        }

        // Apply group membership via the service (validates access etc.).
        assert($this->setGroupsForNodeService instanceof SetGroupsForNodeService);
        $this->setGroupsForNodeService->setGroupsForNode(
          $node,
          [],
          $groups_to_add,
          [],
          TRUE
        );

        // Sync the groups field on the node for the second save.
        $groups_field_values = [];
        $groups_field_values[] = ['target_id' => $primary_group->id()];
        foreach ($crossposted_groups as $group) {
          $groups_field_values[] = ['target_id' => $group->id()];
        }
        $node->set('groups', $groups_field_values);

        // Re-validate after group changes; roll back and report if invalid.
        $violations = $node->validate();
        if ($violations->count() > 0) {
          $transaction->rollBack();
          $payload->addViolations($input->convertConstraintViolations($violations));
          return $payload;
        }
        $node->save();
      }

      $payload->setEvent($node);
      return $payload;
    }
    catch (\Throwable $e) {
      $transaction->rollBack();
      $this->getLogger('social_event')->error('Event creation failed in GraphQL Create Event Mutation: @message', [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);
      $payload->addViolation(new Violation('EVENT_SAVE_FAILED'));
      return $payload;
    }
  }

}
