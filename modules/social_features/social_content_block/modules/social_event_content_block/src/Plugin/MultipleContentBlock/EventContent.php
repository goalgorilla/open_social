<?php

namespace Drupal\social_event_content_block\Plugin\MultipleContentBlock;

use Drupal\social_content_block\MultipleContentBlockBase;

/**
 * Provides a content block for events.
 *
 * @MultipleContentBlock(
 *   id = "event_content",
 *   label = @Translation("Event"),
 *   entity_type = "node",
 *   bundle = "event"
 * )
 */
class EventContent extends MultipleContentBlockBase {

}
