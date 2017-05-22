<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoEntity;

/**
 * @DemoContent(
 *   id = "post",
 *   label = @Translation("Post"),
 *   source = "content/entity/post.yml",
 *   entity_type = "post"
 * )
 */
class Post extends DemoEntity {

  /**
   * {@inheritdoc}
   */
  public function getEntry($item) {
    $recipient_id = NULL;
    $group_id = NULL;
    $entry = parent::getEntry($item);
    $created = $this->createDate($item['created']);

    if (!empty($item['recipient']) && ($recipient = $this->loadByUuid('user', $item['recipient']))) {
      $recipient_id = $recipient->id();
    }

    if (!empty($item['group']) && ($group = $this->loadByUuid('group', $item['group']))) {
      $group_id = $group->id();
    }

    return $entry + [
      'langcode' => $item['langcode'],
      'type' => $item['type'],
      'field_post' => $item['field_post'],
      'field_visibility' => $item['field_visibility'],
      'field_recipient_user' => $recipient_id,
      'field_recipient_group' => $group_id,
      'user_id' => $this->loadByUuid('user', $item['uid'])->id(),
      'created' => $created,
      'changed' => $created,
    ];
  }

  /**
   * Converts a date in the correct format.
   *
   * @param string $date_string
   * @return int|false
   */
  protected function createDate($date_string) {
    // Split from delimiter.
    $timestamp = explode('|', $date_string);

    $date = strtotime($timestamp[0]);
    $date = date('Y-m-d', $date) . 'T' . $timestamp[1] . ':00';

    return strtotime($date);
  }

}
