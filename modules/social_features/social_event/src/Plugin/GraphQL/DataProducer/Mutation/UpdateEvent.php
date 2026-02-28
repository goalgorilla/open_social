<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_event\Wrappers\Input\UpdateEventInput;
use Drupal\social_event\Wrappers\Payload\UpdateEventPayload;
use Drupal\social_graphql\GraphQL\Violation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates an existing event.
 *
 * @DataProducer(
 *   id = "update_event",
 *   name = @Translation("Update Event"),
 *   description = @Translation("Updates an existing event."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("UpdateEventPayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "UpdateEventInput",
 *     ),
 *   }
 * )
 */
class UpdateEvent extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  use LoggerChannelTrait;
  use VisibilityTrait;

  /**
   * UpdateEvent constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    LoggerChannelFactoryInterface $logger_factory,
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
    );
  }

  /**
   * Updates an existing event.
   *
   * @param \Drupal\social_event\Wrappers\Input\UpdateEventInput $input
   *   The information for updating the event.
   *
   * @return \Drupal\social_event\Wrappers\Payload\UpdateEventPayload
   *   The GraphQL event response.
   */
  public function resolve(UpdateEventInput $input): UpdateEventPayload {
    $payload = new UpdateEventPayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    // Load the event node.
    $node = $input->getEvent();

    // Track if any fields were modified.
    $modified = FALSE;

    // Update title if provided.
    if ($input->hasTitle()) {
      $node->setTitle($input->getTitle());
      $modified = TRUE;
    }

    // Update event type if provided (set to term or clear if null).
    if ($input->eventTypeProvided()) {
      $node->set('field_event_type', $input->getEventType());
      $modified = TRUE;
    }

    // Update visibility if provided (validated in UpdateEventInput).
    if ($input->hasVisibility()) {
      $visibility_value = $this->convertVisibilityUserInputToConstant($input->getVisibility());
      $node->set('field_content_visibility', $visibility_value);
      $modified = TRUE;
    }

    // Update start date if provided.
    if ($input->hasStartDate()) {
      $start_date = DrupalDateTime::createFromTimestamp($input->getStartDate(), DateTimeItemInterface::STORAGE_TIMEZONE);
      $node->set('field_event_date', $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
      $modified = TRUE;
    }

    // Update end date if provided.
    if ($input->hasEndDate()) {
      $end_date = DrupalDateTime::createFromTimestamp($input->getEndDate(), DateTimeItemInterface::STORAGE_TIMEZONE);
      $node->set('field_event_date_end', $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT));
      $modified = TRUE;
    }

    // Update location if provided.
    if ($input->hasLocation()) {
      $node->set('field_event_location', $input->getLocation());
      $modified = TRUE;
    }

    // Update address if provided (set to value or clear if null).
    if ($input->hasAddress()) {
      $address = $input->getAddress();
      $node->set('field_event_address', $address !== NULL ? [0 => $address] : []);
      $modified = TRUE;
    }

    // Update body if provided.
    if ($input->hasBody()) {
      $node->set('body', $input->getBody());
      $modified = TRUE;
    }

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

    // If no fields were modified, return the node without saving.
    if (!$modified) {
      $payload->setEvent($node);
      return $payload;
    }

    // Save the node.
    try {
      $node->save();
    }
    catch (EntityStorageException $e) {
      $this->getLogger('social_event')->error('Event save failed in GraphQL Update Event Mutation: @message', [
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
