<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
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
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * CreateEvent constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
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

    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create($node_values);

    // Validate the entity before saving.
    // This ensures that field-level constraints are checked
    // (e.g., title length, field types, required fields)
    // before the entity is persisted.
    $violations = $node->validate();
    if ($violations->count() > 0) {
      // Convert Drupal constraint violations to GraphQL violations.
      foreach ($violations as $violation) {
        // Create a violation ID based on the constraint type and field.
        $property_path = $violation->getPropertyPath();
        $constraint = $violation->getConstraint();

        // Skip if constraint is null (should not happen but type-safe).
        if ($constraint === NULL) {
          continue;
        }

        $constraint_class = $constraint::class;
        $last_separator_pos = strrpos($constraint_class, '\\');
        $constraint_type = $last_separator_pos !== FALSE
          ? substr($constraint_class, $last_separator_pos + 1)
          : $constraint_class;

        // Create a machine-readable violation ID.
        // Example: "title" + "LengthConstraint" => "TITLE_LENGTH_CONSTRAINT".
        $violation_id = strtoupper($property_path . '_' . $constraint_type);
        $violation_id = preg_replace('/[^A-Z0-9_]/', '_', $violation_id);

        // Add violation if ID is valid.
        if (is_string($violation_id)) {
          $payload->addViolation(new Violation($violation_id));
        }
      }
      return $payload;
    }

    // Save the event node, and throw an exception if it fails.
    try {
      $node->save();
    }
    catch (EntityStorageException $e) {
      $this->getLogger('social_event')->error('Event save failed in GraphQL Create Event Mutation: @message', [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);
      $payload->addViolation(new Violation('EVENT_SAVE_FAILED'));
      return $payload;
    }

    $payload->setEvent($node);

    return $payload;
  }

}
