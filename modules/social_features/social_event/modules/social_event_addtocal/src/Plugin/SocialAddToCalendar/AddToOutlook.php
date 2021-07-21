<?php

namespace Drupal\social_event_addtocal\Plugin\SocialAddToCalendar;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event_addtocal\Plugin\SocialAddToCalendarBase;

/**
 * Provides add to Outlook calendar plugin.
 *
 * @SocialAddToCalendar(
 *   id = "outlook",
 *   label = @Translation("Outlook"),
 *   url = "https://outlook.live.com/calendar/0/deeplink/compose",
 *   allDayFormat = "Y-m-d",
 *   dateFormat = "Y-m-d\TH:i:s",
 *   utcDateFormat = "Y-m-d\TH:i:s\Z"
 * )
 */
class AddToOutlook extends SocialAddToCalendarBase {

  /**
   * {@inheritdoc}
   */
  public function generateUrl(NodeInterface $node) {
    $settings = $this->generateSettings($node);
    $options = [
      'query' => [
        'path' => '/calendar/action/compose',
        'rru' => 'addevent',
        'subject' => $settings['title'],
        'startdt' => $settings['dates']['start'],
        'enddt' => $settings['dates']['end'],
        'allday' => $settings['dates']['all_day'] ? 'true' : 'false',
        'body' => $settings['description'],
        'location' => $settings['location'],
      ],
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    return Url::fromUri($this->pluginDefinition['url'], $options);
  }

}
