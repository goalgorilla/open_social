<?php

namespace Drupal\social_group_request\Plugin\Join;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Link;
use Drupal\social_group\JoinBase;
use Drupal\user\UserInterface;

/**
 * Provides a join plugin instance for the group entity type.
 *
 * @Join(
 *   id = "social_group_request_join",
 *   entityTypeId = "group",
 * )
 */
class SocialGroupRequestJoin extends JoinBase {

  /**
   * {@inheritdoc}
   */
  public function actions(ContentEntityInterface $entity, UserInterface $account): array {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    if ($entity->hasPermission('join group', $account)) {
      return [
        Link::createFromRoute(
          $text = $this->t('Request to join'),
          'entity.group.join',
          ['group' => $entity->id()],
          [
            'attributes' => [
              'class' => ['btn', 'btn-accent', 'btn-block', 'use-ajax'],
            ],
            'title' => $text,
          ],
        ),
      ];
    }

    return [];
  }

}
