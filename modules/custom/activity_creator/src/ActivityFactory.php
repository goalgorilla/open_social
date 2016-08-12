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
    // Initialize fields for new activity entity.
    $activity_fields = [
      'created' => $this->getCreated($message),
      'field_activity_destinations' => $this->getFieldDestinations($data),
      'field_activity_entity' => $this->getFieldEntity($data),
      'field_activity_message' => $this->getFieldMessage($data),
      'field_activity_output_text' => $this->getFieldOutputText($message),
      'field_activity_recipient_group' => $this->getFieldRecipientGroup($data),
      'field_activity_recipient_user' => $this->getFieldRecipientUser($data),
      // Default activity status.
      'field_activity_status' => ACTIVITY_STATUS_RECEIVED,
      'user_id' => $this->getActor($data),
    ];

    // Check if aggregation is enabled for this message type.
    if ($this->getAggregationSettings($message)) {
      $activities = $this->buildAggregatedActivites($data, $activity_fields);
    }
    else {
      $activity = Activity::create($activity_fields);
      $activity->save();
      $activities[] = $activity;
    }

    return $activities;
  }

  /**
   * Get field value for 'destination' field from data array.
   */
  private function getFieldDestinations(array $data, $allowed_destinations = array()) {
    $value = NULL;
    if (isset($data['destination'])) {
      $value = $data['destination'];
      if (!empty($allowed_destinations)) {
        foreach ($value as $key => $destination) {
          if (!in_array($destination['value'], $allowed_destinations)) {
            unset($value[$key]);
          }
        }
      }
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
  private function getFieldOutputText(Message $message, $count = NULL) {
    $value = NULL;
    if (isset($message)) {
      $value = $message->getText(NULL);
      // Text for aggregated activities.
      if (is_numeric($count) && $count > 0 && !empty($value[1])) {
        $text = t($value[1], array('@count' => $count));
      }
      // Text for default activities.
      else {
        $text = reset($value);
      }

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
   * Get aggregation settings from message template.
   */
  private function getAggregationSettings(Message $message) {
    $value = NULL;
    $message_template = $message->getTemplate();
    return $message_template->getThirdPartySetting('activity_logger', 'activity_aggregate', NULL);
  }

  /**
   * Build the aggregated activities based on a data array.
   */
  private function buildAggregatedActivites($data, $activity_fields) {
    $activities = [];
    // Get related activities.
    $related_activities = $this->getAggregationRelatedActivities($data);
    if (!empty($related_activities)) {
      // Update related activities.
      foreach ($related_activities as $related_activity) {
        // If user already have related activity we remove it and crete new.
        if ($related_activity->getOwnerId() == $this->getActor($data)) {
          $related_activity->delete();
        }
        else {
          // For other users we leave activity only on their profile stream.
          $related_activity->set('field_activity_destinations', ['stream_profile']);
          $related_activity->save();
        }
      }

      // Get number of aggregated items.
      $count = $this->getAggregationAuthorsCount($data);
      // Clone activity fields for separate profile stream activity.
      $profile_activity_fields = $activity_fields;
      // Update output text for activity on not user related streams.
      $message = Message::load($data['mid']);
      $activity_fields['field_activity_output_text'] = $this->getFieldOutputText($message, $count - 1);
      $allowed_destinations = ['stream_group', 'stream_home', 'stream_explore'];
      $activity_fields['field_activity_destinations'] = $this->getFieldDestinations($data, $allowed_destinations);
      $activity = Activity::create($activity_fields);
      $activity->save();
      $activities[] = $activity;

      // Create separate activity for activity on user related streams.
      $profile_allowed_destinations = ['stream_profile', 'notifications'];
      $profile_activity_fields['field_activity_destinations'] = $this->getFieldDestinations($data, $profile_allowed_destinations);
      $activity = Activity::create($profile_activity_fields);
      $activity->save();
      $activities[] = $activity;
    }

    return $activities;
  }

  /**
   * Get related activities for activity aggregation.
   */
  public static function getAggregationRelatedActivities($data) {
    $activities = array();
    $related_object = $data['related_object'][0];
    if (!empty($related_object['target_id']) && !empty($related_object['target_type'])) {
      if ($related_object['target_type'] === 'comment') {
        // Get commented entity.
        $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
        $comment = $comment_storage->load($related_object['target_id']);
        $commented_entity = $comment->getCommentedEntity();
        // Get all comments of commented entity.
        $comment_query = \Drupal::entityQuery('comment');
        $comment_query->condition('entity_id', $commented_entity->id(), '=');
        $comment_query->condition('entity_type', $commented_entity->getEntityTypeId(), '=');
        $comment_ids = $comment_query->execute();
        // Get all activities provided by comments of commented entity.
        if (!empty($comment_ids)) {
          $activity_query = \Drupal::entityQuery('activity');
          $activity_query->condition('field_activity_entity.target_id', $comment_ids, 'IN');
          $activity_query->condition('field_activity_entity.target_type', $related_object['target_type'], '=');
          $activity_ids = $activity_query->execute();
          if (!empty($activity_ids)) {
            $activities = Activity::loadMultiple($activity_ids);
          }
        }
      }
    }
    return $activities;
  }

  /**
   * Get related entity for activity aggregation.
   */
  private function getAggregationRelatedEntity($data) {
    $related_object = $data['related_object'][0];
    if (isset($related_object['target_type']) && $related_object['target_type'] === 'comment') {
      $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
      $comment = $comment_storage->load($related_object['target_id']);
      $commented_entity = $comment->getCommentedEntity();
      $related_object = [
        'target_type' => $commented_entity->getEntityTypeId(),
        'target_id' => $commented_entity->id(),
      ];
    }
    return $related_object;
  }

  /**
   * Get unique authors number for activity aggregation.
   */
  private function getAggregationAuthorsCount($data) {
    $count = 0;
    $related_object = $data['related_object'][0];
    if (isset($related_object['target_type']) && $related_object['target_type'] === 'comment') {
      // Get related entity.
      $related_entity = $this->getAggregationRelatedEntity($data);
      if (!empty($related_entity['target_id']) && !empty($related_entity['target_type'])) {
        $query = \Drupal::database()->select('comment_field_data', 'cfd');
        $query->addExpression('COUNT(DISTINCT cfd.uid)');
        $query->condition('cfd.entity_type', $related_entity['target_type']);
        $query->condition('cfd.entity_id', $related_entity['target_id']);
        $count = $query->execute()->fetchField();
      }
    }
    return $count;
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
