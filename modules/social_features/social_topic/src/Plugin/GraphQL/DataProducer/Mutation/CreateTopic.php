<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_topic\Wrappers\Input\CreateTopicInput;
use Drupal\social_topic\Wrappers\Payload\CreateTopicPayload;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a new topic.
 *
 * @DataProducer(
 *   id = "create_topic",
 *   name = @Translation("Create Topic"),
 *   description = @Translation("Creates a new topic."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("CreateTopicPayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "CreateTopicInput",
 *     ),
 *   }
 * )
 */
class CreateTopic extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountInterface $currentUser;

  /**
   * CreateTopic constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
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
      $container->get('current_user'),
    );
  }

  /**
   * Creates a new topic.
   *
   * @param \Drupal\social_topic\Wrappers\Input\CreateTopicInput $input
   *   The information for the topic.
   *
   * @return \Drupal\social_topic\Wrappers\Payload\CreateTopicPayload
   *   The GraphQL topic response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resolve(CreateTopicInput $input): CreateTopicPayload {
    $payload = new CreateTopicPayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    // Map visibility from GraphQL to Drupal field values.
    $visibility_map = [
      'PUBLIC' => 'public',
      'COMMUNITY' => 'community',
      'GROUP_MEMBER' => 'group',
    ];

    $visibility_value = $visibility_map[$input->getVisibility()] ?? 'community';

    // Create the topic node.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_values = [
      'type' => 'topic',
      'title' => $input->getTitle(),
      'body' => [[
        'value' => ' ',
      ],
      ],
      'field_content_visibility' => $visibility_value,
      'field_topic_type' => $input->getTopicType(),
      'uid' => $input->getAuthor()->id(),
      'status' => 1,
    ];

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

        $constraint_class = get_class($constraint);
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

    $node->save();
    $payload->setTopic($node);

    return $payload;
  }

}
