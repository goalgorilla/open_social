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

    /** @var \Drupal\social_group\SocialGroupInterface $entity */
    if (
      $entity->hasPermission('join group', $this->currentUser) ||
      $this->currentUser->isAnonymous() &&
      in_array($entity->bundle(), ['flexible_group', 'public_group'])
    ) {
      $items[] = [
        'label' => $this->t('Join'),
        'url' => Url::fromRoute('entity.group.join', ['group' => $entity->id()]),
        'attributes' => [
          'class' => ['btn-accent'],
        ],
      ];
    }

    return $items;
  }

}
