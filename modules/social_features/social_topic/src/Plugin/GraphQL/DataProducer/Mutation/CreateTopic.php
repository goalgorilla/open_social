<?php

declare(strict_types=1);

namespace Drupal\social_topic\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use Drupal\social_graphql\FileInputHandler;
use Drupal\social_group\SetGroupsForNodeService;
use Drupal\social_organization\Entity\group\Organization;
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
  use VisibilityTrait;

  /**
   * CreateTopic constructor.
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
   *   The set groups for node service.
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
    $set_groups_for_node_service = NULL;
    if ($container->get('module_handler')->moduleExists('social_group')) {
      $set_groups_for_node_service = $container->get('social_group.set_groups_for_node_service');
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get(FileInputHandler::class),
      $set_groups_for_node_service,
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
    $visibility_value = $this->convertVisibilityUserInputToConstant($input->getVisibility());

    // Create the topic node.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_values = [
      'type' => 'topic',
      'title' => $input->getTitle(),
      'body' => $input->getBody(),
      'field_content_visibility' => $visibility_value,
      'field_topic_type' => $input->getTopicType(),
      'uid' => $input->getAuthor()->id(),
      'status' => 1,
    ];

    // @todo This should roll back if we can not create the topic.
    // @todo We should properly handle finalization errors here.
    $image = $this->fileInputHandler->inputToFile(
      $input->getHeroImage(),
      new EntityFieldUploadDestination('node', 'topic', 'field_topic_image'),
    );
    if ($image !== NULL) {
      $node_values['field_topic_image'] = $image->id();
    }

    // Add content tags if provided.
    if (!empty($input->getContentTags())) {
      // Convert TermInterface objects to target_id values.
      $tag_ids = [];
      foreach ($input->getContentTags() as $tag) {
        $tag_ids[] = $tag->id();
      }
      $node_values['social_tagging'] = $tag_ids;
    }

    // Organization reference: primary first, then cross-posted (IDs only).
    $primary_organization = $input->getPrimaryOrganization();
    if ($primary_organization !== NULL) {
      $node_values[Organization::REFERENCE_FIELD] = array_map(
        fn($organization_entity): array => ['target_id' => $organization_entity->id()],
        array_merge([$primary_organization], $input->getCrosspostedOrganizations())
      );
    }

    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create($node_values);

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

    $node->save();

    // Set groups after saving the node, as groups require a saved entity.
    // The crossposted only can be sent when exist primary-group,
    // because of that I checked only primary-group.
    if ($input->getPrimaryGroup() !== NULL) {
      $primary_group = $input->getPrimaryGroup();
      $crossposted_groups = $input->getCrosspostedGroups();

      // The SetGroupsForNodeService::setGroupsForNode is expecting group-id;.
      $groups_to_add = [];
      $groups_to_add[$primary_group->id()] = $primary_group->id();
      foreach ($crossposted_groups as $group) {
        $groups_to_add[$group->id()] = $group->id();
      }

      // If the social_group module is disabled and a group was provided,
      // the input validation already be added a violation,
      // so that is to make PHPStan happy.
      assert($this->setGroupsForNodeService instanceof SetGroupsForNodeService);
      $this->setGroupsForNodeService->setGroupsForNode(
        $node,
        [],
        $groups_to_add,
        [],
        TRUE
      );

      // Add group IDs to the groups field before validation.
      $groups_field_values = [];
      $groups_field_values[] = ['target_id' => $primary_group->id()];
      foreach ($crossposted_groups as $group) {
        $groups_field_values[] = ['target_id' => $group->id()];
      }
      $node->set('groups', $groups_field_values);

      // Validate after adding groups to ensure group-related constraints
      // are properly validated.
      $violations = $node->validate();
      if ($violations->count() > 0) {
        $graphql_violations = $input->convertConstraintViolations($violations);
        $payload->addViolations($graphql_violations);
        return $payload;
      }
      $node->save();

    }
    $payload->setTopic($node);

    return $payload;
  }

}
