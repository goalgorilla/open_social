<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoTaxonomyTerm;

/**
 * EventType Plugin for demo content.
 *
 * @DemoContent(
 *   id = "event_type",
 *   label = @Translation("Event type"),
 *   source = "content/entity/event-type.yml",
 *   entity_type = "taxonomy_term"
 * )
 */
class EventType extends DemoTaxonomyTerm {

  /**
   * {@inheritdoc}
   */
  public function createContent($generate = FALSE, $max = NULL) {
    // Check if event types are enabled.
    if (!\Drupal::moduleHandler()->moduleExists('social_event_type')) {
      return;
    }

    return parent::createContent($generate, $max);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    // Check if event types are enabled.
    if (!\Drupal::moduleHandler()->moduleExists('social_event_type')) {
      $this->loggerChannelFactory->get('social_demo')->warning('The social event type module is not enabled.');
      return FALSE;
    }

    return parent::count();
  }

}
