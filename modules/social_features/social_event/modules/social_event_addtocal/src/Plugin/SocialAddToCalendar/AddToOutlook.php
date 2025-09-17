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

  protected const string URL = 'https://outlook.live.com/calendar/0/deeplink/compose';

  /**
   * {@inheritdoc}
   */
  public function generateUrl(NodeInterface $node): Url {
    $settings = $this->generateSettings($node);

    $query_params = [
      'subject' => $settings['title'],
      'startdt' => $settings['dates']['start'],
      'enddt' => $settings['dates']['end'],
      'allday' => $settings['dates']['all_day'] ? 'true' : 'false',
      'body' => $settings['description'] ?? '',
      'location' => $settings['location'] ?? '',
    ];

    $options = [
      'query' => $query_params,
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    $url = self::URL;
    if (!empty($this->pluginDefinition['url'])) {
      $url = $this->pluginDefinition['url'];
    }

    return Url::fromUri($url, $options);
  }

}
