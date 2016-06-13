<?php

namespace Drupal\activity_logger\Service;
/**
 * Class ActivityLoggerFactory
 * @package Drupal\activity_logger\Service
 * Service that determines which actions need to be performed.
 */
class ActivityLoggerFactory {

  /**
   * @param $action
   * @param $context
   * @return array
   */
  public function getMessageTypes($action, $context, $bundle) {
    // Init.
    $messagetypes = array();

    // We need the entitytype manager.
    $entity_type_manager = \Drupal::service('entity_type.manager');
    // Message type storage.
    $message_storage = $entity_type_manager->getStorage('message_type');

    // Check all enabled messages.
    foreach($message_storage->loadByProperties(array('status' => '1')) as $key => $messagetype) {
      // Messagetype must be a part of the context the items is in.
      if ($messagetype->getThirdPartySetting('activity_logger', 'activity_context', NULL) === $context) {
        // @TODO: Make this configurable.
        $messagetype_action = explode('_', $key)[0];
        $bundletype = explode('_', $key)[1];
        // Determine the action types to return.
        if ($action === $messagetype_action && $bundle === $bundletype) {
          $messagetypes[$key] = array(
            'messagetype' => $messagetype,
            'bundle' => $bundletype,
            'destinations' => $messagetype->getThirdPartySetting('activity_logger', 'activity_destinations', NULL),
          );
        }
      }
    }
    // Return the message types that belong to the requested action.
    return $messagetypes;
  }
}
