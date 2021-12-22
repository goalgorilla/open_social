<?php

namespace Drupal\social_group\Plugin\Join;

use Drupal\Core\Link;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\JoinBase;
use Drupal\user\UserInterface;

/**
 * Provides a join plugin instance for all entity types.
 *
 * @Join(
 *   id = "social_group_join",
 * )
 */
class SocialGroupJoin extends JoinBase {

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, UserInterface $account): array {
    $items = [];

    if (!$entity->hasMember($account)) {
      return $items;
    }

    $items[] = $this->t('Joined', [], ['context' => 'Is a member']);

    $entity_type_id = $entity->getEntityTypeId();

    $items[] = Link::createFromRoute(
      $this->t(
        'Leave @entity_type_id',
        ['@entity_type_id' => $entity->getEntityType()->getSingularLabel()],
      ),
      'entity.' . $entity_type_id . '.leave',
      [$entity_type_id => $entity->id()],
    );

    return $items;
  }

}
