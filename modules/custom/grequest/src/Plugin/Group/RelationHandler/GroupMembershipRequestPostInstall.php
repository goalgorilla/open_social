<?php

namespace Drupal\grequest\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\grequest\Plugin\Group\Relation\GroupMembershipRequest;
use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface;
use Drupal\group\Plugin\Group\RelationHandler\PostInstallTrait;

/**
 * Provides post install tasks for the group_membership_request relation plugin.
 */
class GroupMembershipRequestPostInstall implements PostInstallInterface {

  use PostInstallTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new GroupMembershipRequestPostInstall.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface $parent
   *   The default post install handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(PostInstallInterface $parent, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallTasks() {
    $tasks = $this->parent->getInstallTasks();
    $tasks['install-group-request-membership-fields'] = [$this, 'installGroupRequestsMembershipFields'];
    return $tasks;
  }

  /**
   * Installs group membership request fields.
   *
   * @param \Drupal\group\Entity\GroupRelationshipTypeInterface $relationship_type
   *   The GroupRelationshipType created by installing the plugin.
   * @param $is_syncing
   *   Whether config is syncing.
   */
  public function installGroupRequestsMembershipFields(GroupRelationshipTypeInterface $relationship_type, $is_syncing) {

    // Only create config objects while config import is not in progress.
    if ($is_syncing === TRUE) {
      return;
    }

    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $entity_view_display_storage = $this->entityTypeManager->getStorage('entity_view_display');

    $relationship_type_id = $relationship_type->id();

    // Add Status field.
    $field_config_storage->create([
      'field_storage' => $field_storage_config_storage->load('group_content.' . GroupMembershipRequest::STATUS_FIELD),
      'bundle' => $relationship_type_id,
      'label' => $this->t('Request status'),
      'required' => TRUE,
      'settings' => [
        'workflow' => 'request',
        'workflow_callback' => '',
      ],
    ])->save();

    // Add "Updated by" field, to save reference to
    // user who approved/denied request.
    $field_config_storage->create([
      'field_storage' => $field_storage_config_storage->load('group_content.grequest_updated_by'),
      'bundle' => $relationship_type_id,
      'label' => $this->t('Approved/Rejected by'),
      'settings' => [
        'handler' => 'default',
        'target_bundles' => NULL,
      ],
    ])->save();

    // Build the 'default' display ID for both the entity form and view mode.
    $default_display_id = "group_content.$relationship_type_id.default";
    // Build or retrieve the 'default' view mode.
    if (!$view_display = $entity_view_display_storage->load($default_display_id)) {
      $view_display = $entity_view_display_storage->create([
        'targetEntityType' => 'group_content',
        'bundle' => $relationship_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Assign display settings for the 'default' view mode.
    $view_display
      ->setComponent('grequest_status', [
        'type' => 'list_default',
      ])
      ->setComponent('grequest_updated_by', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'settings' => [
          'link' => 1,
        ],
      ])
      ->save();

  }

}
