<?php

namespace Drupal\social_course\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\gnode\Plugin\Group\RelationHandler\GroupNodePermissionProvider;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Decorates group permissions for the group_node relation plugin.
 */
class SocialCourseNodePermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;
  use StringTranslationTrait;

  /**
   * The list of node bundles using in course sections.
   */
  const COURSE_SECTION_CONTENT_BUNDLES = ['course_article', 'course_video'];

  /**
   * The entity type bundle info service.
   */
  protected EntityTypeBundleInfoInterface $entityTypeBundleInfo;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
   *
   * @param \Drupal\gnode\Plugin\Group\RelationHandler\GroupNodePermissionProvider $parent
   *   The decorated parent service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   */
  public function __construct(GroupNodePermissionProvider $parent, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->parent = $parent;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(): array {
    $permissions = $this->parent->buildPermissions();

    $node_bundles_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');

    // "Article" and "Video" node types don't have a relation with course, but
    // could be added to "Section" node, which could be a part of a course.
    // We need to add permissions allows to control access to "Article" and
    // "Video" nodes in a group context (usually, route context).
    if ($this->pluginId === 'group_node:course_section') {
      foreach (self::COURSE_SECTION_CONTENT_BUNDLES as $bundle) {
        foreach (['create', 'view', 'update', 'delete'] as $operation) {
          $permissions["$operation $bundle"] = [
            'title' => '%label: %operation content',
            'title_args' => [
              '%operation' => ucfirst($operation),
              '%label' => $node_bundles_info[$bundle]['label'] ?? '',
            ],
          ];
        }
      }
    }

    return $permissions;
  }

}
