<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\social_role_visibility\Service\VisibilityElementManager;

/**
 * Type class for ContentVisibility data.
 */
class ContentVisibility {

  /**
   * Constructs the ContentVisibility type.
   */
  public function __construct(
    public readonly string $type,
    public readonly array $groups = [],
    public readonly array $roles = [],
  ) {}

  /**
   * Get formatted ContentVisibility output.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return self|null
   *   The ContentVisibility object or null.
   */
  public static function fromEntity(ContentEntityInterface $entity): ?self {
    if (!$entity->hasField('field_content_visibility')) {
      return NULL;
    }

    $visibility_type = $entity->get('field_content_visibility')->value;

    switch ($visibility_type) {
      case "group":
        $groups_ids = [];
        if ($entity->hasField('groups')) {
          /** @var \Drupal\group\Entity\GroupInterface $groups */
          $groups = $entity->get('groups');
          foreach ($groups->referencedEntities() as $group) {
            $groups_ids[] = $group->uuid();
          }
        }

        return new self(
          type: 'groups',
          groups: $groups_ids,
        );

      case "visibility_by_role":
        $roles = [];
        if ($entity->hasField('role_visibility')) {
          $visibility_roles = $entity->get('role_visibility')->getValue();

          foreach ($visibility_roles as $item) {
            $id = $item['value'];

            // CM+ is a special role that applies to multiple ids.
            if ($id == "cm_plus" && class_exists('\Drupal\social_role_visibility\Service\VisibilityElementManager')) {
              foreach (VisibilityElementManager::CM_PLUS_ROLES as $role_id) {
                $roles[] = $role_id;
              }
              continue;
            }

            $roles[] = $id;
          }
        }

        return new self(
          type: 'roles',
          roles: $roles,
        );

      default:
        return new self(
          type: (string) $visibility_type,
        );

    }
  }

}
