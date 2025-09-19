<?php

declare(strict_types=1);

namespace Drupal\social_post\Types;

use Drupal\group\Entity\GroupInterface;
use Drupal\social_post\Entity\PostInterface;
use Drupal\social_eda\Types\Entity;
use Drupal\social_eda\Types\User;
use Drupal\user\UserInterface;

/**
 * Type class for Stream data.
 */
final class Stream implements \JsonSerializable {

  /**
   * Constructs the Stream type.
   *
   * @param string $type
   *   The stream type: community, group, or user.
   * @param \Drupal\social_eda\Types\Entity|null $group
   *   The group object (only for group streams).
   * @param \Drupal\social_eda\Types\User|null $user
   *   The user object (only for user streams).
   */
  public function __construct(
    public readonly string $type,
    public readonly ?Entity $group = NULL,
    public readonly ?User $user = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    // Always include the type field.
    $data = ['type' => $this->type];

    // Only include group when type is 'group'.
    if ($this->type === 'group' && $this->group !== NULL) {
      $data['group'] = $this->group;
    }
    // Only include user when type is 'user'.
    elseif ($this->type === 'user' && $this->user !== NULL) {
      $data['user'] = $this->user;
    }

    return $data;
  }

  /**
   * Get formatted Stream output from a post entity.
   *
   * @param \Drupal\social_post\Entity\PostInterface $post
   *   The post entity.
   *
   * @return self
   *   The Stream data object.
   */
  public static function fromPost(PostInterface $post): self {
    // Check if post is targeted to a specific group.
    if ($post->hasField('field_recipient_group') && !$post->get('field_recipient_group')->isEmpty()) {
      $group = $post->get('field_recipient_group')->entity;
      if ($group instanceof GroupInterface) {
        return new self(
          type: 'group',
          group: Entity::fromEntity($group),
        );
      }
    }

    // Check if post is targeted to a specific user.
    if ($post->hasField('field_recipient_user') && !$post->get('field_recipient_user')->isEmpty()) {
      $user = $post->get('field_recipient_user')->entity;
      if ($user instanceof UserInterface) {
        return new self(
          type: 'user',
          user: User::fromEntity($user),
        );
      }
    }

    // Default to community stream.
    return new self(
      type: 'community',
    );
  }

}
