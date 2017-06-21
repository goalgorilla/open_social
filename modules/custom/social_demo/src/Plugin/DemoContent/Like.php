<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoEntity;

/**
 * @DemoContent(
 *   id = "like",
 *   label = @Translation("Like"),
 *   source = "content/entity/like.yml",
 *   entity_type = "vote"
 * )
 */
class Like extends DemoEntity {

  /**
   * {@inheritdoc}
   */
  public function getEntry($item) {
    $entry = parent::getEntry($item);

    return $entry + [
      'type' => $item['type'],
      'entity_type' => $item['entity_type'],
      'entity_id' => $this->loadByUuid($item['entity_type'], $item['entity_id'])->id(),
      'value' => $item['value'],
      'value_type' => $item['value_type'],
      'user_id' => $this->loadByUuid('user', $item['uid'])->id(),
      'timestamp' => REQUEST_TIME,
      'vote_source' => $item['vote_source'],
    ];
  }

}
