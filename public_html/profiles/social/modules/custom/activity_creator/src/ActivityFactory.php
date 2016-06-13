<?php
/**
 * ActivityFactory
 */

namespace Drupal\activity_creator;

use Drupal\activity_creator\Entity\Activity;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManager;
use Drupal\message\Entity\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @return mixed
   */
  public function createActivities(array $data) {
    $activities = $this->buildActivities($data);

    return $activities;
  }

  /**
   * Build the activities based on a data array.
   *
   * @param array $data
   * @return mixed
   */
  private function buildActivities(array $data) {
    $activities = [];

    // For every insert we create an activity item.
    $activity = Activity::create([
      'field_activity_destinations' => $this->getFieldDestinations($data),
      'field_activity_entity' =>  $this->getFieldEntity($data),
      'field_activity_message' => $this->getFieldMessage($data),
      'field_activity_output_text' =>  $this->getFieldOutputText($data),
      'field_activity_recipient_group' => $this->getFieldRecipientGroup($data),
      'field_activity_recipient_user' => $this->getFieldRecipientUser($data),
      'user_id' => $this->getActor($data),
    ]);
    $activity->save();

    return $activities;
  }

  private function getFieldDestinations(array $data) {
    $value = NULL;
    if (isset($data['destination'])) {
      $value = $data['destination'];
    }
    return $value;
  }

  private function getFieldEntity($data) {
    $value = NULL;
    if (isset($data['related_object'])) {
      $value = $data['related_object'];
    }
    return $value;
  }

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

  private function getFieldOutputText($data) {
    $value = NULL;
    if (isset($data['mid'])) {

      $message = Message::load($data['mid']);
      // @TODO setArguments here? Replace tokens here? Or let message do work?
      $value = $message->getText(NULL);
    }

    return $value;
  }

  private function getFieldRecipientGroup($data) {
    // @TODO create logic here, based on recipients in data array.
    $value = NULL;
    if (isset($data['recipient'])) {
      if ($data['recipient']['target_type'] === 'group') {
        // Should be in an array for the field.
        $value = array($data['recipient']);
      }
    }
    return $value;
  }

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
   * @param $data
   * @return int
   */
  private function getActor($data) {
    $value = 0;
    if (isset($data['actor'])) {
      $value = $data['actor'];
    }
    return $value;
  }

}