<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\social_graphql\FileInputHandler;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_group\SetGroupsForNodeService;
use Drupal\social_organization\Entity\group\Organization;
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
   * UpdateTopic constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\social_graphql\FileInputHandler $fileInputHandler
   *   Resolves GraphQL file input to file entities.
   * @param \Drupal\social_group\SetGroupsForNodeService|null $setGroupsForNodeService
   *   The service to set groups for a node.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileInputHandler $fileInputHandler,
    protected ?SetGroupsForNodeService $setGroupsForNodeService = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $set_groups_service = NULL;
    if ($container->get('module_handler')->moduleExists('social_group')) {
      $set_groups_service = $container->get('social_group.set_groups_for_node_service');
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get(FileInputHandler::class),
      $set_groups_service,
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

    // Update image when the client sent heroImage (new file or explicit null).
    // @todo This finalization should roll back if we can not update the topic.
    // @todo We should properly handle finalization errors here.
    if ($input->hasHeroImageUpdate()) {
      $image = $this->fileInputHandler->inputToFile(
        $input->getHeroImage(),
        new EntityFieldUploadDestination('node', 'topic', 'field_topic_image'),
      );
      $node->set('field_topic_image', $image?->id() ?? []);
    }

    // Process groups if provided.
    if ($input->hasGroups()) {
      $primary_group = $input->getPrimaryGroup();
      $crossposted_groups = $input->getCrosspostedGroups();

      // Get current groups from relationships.
      $current_groups = $this->getCurrentGroupsFromRelationships($node);

      // Remove from all groups.
      if ($primary_group === NULL) {
        if ($this->setGroupsForNodeService !== NULL && !empty($current_groups)) {
          $this->setGroupsForNodeService->setGroupsForNode(
            $node,
            $current_groups,
            [],
            $current_groups,
            FALSE
          );
          $node->set('groups', []);
        }
      }
      // Update groups.
      else {
        $groups_to_add = $this->prepareGroupsToAdd($primary_group, $crossposted_groups);
        $this->setGroupsForNodeService?->setGroupsForNode(
          $node,
          $current_groups,
          $groups_to_add,
          $current_groups,
          FALSE
        );

        // Update groups field.
        $groups_field_values = $this->prepareGroupsFieldValues($primary_group, $crossposted_groups);
        $node->set('groups', $groups_field_values);
      }
    }

    // Process organizations if provided.
    if ($input->hasOrganizationsUpdate()) {
      if (!$node->hasField(Organization::REFERENCE_FIELD)) {
        $payload->addViolation(new Violation('ORGANIZATIONS_NOT_SUPPORTED'));
        return $payload;
      }
      if ($input->shouldClearOrganizations()) {
        $node->set(Organization::REFERENCE_FIELD, []);
      }
      else {
        $primary = $input->getPrimaryOrganization();
        if ($primary !== NULL) {
          $organization_ids = array_map(
            fn($entity): array => ['target_id' => $entity->id()],
            array_merge([$primary], $input->getCrosspostedOrganizations())
          );
          $node->set(Organization::REFERENCE_FIELD, $organization_ids);
        }
      }
    }

    // Validate the entity before saving.
    // This ensures that field-level constraints are checked
    // (e.g., title length, field types, required fields)
    // before the entity is persisted.
    $violations = $node->validate();
    if ($violations->count() > 0) {
      $graphql_violations = $input->convertConstraintViolations($violations);
      $payload->addViolations($graphql_violations);
      return $payload;
    }

    // Save the node.
    $node->save();
    $payload->setTopic($node);

    return $payload;
  }

  /**
   * Gets current group IDs from relationships for a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array<int, int>
   *   Array of group IDs keyed by group ID.
   */
  protected function getCurrentGroupsFromRelationships(NodeInterface $node): array {
    $current_groups = [];
    $relationships = GroupRelationship::loadByEntity($node);
    foreach ($relationships as $relationship) {
      $group_id = $relationship->getGroupId();
      $current_groups[$group_id] = $group_id;
    }
    return $current_groups;
  }

  /**
   * Prepares groups to add array for SetGroupsForNodeService.
   *
   * @param \Drupal\group\Entity\GroupInterface $primary_group
   *   The primary group.
   * @param \Drupal\group\Entity\GroupInterface[] $crossposted_groups
   *   Array of cross-posted groups.
   *
   * @return array
   *   Array of group IDs keyed by group ID.
   */
  private function prepareGroupsToAdd(GroupInterface $primary_group, array $crossposted_groups): array {
    $groups_to_add = [];
    $groups_to_add[$primary_group->id()] = $primary_group->id();
    foreach ($crossposted_groups as $group) {
      $groups_to_add[$group->id()] = $group->id();
    }
    return $groups_to_add;
  }

  /**
   * Prepares groups field values for setting on the node.
   *
   * @param \Drupal\group\Entity\GroupInterface $primary_group
   *   The primary group.
   * @param \Drupal\group\Entity\GroupInterface[] $crossposted_groups
   *   Array of cross-posted groups.
   *
   * @return array
   *   Array of field values with target_id keys.
   */
  private function prepareGroupsFieldValues(GroupInterface $primary_group, array $crossposted_groups): array {
    $groups_field_values = [];
    $groups_field_values[] = ['target_id' => $primary_group->id()];
    foreach ($crossposted_groups as $group) {
      $groups_field_values[] = ['target_id' => $group->id()];
    }
    return $groups_field_values;
  }

}
