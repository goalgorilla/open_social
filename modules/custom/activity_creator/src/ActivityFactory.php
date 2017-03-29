<?php
/**
 * @file
 * ActivityFactory.
 */

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\message\Entity\Message;
use Drupal\activity_creator\Plugin\ActivityDestinationManager;

/**
 * Class ActivityFactory to create Activity items based on ActivityLogs.
 *
 * @package Drupal\activity_creator
 */
class ActivityFactory extends ControllerBase {

  /**
   * @var \Drupal\activity_creator\Plugin\ActivityDestinationManager
   */
  private $activityDestinationManager;

  public function __construct(ActivityDestinationManager $activityDestinationManager) {
    $this->activityDestinationManager = $activityDestinationManager;
  }

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
    // @TODO: Consider if we should put aggregation to separate service.
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
  private function getFieldOutputText(Message $message, $arguments = []) {
    $value = NULL;
    if (isset($message)) {

      $value = $this->getMessageText($message);

      // Text for aggregated activities.
      if (!empty($value[1]) && !empty($arguments)) {
        $text = str_replace('@count', $arguments['@count'], $value[1]);
      }
      // Text for default activities.
      else {
        $text = $value[0];
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
    $common_destinations = $this->activityDestinationManager->getListByProperties('is_common', TRUE);
    $personal_destinations = $this->activityDestinationManager->getListByProperties('is_common', FALSE);

    // Get related activities.
    $related_activities = $this->getAggregationRelatedActivities($data);
    if (!empty($related_activities)) {
      // Update related activities.
      foreach ($related_activities as $related_activity) {
        $destination = $related_activity->field_activity_destinations->value;
        // If user already have related activity we remove it and create new.
        // And we also remove related activities from common streams.
        if ($related_activity->getOwnerId() == $this->getActor($data) || in_array($destination, $common_destinations)) {
          // @TODO: Consider if need to delete or unpublish old activites.
          $related_activity->delete();
        }
        else {
          // For other users we leave activity only on their profile stream.
          $related_activity->set('field_activity_destinations', ['stream_profile']);
          $related_activity->save();
        }
      }

      // Clone activity fields for separate profile stream activity.
      $profile_activity_fields = $activity_fields;

      // Update output text for activity on not user related streams.
      $arguments = [];
      $message = Message::load($data['mid']);
      $count = $this->getAggregationAuthorsCount($data);
      if (is_numeric($count) && $count > 1) {
        $arguments = ['@count' => $count - 1];
      }
      $activity_fields['field_activity_output_text'] = $this->getFieldOutputText($message, $arguments);
      $activity_fields['field_activity_destinations'] = $this->getFieldDestinations($data, $common_destinations);

      // Create separate activity for activity on user related streams.
      $profile_activity_fields['field_activity_destinations'] = $this->getFieldDestinations($data, $personal_destinations);
      $activity = Activity::create($profile_activity_fields);
      $activity->save();
      $activities[] = $activity;
    }

    $activity = Activity::create($activity_fields);
    $activity->save();
    $activities[] = $activity;

    return $activities;
  }

  /**
   * Get related activities for activity aggregation.
   */
  private function getAggregationRelatedActivities($data) {
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
          // We exclude activities with email, platform_email and notifications
          // destinations from aggregation.
          $aggregatable_destinations = $this->activityDestinationManager->getListByProperties('is_aggregatable', TRUE);
          $activity_query->condition('field_activity_destinations.value', $aggregatable_destinations, 'IN');
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
  public static function getActivityRelatedEntity($data) {
    $related_object = $data['related_object'][0];

    // We return parent comment as related object as comment
    // for create_comment_reply messages.
    if ($data['message_template'] === 'create_comment_reply') {
      $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
      // @TODO: Check if comment published?
      $comment = $comment_storage->load($related_object['target_id']);
      $parent_comment = $comment->getParentComment();
      if (!empty($parent_comment)) {
        $related_object = [
          'target_type' => $parent_comment->getEntityTypeId(),
          'target_id' => $parent_comment->id(),
        ];
      }
    }
    // We return commented entity as related object for all other comments.
    elseif (isset($related_object['target_type']) && $related_object['target_type'] === 'comment') {
      $comment_storage = \Drupal::entityTypeManager()->getStorage('comment');
      // @TODO: Check if comment published?
      $comment = $comment_storage->load($related_object['target_id']);
      if($comment){
        $commented_entity = $comment->getCommentedEntity();
        if(!empty($commented_entity)) {
          $related_object = [
            'target_type' => $commented_entity->getEntityTypeId(),
            'target_id' => $commented_entity->id(),
          ];
        }
      }
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
      $related_entity = $this->getActivityRelatedEntity($data);
      if (!empty($related_entity['target_id']) && !empty($related_entity['target_type'])) {
        $query = \Drupal::database()->select('comment_field_data', 'cfd');
        $query->addExpression('COUNT(DISTINCT cfd.uid)');
        $query->condition('cfd.status', 1);
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

  /**
   * Get message text.
   *
   * @return array
   *    Message text array.
   */
  public function getMessageText(Message $message) {

    /** @var \Drupal\message\Entity\MessageTemplate $message_template */
    $message_template = $message->getTemplate();

    $message_arguments = $message->getArguments();
    $message_template_text = $message_template->get('text');

    $output = $this->processArguments($message_arguments, $message_template_text, $message);

    $token_options = $message_template->getSetting('token options', []);
    if (!empty($token_options['token replace'])) {
      // Token should be processed.
      $output = $this->processTokens($output, !empty($token_options['clear']), $message);
    }

    return $output;
  }


  /**
   * Process the message given the arguments saved with it.
   *
   * @param array $arguments
   *   Array with the arguments.
   * @param array $output
   *   Array with the templated text saved in the message template.
   *
   * @return array
   *   The templated text, with the placeholders replaced with the actual value,
   *   if there are indeed arguments.
   */
  protected function processArguments(array $arguments, array $output, Message $message) {
    // Check if we have arguments saved along with the message.
    if (empty($arguments)) {
      return $output;
    }

    foreach ($arguments as $key => $value) {
      if (is_array($value) && !empty($value['callback']) && is_callable($value['callback'])) {

        // A replacement via callback function.
        $value += ['pass message' => FALSE];

        if ($value['pass message']) {
          // Pass the message object as-well.
          $value['arguments']['message'] = $message;
        }

        $arguments[$key] = call_user_func_array($value['callback'], $value['arguments']);
      }
    }

    foreach ($output as $key => $value) {
      $output[$key] = new FormattableMarkup($value, $arguments);
    }

    return $output;
  }

  /**
   * Replace placeholders with tokens.
   *
   * @param array $output
   *   The templated text to be replaced.
   * @param bool $clear
   *   Determine if unused token should be cleared.
   *
   * @return array
   *   The output with placeholders replaced with the token value,
   *   if there are indeed tokens.
   */
  protected function processTokens(array $output, $clear, Message $message) {
    $options = [
      'clear' => $clear,
    ];

    foreach ($output as $key => $value) {
      if (is_string($value)) {
        $output[$key] = \Drupal::token()
          ->replace($value, ['message' => $message], $options);
      }
      else {
        if (isset($value['value'])) {
          $output[$key] = \Drupal::token()
            ->replace($value['value'], ['message' => $message], $options);
        }
      }
    }

    return $output;
  }

}
