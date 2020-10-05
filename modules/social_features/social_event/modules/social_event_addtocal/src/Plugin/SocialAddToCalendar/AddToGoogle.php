<?php

namespace Drupal\social_event_addtocal\Plugin\SocialAddToCalendar;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event_addtocal\Plugin\SocialAddToCalendarBase;

/**
 * Provides add to Google calendar plugin.
 *
 * @SocialAddToCalendar(
 *   id = "google",
 *   label = @Translation("Google"),
 *   url = "http://www.google.com/calendar/event"
 * )
 */
class AddToGoogle extends SocialAddToCalendarBase {

  /**
   * {@inheritdoc}
   */
  public function generateUrl(NodeInterface $node) {
    $settings = $this->generateSettings($node);
    $options = [
      'query' => [
        'action' => 'TEMPLATE',
        'text' => $settings['title'],
        'dates' => $settings['dates']['both'],
        'ctz' => $settings['timezone'],
        'details' => $settings['description'],
        'location' => $settings['location'],
      ],
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    return Url::fromUri($this->pluginDefinition['url'], $options);
  }

}
