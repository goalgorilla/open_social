<?php

namespace Drupal\social_group\Plugin\Join;

use Drupal\Core\Link;
use Drupal\social_group\EntityMemberInterface;
use Drupal\social_group\JoinBase;

/**
 * Provides a join plugin instance for members.
 *
 * @Join(
 *   id = "social_group_already_join",
 * )
 */
class SocialGroupAlreadyJoin extends JoinBase {

  /**
   * {@inheritdoc}
   */
  public function actions(EntityMemberInterface $entity, array &$variables): array {
    $items = [];

    if (!$entity->hasMember($this->currentUser)) {
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

    $variables['joined'] = TRUE;

    return $items;
  }

}
