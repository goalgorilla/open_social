<?php

/**
 * @file
 * Event Handler when a mention is deleted.
 */

namespace Drupal\mentions\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MentionsDelete handles event 'mentions.delete'.
 */
class MentionsDelete implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = array();
    $events['mentions.delete'][] = array('onMentionsDelete', 0);
    return $events;
  }

  /**
   * Event handler.
   */
  public function onMentionsDelete($event) {
    $config = \Drupal::config('mentions.mentions');
    $config_mentions_events = $config->get('mentions_events');
    $action_id = $config_mentions_events['delete'];
    if (empty($action_id)) {
      return;
    }
    $entity_storage = \Drupal::entityManager()->getStorage('action');
    $action = $entity_storage->load($action_id);
    $action_plugin = $action->getPlugin();
    if (!empty($action_id)) {
      $action_plugin->execute(FALSE);
    }
  }

}
