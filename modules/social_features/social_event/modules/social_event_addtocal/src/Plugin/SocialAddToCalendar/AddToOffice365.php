<?php

namespace Drupal\social_event_addtocal\Plugin\SocialAddToCalendar;

/**
 * Provides add to Office 365 calendar plugin.
 *
 * @SocialAddToCalendar(
 *   id = "office_365",
 *   label = @Translation("Office 365"),
 *   url = "https://outlook.office.com/calendar/0/deeplink/compose",
 *   allDayFormat = "Y-m-d",
 *   dateFormat = "Y-m-d\TH:i:s",
 *   utcDateFormat = "Y-m-d\TH:i:s\Z"
 * )
 */
class AddToOffice365 extends AddToOutlook {

}
