<?php

namespace Drupal\social_event_addtocal\Plugin\SocialAddToCalendar;

/**
 * Provides add to Outlook (work) calendar plugin.
 *
 * @SocialAddToCalendar(
 *   id = "office_365",
 *   label = @Translation("Outlook Professional"),
 *   publicLabel = @Translation("Outlook Pro"),
 *   url = "https://outlook.office.com/calendar/0/deeplink/compose",
 *   allDayFormat = "Y-m-d",
 *   dateFormat = "Y-m-d\TH:i:s",
 *   utcDateFormat = "Y-m-d\TH:i:s\Z"
 * )
 */
class AddToOffice365 extends AddToOutlook {

}
