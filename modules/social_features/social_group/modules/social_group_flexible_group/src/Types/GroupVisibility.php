<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Types;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\social_role_visibility\Service\VisibilityElementManager;

/**
 * Type class for GroupVisibility data.
 */
class GroupVisibility {

  /**
   * Constructs the ContentVisibility type.
   */
  public function __construct(
    public readonly string $type,
    public readonly array $roles = [],
  ) {}

  /**
   * Get formatted GroupVisibility output.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return self|null
   *   The GroupVisibility object or null.
   */
  public static function fromEntity(ContentEntityInterface $entity): ?self {
    if (!$entity->hasField('field_flexible_group_visibility')) {
      return NULL;
    }

    $visibility_type = $entity->get('field_flexible_group_visibility')->value;

    switch ($visibility_type) {
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
