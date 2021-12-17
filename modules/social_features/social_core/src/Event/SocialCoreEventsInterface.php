<?php

namespace Drupal\social_core\Event;

/**
 * Defines events for the Open Social.
 */
final class SocialCoreEventsInterface {

  /**
   * Name of the event fired after an entity become published.
   *
   * @Event
   *
   * @see \Drupal\social_core\Event\EntityPublishedEvent
   *
   * @var string
   */
  const PUBLISHED = 'social_core.entity.published';

}
