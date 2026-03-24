<?php

namespace Drupal\social_event_addtocal\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\social_event_addtocal\Plugin\SocialAddToCalendarBase;

/**
 * Defines a Social add to calendar item annotation object.
 *
 * @see \Drupal\social_event_addtocal\Plugin\SocialAddToCalendarManager
 * @see plugin_api
 *
 * @Annotation
 */
class SocialAddToCalendar extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * Used in administrative UIs (e.g. allowed calendars on the settings form).
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Short label shown to end users on the site (optional).
   *
   * When set, event add-to-calendar links use this instead of label.
   *
   * @var \Drupal\Core\Annotation\Translation|null
   *
   * @ingroup plugin_translatable
   */
  public $publicLabel;

  /**
   * The url for adding to calendar.
   *
   * @var string
   */
  public $url;

  /**
   * The date modifications for all day events.
   *
   * @var string
   */
  public $endDateModification = SocialAddToCalendarBase::END_DATE_MODIFICATION_DEFAULT_VALUE;

  /**
   * Date format for all day event.
   *
   * @var string
   */
  public $allDayFormat = SocialAddToCalendarBase::ALL_DAY_FORMAT_DEFAULT_VALUE;

  /**
   * Date format.
   *
   * @var string
   */
  public $dateFormat = SocialAddToCalendarBase::DATE_FORMAT_DEFAULT_VALUE;

  /**
   * Date format if users timezone is UTC.
   *
   * @var string
   */
  public $utcDateFormat = SocialAddToCalendarBase::UTC_DATE_FORMAT_DEFAULT_VALUE;

}
