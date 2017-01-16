<?php

/**
 * @file
 * Event Handler when a mention is updated.
 */

namespace Drupal\mentions\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MentionsUpdate handles event 'mentions.update'.
 */
class MentionsUpdate implements EventSubscriberInterface {
  /**
   * @{inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = array();
    $events['mentions.update'][] = array('onMentionsUpdate', 0);
    return $events;
  }

  public function onMentionsUpdate($event) {
    $config = \Drupal::config('mentions.mentions');
    $config_mentions_events = $config->get('mentions_events');
    $action_id = $config_mentions_events['update'];
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
