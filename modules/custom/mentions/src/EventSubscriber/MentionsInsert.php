<?php

/**
 * @file
 * Event Handler when a mention is inserted.
 */

namespace Drupal\mentions\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * MentionsInsert handles event 'mentions.insert'.
 */
class MentionsInsert implements EventSubscriberInterface {
  /**
   * @{inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['mentions.insert'][] = array('onMentionsInsert', 0);
    return $events;
  }

  /**
   * Event Handler.
   */
  public function onMentionsInsert($event) {
    $config = \Drupal::config('mentions.mentions');
    $config_mentions_events = $config->get('mentions_events');
    $action_id = $config_mentions_events['insert'];
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
