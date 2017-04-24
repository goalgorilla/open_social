<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoEntity;

/**
 * @DemoContent(
 *   id = "event_enrollment",
 *   label = @Translation("Event enrollment"),
 *   source = "content/entity/event-enrollment.yml",
 *   entity_type = "event_enrollment"
 * )
 */
class EventEnrollment extends DemoEntity {

  /**
   * {@inheritdoc}
   */
  public function getEntry($item) {
    $uid = $this->loadByUuid('user', $item['uid'])->id();
    $entry = parent::getEntry($item);

    return $entry + [
      'langcode' => $item['langcode'],
      'name' => substr($item['title'], 0, 50),
      'user_id' => $uid,
      'created' => REQUEST_TIME,
      'field_event' => $this->loadByUuid('node', $item['field_event'])->id(),
      'field_enrollment_status' => $item['field_enrollment_status'],
      'field_account' => $uid,
    ];
  }

}
