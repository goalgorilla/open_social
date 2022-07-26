<?php

namespace Drupal\social_group\Plugin\Join;

use Drupal\Core\Url;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\JoinBase;

/**
 * Provides a join plugin instance for joining directly.
 *
 * @Join(
 *   id = "social_group_direct_join",
 *   entityTypeId = "group",
 *   method = "direct",
 *   weight = 10,
 * )
 */
class SocialGroupDirectJoin extends JoinBase {

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array {
    $items = [];

    if (!$this->access($entity)) {
      return $items;
    }

    $entity_type_id = $entity->getEntityTypeId();

    $url = Url::fromRoute(
      'entity.' . $entity_type_id . '.join',
      [$entity_type_id => $entity->id()],
    );

    $items[] = [
      'label' => $this->t('Join'),
      'url' => $url,
      'attributes' => [
        'class' => ['btn-accent'],
      ],
    ];

    $variables['group_operations_url'] = $url;

    return $items;
  }

  /**
   * Check if a user can join directly.
   *
   * @param \Drupal\social_group\EntityMemberInterface $entity
   *   The membership entity object.
   */
  protected function access(EntityMemberInterface $entity): bool {
    /** @var \Drupal\social_group\SocialGroupInterface $entity */
    return $entity->hasPermission('join group', $this->currentUser) ||
      $this->currentUser->isAnonymous() &&
      in_array($entity->bundle(), ['flexible_group', 'public_group']);
  }

}
