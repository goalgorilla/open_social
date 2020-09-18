<?php

namespace Drupal\social_event_addtocal\Plugin\SocialAddToCalendar;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event_addtocal\Plugin\SocialAddToCalendarBase;

/**
 * Provides add to Google calendar plugin.
 *
 * @SocialAddToCalendar(
 *   id = "yahoo",
 *   label = @Translation("Yahoo"),
 *   url = "http://calendar.yahoo.com"
 * )
 */
class AddToYahoo extends SocialAddToCalendarBase {

  /**
   * {@inheritdoc}
   */
  public function generateUrl(NodeInterface $node) {
    $settings = $this->generateSettings($node);
    $options = [
      'query' => [
        'v' => 60,
        'TITLE' => $settings['title'],
        'ST' => $settings['dates']['start'],
        'ET' => $settings['dates']['end'],
        'desc' => $settings['description'],
        'in_loc' => $settings['location'],
      ],
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    return Url::fromUri($this->pluginDefinition['url'], $options);
  }

}
