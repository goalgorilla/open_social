<?php

namespace Drupal\social_demo\Plugin\DemoContent;

use Drupal\social_demo\DemoTaxonomyTerm;
use Drush\Log\LogLevel;

/**
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
  public function createContent() {
    // Check if event types are enabled.
    if (!\Drupal::moduleHandler()->moduleExists('social_event_type')) {
      return;
    }

    return parent::createContent();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    // Check if event types are enabled.
    if (!\Drupal::moduleHandler()->moduleExists('social_event_type')) {
      drush_log(dt('The social event type module is not enabled.'), LogLevel::WARNING);
      return FALSE;
    }

    return parent::count();
  }

}
