<?php

declare(strict_types=1);

namespace Drupal\social_post\Types;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Type class for Post ContentVisibility data.
 */
final class PostContentVisibility implements \JsonSerializable {

  /**
   * Constructs the PostContentVisibility type.
   */
  public function __construct(
    public readonly string $type,
    public readonly ?array $groups = NULL,
    public readonly ?array $roles = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    // Always include the type field.
    $data = ['type' => $this->type];

    // Only include groups array when type is 'groups' and groups exist.
    if ($this->type === 'groups' && !empty($this->groups)) {
      $data['groups'] = array_values($this->groups);
    }
    // Only include roles array when type is 'roles' and roles exist.
    elseif ($this->type === 'roles' && !empty($this->roles)) {
      $data['roles'] = array_values($this->roles);
    }

    return $data;
  }

  /**
   * Get formatted PostContentVisibility output from a post entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The post entity object.
   *
   * @return self|null
   *   The PostContentVisibility object or null.
   */
  public static function fromPost(ContentEntityInterface $entity): ?self {
    if (!$entity->hasField('field_visibility')) {
      return NULL;
    }

    $visibility_value = $entity->get('field_visibility')->value;

    switch ($visibility_value) {
      case "0":
      case "2":
        // Community (recipient).
        // Community.
        return new self(type: 'community');

      case "1":
        // Public.
        return new self(type: 'public');

      case "3":
        // Group members.
        return self::createGroupVisibility($entity);

      default:
        // Anything else is a role.
        return self::createRoleVisibilityFromPost($entity, $visibility_value);
    }
  }

  /**
   * Creates group visibility for posts.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The post entity.
   *
   * @return self
   *   The PostContentVisibility object with group visibility.
   */
  private static function createGroupVisibility(ContentEntityInterface $entity): self {
    $groups_ids = [];
    if ($entity->hasField('field_recipient_group') && !$entity->get('field_recipient_group')->isEmpty()) {
      $group = $entity->get('field_recipient_group')->entity;
      if ($group instanceof GroupInterface) {
        $groups_ids[] = $group->uuid();
      }
    }

    return new self(
      type: 'groups',
      groups: $groups_ids,
    );
  }

  /**
   * Creates role visibility for posts based on field_visibility value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The post entity.
   * @param string $visibility_value
   *   The visibility value from field_visibility.
   *
   * @return self
   *   The PostContentVisibility object with role visibility.
   */
  private static function createRoleVisibilityFromPost(ContentEntityInterface $entity, string $visibility_value): self {
    $allowed_values = $entity->get('field_visibility')->getFieldDefinition()->getSetting('allowed_values');
    if (!$allowed_values) {
      return new self(type: 'community');
    }

    foreach ($allowed_values as $option) {
      if ($option['value'] === $visibility_value) {
        // If it's not one of the standard options, treat it as a role.
        if (!in_array($visibility_value, ['0', '1', '2', '3'])) {
          $roles = self::findRolesByLabel($option['label']);
          return new self(
            type: 'roles',
            roles: $roles,
          );
        }
      }
    }

    // Fallback to community.
    return new self(type: 'community');
  }

  /**
   * Finds role IDs by role label.
   *
   * @param string $label
   *   The role label to search for.
   *
   * @return array
   *   Array of role IDs.
   */
  private static function findRolesByLabel(string $label): array {
    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
    $role_ids = $role_storage->getQuery()
      ->condition('label', $label)
      ->execute();

    return !empty($role_ids) ? array_values($role_ids) : [];
  }

}
