<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\GraphQL\DataProducer\Mutation;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\entity_access_by_field\Traits\VisibilityTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\NodeInterface;
use Drupal\social_event\Wrappers\Input\UpdateEventInput;
use Drupal\social_event\Wrappers\Payload\UpdateEventPayload;
use Drupal\social_graphql\GraphQL\Violation;
use Drupal\social_group\SetGroupsForNodeService;
use Drupal\social_organization\Entity\group\Organization;
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
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\social_group\SetGroupsForNodeService|null $setGroupsForNodeService
   *   The set groups for node service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
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
      $container->get('logger.factory'),
      $container->get('database'),
      $set_groups_for_node_service,
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

    // Update content tags if provided.
    if ($input->hasContentTags()) {
      if (!$node->hasField('social_tagging')) {
        $payload->addViolation(new Violation('CONTENT_TAGS_NOT_SUPPORTED'));
        return $payload;
      }
      $content_tags = $input->getContentTags();
      $tag_ids = [];
      foreach ($content_tags as $tag) {
        $tag_ids[] = $tag->id();
      }
      $node->set('social_tagging', $tag_ids);
      $modified = TRUE;
    }

    // Update groups if provided: set field value now; sync relationships after
    // successful save.
    $groups_sync_pending = FALSE;
    // Group IDs to pass as "current" to setGroupsForNode.
    $groups_sync_current = [];
    // Group IDs to add (used when transaction runs).
    $groups_sync_to_add = [];
    if ($input->hasGroups()) {
      $primary_group = $input->getPrimaryGroup();
      $crossposted_groups = $input->getCrosspostedGroups();
      $current_groups = $this->getCurrentGroupsFromRelationships($node);

      if ($primary_group === NULL) {
        // Clearing groups: remove all group memberships.
        $node->set('groups', []);
        $groups_sync_pending = TRUE;
        $groups_sync_current = $current_groups;
        $groups_sync_to_add = [];
      }
      else {
        // Build the list of group IDs to add (primary + crossposted).
        $groups_to_add = [];
        $groups_to_add[$primary_group->id()] = $primary_group->id();
        foreach ($crossposted_groups as $group) {
          $groups_to_add[$group->id()] = $group->id();
        }

        // Prepare the field values for the groups field on the node.
        $groups_field_values = [];
        $groups_field_values[] = ['target_id' => $primary_group->id()];
        foreach ($crossposted_groups as $group) {
          $groups_field_values[] = ['target_id' => $group->id()];
        }
        $node->set('groups', $groups_field_values);

        $groups_sync_pending = TRUE;
        $groups_sync_current = $current_groups;
        $groups_sync_to_add = $groups_to_add;
      }
      $modified = TRUE;
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
      $modified = TRUE;
    }

    // Validate the entity before saving.
    // This ensures that field-level constraints are checked
    // (e.g., title length, field types, required fields)
    // before the entity is persisted.
    $violations = $node->validate();
    if ($violations->count() > 0) {
      $payload->addViolations($input->convertConstraintViolations($violations));
      return $payload;
    }

    // If no fields were modified, return the node without saving.
    if (!$modified) {
      $payload->setEvent($node);
      return $payload;
    }

    // Wrap save and group sync in a single transaction so that a failed
    // setGroupsForNode rolls back the node save (no partial state).
    $transaction = $this->database->startTransaction();
    try {
      // Persist the updated event node.
      $node->save();

      // Sync group relationships only after successful save.
      if ($groups_sync_pending && $this->setGroupsForNodeService !== NULL) {
        // Apply group membership changes via the service.
        $this->setGroupsForNodeService->setGroupsForNode(
          $node,
          $groups_sync_current,
          $groups_sync_to_add,
          $groups_sync_current,
          FALSE
        );
      }

      $payload->setEvent($node);
      return $payload;
    }
    catch (\Throwable $e) {
      $transaction->rollBack();
      $this->getLogger('social_event')->error('Event update failed in GraphQL Update Event Mutation: @message', [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);
      $payload->addViolation(new Violation('EVENT_SAVE_FAILED'));
      return $payload;
    }
  }

  /**
   * Gets the current group IDs for a node from its group relationships.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of group IDs keyed by group ID (gid => gid).
   */
  protected function getCurrentGroupsFromRelationships(NodeInterface $node): array {
    $relationships = GroupRelationship::loadByEntity($node);
    $current = [];
    foreach ($relationships as $relationship) {
      $gid = $relationship->getGroup()->id();
      $current[$gid] = $gid;
    }
    return $current;
  }

}
