<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_topic\Wrappers\Input\UpdateTopicInput;
use Drupal\social_topic\Wrappers\Payload\UpdateTopicPayload;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates an existing topic.
 *
 * @DataProducer(
 *   id = "update_topic",
 *   name = @Translation("Update Topic"),
 *   description = @Translation("Updates an existing topic."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("UpdateTopicPayload")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = "UpdateTopicInput",
 *     ),
 *   }
 * )
 */
class UpdateTopic extends DataProducerPluginBase implements ContainerFactoryPluginInterface {
  use VisibilityTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * UpdateTopic constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
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
   * Updates an existing topic.
   *
   * @param \Drupal\social_topic\Wrappers\Input\UpdateTopicInput $input
   *   The information for updating the topic.
   *
   * @return \Drupal\social_topic\Wrappers\Payload\UpdateTopicPayload
   *   The GraphQL topic response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function resolve(UpdateTopicInput $input): UpdateTopicPayload {
    $payload = new UpdateTopicPayload();
    $payload->setClientMutationId($input->getClientMutationId());

    // Validate the input.
    if (!$input->validate()) {
      $payload->addViolations($input->getViolations());
      return $payload;
    }

    // Load the topic node.
    $node = $input->getTopic();

    // Update title if provided.
    if ($input->hasTitle()) {
      $node->setTitle($input->getTitle());
    }

    // Update topic type if provided.
    if ($input->hasTopicType()) {
      $node->set('field_topic_type', $input->getTopicType());
    }

    // Update visibility if provided.
    if ($input->hasVisibility()) {
      // Map visibility from GraphQL to Drupal field values.
      $graphql_visibility = $input->getVisibility();
      if (!$this->isValidVisibility($graphql_visibility)) {
        $payload->addViolation(new Violation('INVALID_VISIBILITY'));
        return $payload;
      }
      $visibility_value = $this->convertVisibilityUserInputToConstant($graphql_visibility);
      $node->set('field_content_visibility', $visibility_value);
    }

    // Update content tags if provided.
    if ($input->hasContentTags()) {
      $content_tags = $input->getContentTags();
      if ($node->hasField('social_tagging')) {
        // Convert TermInterface objects to target_id values.
        $tag_ids = [];
        foreach ($content_tags as $tag) {
          $tag_ids[] = $tag->id();
        }
        $node->set('social_tagging', $tag_ids);
      }
    }

    // Update body if provided.
    if ($input->hasBody()) {
      $node->set('body', $input->getBody());
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

    // Save the node.
    $node->save();
    $payload->setTopic($node);

    return $payload;
  }

}
