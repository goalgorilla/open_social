<?php

namespace Drupal\social_event_addtocal\Annotation;

use Drupal\Component\Annotation\Plugin;

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
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

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
  public $endDateModification = '+ 1 day';

  /**
   * Date format for all day event.
   *
   * @var string
   */
  public $allDayFormat = 'Ymd';

  /**
   * Date format.
   *
   * @var string
   */
  public $dateFormat = 'Ymd\THis';

  /**
   * Date format if users timezone is UTC.
   *
   * @var string
   */
  public $utcDateFormat = 'Ymd\THis\Z';

}
