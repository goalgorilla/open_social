<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoEntity;

/**
 * Post Plugin for demo content.
 *
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
  public function getEntry(array $item) {
    $recipient_id = NULL;
    $group_id = NULL;
    $file_id = NULL;
    $entry = parent::getEntry($item);
    $created = $this->createDate($item['created']);

    if (!empty($item['recipient']) && ($recipient = $this->loadByUuid('user', $item['recipient']))) {
      $recipient_id = $recipient->id();
    }

    if (!empty($item['group']) && ($group = $this->loadByUuid('group', $item['group']))) {
      $group_id = $group->id();
    }

    // Load image by uuid and set to post.
    if (!empty($item['field_post_image']) && ($file = $this->loadByUuid('file', $item['field_post_image']))) {
      $file_id = $file->id();
    }

    return $entry + [
      'langcode' => $item['langcode'],
      'type' => $item['type'],
      'field_post' => [
        'value' => $this->checkMentionOrLinkByUuid($item['field_post']),
        'format' => 'basic_html',
      ],
      'field_visibility' => $item['field_visibility'],
      'field_recipient_user' => $recipient_id,
      'field_recipient_group' => $group_id,
      'field_post_image' => $file_id,
      'user_id' => $this->loadByUuid('user', $item['uid'])->id(),
      'created' => $created,
      'changed' => $created,
    ];
  }

  /**
   * Converts a date in the correct format.
   *
   * @param string $date_string
   *   The date.
   *
   * @return int|false
   *   Returns a timestamp on success, false otherwise.
   */
  protected function createDate($date_string) {
    if ($date_string === 'now') {
      return time();
    }
    // Split from delimiter.
    $timestamp = explode('|', $date_string);

    $date = strtotime($timestamp[0]);
    $date = date('Y-m-d', $date) . 'T' . $timestamp[1] . ':00';

    return strtotime($date);
  }

}
