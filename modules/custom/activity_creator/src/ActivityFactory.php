<?php
/**
 * @file
 * ActivityFactory.
 */

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\message\Entity\Message;

/**
 * Class ActivityFactory to create Activity items based on ActivityLogs.
 *
 * @package Drupal\activity_creator
 */
class ActivityFactory extends ControllerBase {

  /**
   * Create the activities based on a data array.
   *
   * @param array $data
   *    An array of data to create activity from.
   *
   * @return array
   *    An array of created activities.
   */
  public function createActivities(array $data) {
    $activities = $this->buildActivities($data);

    return $activities;
  }

  /**
   * Build the activities based on a data array.
   *
   * @param array $data
   *    An array of data to create activity from.
   *
   * @return array
   *    An array of created activities.
   */
  private function buildActivities(array $data) {
    $activities = [];
    $message = Message::load($data['mid']);
    // For every insert we create an activity item.
    $activity = Activity::create([
      'created' => $this->getCreated($message),
      'field_activity_destinations' => $this->getFieldDestinations($data),
      'field_activity_entity' => $this->getFieldEntity($data),
      'field_activity_message' => $this->getFieldMessage($data),
      'field_activity_output_text' => $this->getFieldOutputText($message),
      'field_activity_recipient_group' => $this->getFieldRecipientGroup($data),
      'field_activity_recipient_user' => $this->getFieldRecipientUser($data),
    // Default status.
      'field_activity_status' => ACTIVITY_STATUS_RECEIVED,
      'user_id' => $this->getActor($data),
    ]);

    $activity->save();
    $activities[] = $activity;

    return $activities;
  }

  /**
   * Get field value for 'destination' field from data array.
   */
  private function getFieldDestinations(array $data) {
    $value = NULL;
    if (isset($data['destination'])) {
      $value = $data['destination'];
    }
    return $value;
  }

  /**
   * Get field value for 'entity' field from data array.
   */
  private function getFieldEntity($data) {
    $value = NULL;
    if (isset($data['related_object'])) {
      $value = $data['related_object'];
    }
    return $value;
  }

  /**
   * Get field value for 'message' field from data array.
   */
  private function getFieldMessage($data) {
    $value = NULL;
    if (isset($data['mid'])) {
      $value = [];
      $value[] = [
        'target_id' => $data['mid'],
      ];
    }
    return $value;
  }

  /**
   * Get field value for 'output_text' field from data array.
   */
  private function getFieldOutputText(Message $message) {
    $value = NULL;
    if (isset($message)) {
      $value = $message->getText(NULL);
      $text = reset($value);
      // Add format.
      $value = [
        '0' => array(
          'value' => $text,
          'format' => 'basic_html',
        ),
      ];
    }

    return $value;
  }

  /**
   * Get field value for 'created' field from data array.
   */
  private function getCreated(Message $message) {
    $value = NULL;
    if (isset($message)) {
      $value = $message->getCreatedTime();
    }
    return $value;
  }

  /**
   * Get field value for 'recipient_group' field from data array.
   */
  private function getFieldRecipientGroup($data) {
    $value = NULL;
    if (isset($data['recipient'])) {
      if ($data['recipient']['target_type'] === 'group') {
        // Should be in an array for the field.
        $value = array($data['recipient']);
      }
    }
    return $value;
  }

  /**
   * Get field value for 'recipient_user' field from data array.
   */
  private function getFieldRecipientUser($data) {
    $value = NULL;
    if (isset($data['recipient']) && is_array($data['recipient'])) {
      if ($data['recipient']['target_type'] === 'user') {
        // Should be in an array for the field.
        $value = array($data['recipient']);
      }
    }
    return $value;
  }

  /**
   * Return the actor uid.
   *
   * @param array $data
   *    Array of data.
   *
   * @return int
   *    Value uid integer.
   */
  private function getActor($data) {
    $value = 0;
    if (isset($data['actor'])) {
      $value = $data['actor'];
    }
    return $value;
  }

}
