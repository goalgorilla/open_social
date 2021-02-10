<?php

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\activity_creator\Plugin\ActivityDestinationManager;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\message\Entity\Message;
use Drupal\social_event\EventEnrollmentInterface;

/**
 * Class ActivityFactory to create Activity items based on ActivityLogs.
 *
 * @package Drupal\activity_creator
 */
class ActivityFactory extends ControllerBase {

  /**
   * Activity destination manager.
   *
   * @var \Drupal\activity_creator\Plugin\ActivityDestinationManager
   */
  protected $activityDestinationManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The connection to the database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The token replacement instance.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ActivityFactory constructor.
   *
   * @param \Drupal\activity_creator\Plugin\ActivityDestinationManager $activityDestinationManager
   *   The activity destination manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The connection to the database.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The new language manager.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ActivityDestinationManager $activityDestinationManager,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LanguageManagerInterface $language_manager,
    Token $token,
    ModuleHandlerInterface $module_handler
  ) {
    $this->activityDestinationManager = $activityDestinationManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->languageManager = $language_manager;
    $this->token = $token;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Create the activities based on a data array.
   *
   * @param array $data
   *   An array of data to create activity from.
   *
   * @return array
   *   An array of created activities.
   */
  public function createActivities(array $data) {
    $activities = $this->buildActivities($data);

    return $activities;
  }

  /**
   * Build the activities based on a data array.
   *
   * @param array $data
   *   An array of data to create activity from.
   *
   * @return array
   *   An array of created activities.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function buildActivities(array $data) {
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
    // @todo Consider if we should put aggregation to separate service.
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
  protected function getFieldDestinations(array $data, $allowed_destinations = []) {
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
  protected function getFieldEntity($data) {
    $value = NULL;
    if (isset($data['related_object'])) {
      $value = $data['related_object'];
    }
    return $value;
  }

  /**
   * Get field value for 'message' field from data array.
   */
  protected function getFieldMessage($data) {
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
  protected function getFieldOutputText(Message $message, $arguments = []) {
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
        '0' => [
          'value' => $text,
          'format' => 'basic_html',
        ],
      ];
    }

    return $value;
  }

  /**
   * Get field value for 'created' field from data array.
   */
  protected function getCreated(Message $message) {
    $value = NULL;
    if (isset($message)) {
      $value = $message->getCreatedTime();
    }
    return $value;
  }

  /**
   * Get aggregation settings from message template.
   */
  protected function getAggregationSettings(Message $message) {
    $message_template = $message->getTemplate();
    return $message_template->getThirdPartySetting('activity_logger', 'activity_aggregate', NULL);
  }

  /**
   * Build the aggregated activities based on a data array.
   */
  protected function buildAggregatedActivites($data, $activity_fields) {
    $activities = [];
    $common_destinations = $this->activityDestinationManager->getListByProperties('isCommon', TRUE);
    $personal_destinations = $this->activityDestinationManager->getListByProperties('isCommon', FALSE);

    // Get related activities.
    $related_activities = $this->getAggregationRelatedActivities($data);
    if (!empty($related_activities)) {
      // Update related activities.
      foreach ($related_activities as $related_activity) {
        $destination = $related_activity->field_activity_destinations->value;
        // If user already have related activity we remove it and create new.
        // And we also remove related activities from common streams.
        if ($related_activity->getOwnerId() == $this->getActor($data) || in_array($destination, $common_destinations)) {
          // @todo Consider if need to delete or unpublish old activites.
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
  protected function getAggregationRelatedActivities($data) {
    $activities = [];
    $related_object = $data['related_object'][0];
    if (!empty($related_object['target_id']) && !empty($related_object['target_type'])) {
      if ($related_object['target_type'] === 'comment') {
        // Get commented entity.
        $comment_storage = $this->entityTypeManager->getStorage('comment');
        $comment = $comment_storage->load($related_object['target_id']);
        $commented_entity = $comment->getCommentedEntity();
        // Get all comments of commented entity.
        $comment_query = $this->entityTypeManager->getStorage('comment')->getQuery();
        $comment_query->condition('entity_id', $commented_entity->id(), '=');
        $comment_query->condition('entity_type', $commented_entity->getEntityTypeId(), '=');
        $comment_ids = $comment_query->execute();
        // Get all activities provided by comments of commented entity.
        if (!empty($comment_ids)) {
          $activity_query = $this->entityTypeManager->getStorage('activity')->getQuery();
          $activity_query->condition('field_activity_entity.target_id', $comment_ids, 'IN');
          $activity_query->condition('field_activity_entity.target_type', $related_object['target_type'], '=');
          // We exclude activities with email, platform_email and notifications
          // destinations from aggregation.
          $aggregatable_destinations = $this->activityDestinationManager->getListByProperties('isAggregatable', TRUE);
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
  public function getActivityRelatedEntity($data) {
    $related_object = $data['related_object'][0];

    // We return parent comment as related object as comment
    // for create_comment_reply messages.
    if ($data['message_template'] === 'create_comment_reply') {
      $comment_storage = $this->entityTypeManager->getStorage('comment');
      // @todo Check if comment published?
      $comment = $comment_storage->load($related_object['target_id']);
      if ($comment) {
        $parent_comment = $comment->getParentComment();
        if (!empty($parent_comment)) {
          $related_object = [
            'target_type' => $parent_comment->getEntityTypeId(),
            'target_id' => $parent_comment->id(),
          ];
        }
      }
    }
    // We return commented entity as related object for all other comments.
    elseif (isset($related_object['target_type']) && $related_object['target_type'] === 'comment') {
      $comment_storage = $this->entityTypeManager->getStorage('comment');
      // @todo Check if comment published?
      $comment = $comment_storage->load($related_object['target_id']);
      if ($comment) {
        $commented_entity = $comment->getCommentedEntity();
        if (!empty($commented_entity)) {
          $related_object = [
            'target_type' => $commented_entity->getEntityTypeId(),
            'target_id' => $commented_entity->id(),
          ];
        }
      }
    }
    // We return Event as related object for all Event Enrollments.
    elseif (isset($related_object['target_type']) && $related_object['target_type'] === 'event_enrollment') {
      $entity_storage = $this->entityTypeManager
        ->getStorage($related_object['target_type']);
      $entity = $entity_storage->load($related_object['target_id']);

      if ($entity instanceof EventEnrollmentInterface) {
        /** @var \Drupal\social_event\Entity\EventEnrollment $entity */
        $event_id = $entity->getFieldValue('field_event', 'target_id');
        if (!empty($event_id)) {
          $related_object = [
            'target_type' => 'node',
            'target_id' => $event_id,
          ];
        }
      }
    }

    $this->moduleHandler->alter('activity_creator_related_entity_object', $related_object, $data);

    return $related_object;
  }

  /**
   * Get unique authors number for activity aggregation.
   */
  protected function getAggregationAuthorsCount(array $data) {
    $count = 0;
    $related_object = $data['related_object'][0];
    if (isset($related_object['target_type']) && $related_object['target_type'] === 'comment') {
      // Get related entity.
      $related_entity = $this->getActivityRelatedEntity($data);
      if (!empty($related_entity['target_id']) && !empty($related_entity['target_type'])) {
        $query = $this->database->select('comment_field_data', 'cfd');
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
  protected function getFieldRecipientGroup($data) {
    $value = NULL;
    if (isset($data['recipient']['target_type']) && $data['recipient']['target_type'] === 'group') {
      // Should be in an array for the field.
      $value = [$data['recipient']];
    }
    return $value;
  }

  /**
   * Get field value for 'recipient_user' field from data array.
   */
  protected function getFieldRecipientUser($data) {
    $value = NULL;
    $user_recipients = [];
    if (isset($data['recipient']) && is_array($data['recipient'])) {
      // Get activities by type and check when there are users entities.
      $activity_by_type = array_column($data['recipient'], 'target_type');
      foreach ($activity_by_type as $recipients_key => $target_type) {
        if ($target_type === 'user') {
          $user_recipients[] = $data['recipient'][$recipients_key];
        }
      }

      if (!empty($user_recipients)) {
        $value = $user_recipients;
      }
    }
    return $value;
  }

  /**
   * Return the actor uid.
   *
   * @param array $data
   *   Array of data.
   *
   * @return int
   *   Value uid integer.
   */
  protected function getActor(array $data) {
    $value = 0;
    if (isset($data['actor'])) {
      $value = $data['actor'];
    }
    return $value;
  }

  /**
   * Get message text.
   *
   * @param \Drupal\message\Entity\Message $message
   *   Message object we get the text for.
   * @param string $langcode
   *   The language code we try to get the translation for.
   *
   * @return array
   *   Message text array.
   */
  public function getMessageText(Message $message, $langcode = '') {
    /** @var \Drupal\message\Entity\MessageTemplate $message_template */
    $message_template = $message->getTemplate();

    $message_arguments = $message->getArguments();
    $message_template_text = $message_template->get('text');

    // If we have a language code here we can try to get a translated text.
    if (!empty($langcode)) {
      $language_manager = $this->languageManager;
      if ($language_manager instanceof ConfigurableLanguageManagerInterface) {
        // Load the language override for the message template.
        $config_translation = $language_manager->getLanguageConfigOverride($langcode, 'message.template.' . $message_template->id());
        $translated_text = $config_translation->get('text');

        // Replace the text *only* if we have an translation available.
        if ($translated_text) {
          $message_template_text = $translated_text;
        }
      }
    }

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
   * @param \Drupal\message\Entity\Message $message
   *   Message object.
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
   * @param \Drupal\message\Entity\Message $message
   *   Message object.
   *
   * @return array
   *   The output with placeholders replaced with the token value,
   *   if there are indeed tokens.
   */
  protected function processTokens(array $output, $clear, Message $message) {
    $options = [
      'clear' => $clear,
    ];

    $bubbleable_metadata = new BubbleableMetadata();
    foreach ($output as $key => $value) {
      if (is_string($value)) {
        $output[$key] = $this->token
          ->replace($value, ['message' => $message], $options, $bubbleable_metadata);
      }
      else {
        if (isset($value['value'])) {
          $output[$key] = $this->token
            ->replace($value['value'], ['message' => $message], $options, $bubbleable_metadata);
        }
      }
    }
    $bubbleable_metadata->applyTo($output);

    return $output;
  }

}
