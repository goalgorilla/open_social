<?php

namespace Drupal\social_group\Plugin\Field;

use Drupal\activity_creator\FirstPublicationMarker;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\social_group\GroupRelationshipStanding;

/**
 * A computed property for the related groups.
 */
class SocialGroupReferenceItemList extends EntityReferenceFieldItemList {

  // Support non-database views. Ex: Search API Solr.
  use DependencySerializationTrait;
  use ComputedItemListTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The single owner of the group relationship standing rule.
   *
   * Resolved on first use rather than in the constructor: this list is built
   * for every entity carrying the field, on every load, while the rule is only
   * consulted when the entity is saved.
   *
   * @var \Drupal\social_group\GroupRelationshipStanding|null
   */
  protected ?GroupRelationshipStanding $groupRelationshipStanding = NULL;

  /**
   * Constructs a SocialGroupReferenceItemList object.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $name
   *   (optional) The name of the created property, or NULL if it is the root
   *   of a typed data tree. Defaults to NULL.
   * @param \Drupal\Core\TypedData\TypedDataInterface|null $parent
   *   (optional) The parent object of the data property, or NULL if it is the
   *   root of a typed data tree. Defaults to NULL.
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, ?TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue(): void {
    // @todo "$plugin_id" should be counted dynamically.
    // We only support nodes and users.
    if ($this->getEntity()->getEntityTypeId() === 'node') {
      $plugin_id = 'group_node:' . $this->getEntity()->bundle();
    }
    elseif ($this->getEntity()->getEntityTypeId() === 'user') {
      $plugin_id = 'group_membership';
    }
    elseif ($this->getEntity()->getEntityTypeId() === 'media') {
      $plugin_id = 'group_media:' . $this->getEntity()->bundle();
    }
    else {
      return;
    }

    // No value will exist if the entity has not been created so exit early.
    if ($this->getEntity()->isNew()) {
      return;
    }

    $handler_settings = $this->getItemDefinition()->getSetting('handler_settings');
    $group_types = $handler_settings['target_bundles'] ?? $this->entityTypeManager
      ->getStorage('group_type')
      ->loadMultiple();

    $group_relationship_types = $this->entityTypeManager
      ->getStorage('group_content_type')
      ->loadByProperties([
        'group_type' => array_keys($group_types),
        'content_plugin' => $plugin_id,
      ]);

    if (empty($group_relationship_types)) {
      return;
    }

    /** @var \Drupal\group\Entity\GroupRelationshipInterface[] $group_relationships */
    $group_relationships = $this->entityTypeManager
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_relationship_types),
        'entity_id' => $this->getEntity()->id(),
      ]);

    $this->list = [];

    if (empty($group_relationships)) {
      return;
    }

    foreach ($group_relationships as $delta => $group_relationship) {
      $this->list[] = $this->createItem($delta, [
        'target_id' => $group_relationship->getGroupId(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    if (!$this->valueComputed) {
      return parent::postSave($update);
    }

    $entity = $this->getEntity();

    // Get groups from the field.
    $gids_wanted = [];

    // Get all group ids where the current entity has a group relationship.
    $gids_existing = [];

    // Get all group relationships for the current entity.
    $group_relationship_existing = [];

    foreach ($this->list as $delta => $item) {
      $gid = $item->get('target_id')->getValue();
      $gids_wanted[$gid] = $gid;
    }

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_content');

    // Loads all groups with a relation to the node.
    foreach ($storage->loadByEntity($entity) as $group_relationship) {
      // Fill Index-Array with existing groups gid => gid.
      $gid = $group_relationship->getGroup()->id();
      $gids_existing[$gid] = $gid;

      // Cache the relationship in array to make possible to use
      // it later.
      $group_relationship_existing[$gid] = $group_relationship;
    }

    // Reconciling here rather than in the group selector widget covers every
    // write path, because a locked form control only constrains an honest
    // browser (PROD-36392). Keep in sync with the same guard in
    // SetGroupsForNodeService::setGroupsForNode(), which covers the writes
    // that delete relationships outside an entity save.
    $this->groupRelationshipStanding ??= \Drupal::service('social_group.group_relationship_standing');

    foreach ($this->groupRelationshipStanding->lockedGroupIds($entity, $group_relationship_existing) as $gid) {
      if (!isset($gids_wanted[$gid])) {
        $gids_wanted[$gid] = $gid;

        // Keep the computed value in step with what is actually written. Code
        // running after the save - social_group_save_group_from_node()
        // reconciles the very same relationships once more - must see the
        // groups that were preserved, not the value that was submitted.
        $this->appendItem(['target_id' => $gid]);
      }
    }

    // Union for existing and wanted groups.
    $gids_union = $gids_existing + $gids_wanted;

    // The current entity gets a new group relations.
    $should_be_created = array_diff($gids_union, $gids_existing);

    // Creating a relationship saves it synchronously, so the activity entity
    // conditions run inside this loop. Tell them whether the content is
    // entering its groups for the first time: $update is FALSE only on the
    // entity's own insert, so a later edit that adds a group stays silent.
    // $entity->original is still available here, because the storage unsets it
    // only after invoking the insert and update hooks, but it is deliberately
    // not used: a save that publishes a draft is an update, and the acceptance
    // criteria scope the notification to creation.
    $previously_raised = FirstPublicationMarker::isRaised();
    FirstPublicationMarker::setRaised(!$update);

    try {
      foreach ($should_be_created as $gid) {
        $group = Group::load($gid);
        if (!$group instanceof GroupInterface) {
          // Group can't be loaded.
          continue;
        }

        /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage('group_content_type');

        [
          // Some group relationships have non-bundle ids like
          // "group_membership".
          $plugin_type,
          // Other ones are bundled, like "group_node:topic".
          $derivative_plugin_type,
        ] = [
          $storage->getRelationshipTypeId($group->bundle(), 'group_' . $entity->getEntityTypeId()),
          $storage->getRelationshipTypeId($group->bundle(), 'group_' . $entity->getEntityTypeId() . ':' . $entity->bundle()),
        ];

        // First try to load non-bundled, then with using bundle id.
        if (!$group_relationship_entity_type = $storage->load($plugin_type)) {
          $group_relationship_entity_type = $storage->load($derivative_plugin_type);
        }

        if (!$group_relationship_entity_type) {
          // It seems like we can't detect the relationship entity for the
          // current group and entity.
          continue;
        }

        $group_content = GroupRelationship::create([
          'type' => $group_relationship_entity_type->id(),
          'gid' => $group->id(),
          'entity_id' => $entity->id(),
        ]);
        $group_content->save();
      }
    }
    finally {
      FirstPublicationMarker::setRaised($previously_raised);
    }

    // The group relation entities that should be deleted. It because
    // the current entity lost the relations with the current group.
    $should_be_deleted = array_diff($gids_union, $gids_wanted);
    foreach ($should_be_deleted as $gid) {
      $group_relationship_existing[$gid]->delete();
    }

    return parent::postSave($update);
  }

}
