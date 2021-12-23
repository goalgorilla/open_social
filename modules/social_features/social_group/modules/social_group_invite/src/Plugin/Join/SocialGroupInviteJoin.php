<?php

namespace Drupal\social_group_invite\Plugin\Join;

use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\Plugin\Join\SocialGroupDirectJoin;
use Drupal\user\UserInterface;

/**
 * Provides a join plugin instance for joining after invitation.
 *
 * @Join(
 *   id = "social_group_invite_join",
 *   entityTypeId = "group",
 *   method = "added",
 *   weight = 20,
 * )
 */
class SocialGroupInviteJoin extends SocialGroupDirectJoin {

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, UserInterface $account): array {
    /** @var \Drupal\social_group\SocialGroupInterface $entity */
    if (
      count($items = parent::actions($entity, $account)) === 1 &&
      $entity->bundle() === 'flexible_group' ||
      $entity->bundle() === 'closed_group' &&
      !$entity->hasPermission('manage all groups', $account)
    ) {
      if (count($items) === 0) {
        $items[] = ['attributes' => ['class' => ['btn-accent']]];
      }
      else {
        unset($items[0]['url']);
      }

      $items[0]['label'] = $this->t('Invitation only');
    }

    return $items;
  }

}
