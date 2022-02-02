<?php

namespace Drupal\social_event_addtocal\Plugin\SocialAddToCalendar;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\social_event_addtocal\Plugin\SocialAddToCalendarBase;

/**
 * Provides add to iCal calendar plugin.
 *
 * @SocialAddToCalendar(
 *   id = "ical",
 *   label = @Translation("iCal"),
 *   url = "social_event_addtocal.add_to_calendar_ics",
 *   dateFormat = "e:Ymd\THis",
 *   utcDateFormat = "e:Ymd\THis\Z"
 * )
 */
class AddToICal extends SocialAddToCalendarBase {

  /**
   * {@inheritdoc}
   */
  public function generateUrl(NodeInterface $node) {
    $settings = $this->generateSettings($node);
    $options = [
      'query' => $settings,
      'attributes' => [
        'target' => '_blank',
      ],
    ];

    return Url::fromRoute($this->pluginDefinition['url'], [], $options);
  }

}
