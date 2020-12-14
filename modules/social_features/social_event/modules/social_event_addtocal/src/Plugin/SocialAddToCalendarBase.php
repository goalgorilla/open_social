<?php

namespace Drupal\social_event_addtocal\Plugin;

use DateTime;
use DateTimeZone;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;

/**
 * Base class for Social add to calendar plugins.
 */
abstract class SocialAddToCalendarBase extends PluginBase implements SocialAddToCalendarInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function generateUrl(NodeInterface $node) {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function generateSettings(NodeInterface $node) {
    return [
      'title' => $node->getTitle(),
      'dates' => $this->getEventDates($node),
      'timezone' => date_default_timezone_get() !== DateTimeItemInterface::STORAGE_TIMEZONE ? date_default_timezone_get() : '',
      'description' => $this->getEventDescription($node),
      'location' => $this->getEventLocation($node),
      'nid' => $node->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEventDates(NodeInterface $node) {
    // Set default values.
    $all_day = FALSE;
    $start_date = new DateTime($node->field_event_date->value, new DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $end_date = new DateTime($node->field_event_date_end->value, new DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $date_time = [];

    // Set formats for event dates.
    $format = $this->pluginDefinition['dateFormat'];
    if (date_default_timezone_get() === DateTimeItemInterface::STORAGE_TIMEZONE) {
      $format = $this->pluginDefinition['utcDateFormat'];
    }
    $all_day_format = $this->pluginDefinition['allDayFormat'];

    // Check if all day event.
    if ($start_date->format('i') === '01') {
      $all_day = TRUE;
    }

    // Convert date to correct format.
    // Set dates array.
    if ($all_day) {
      $date_time['start'] = $start_date->format($all_day_format);
      $end_date->modify($this->pluginDefinition['endDateModification']);
      $date_time['end'] = $end_date->format($all_day_format);
    }
    else {
      $start_date->setTimezone(new DateTimeZone(date_default_timezone_get()));
      $end_date->setTimezone(new DateTimeZone(date_default_timezone_get()));
      $date_time['start'] = $start_date->format($format);
      $date_time['end'] = $end_date->format($format);
    }

    // Set external values for dates.
    $date_time['both'] = $date_time['start'] . '/' . $date_time['end'];
    $date_time['all_day'] = $all_day;

    return $date_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventDescription(NodeInterface $node) {
    // Get event description.
    $description = $node->body->value;

    // Strings for replace.
    $replace_strings = [
      '&nbsp;' => '',
      '<br />' => '',
      PHP_EOL => '',
    ];

    // Replace node supported strings.
    foreach ($replace_strings as $search => $replace) {
      $description = str_replace($search, $replace, $description);
    }

    return Unicode::truncate(strip_tags($description), 1000, TRUE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getEventLocation(NodeInterface $node) {
    // Get event address values.
    if ($node->get('field_event_address')->isEmpty()) {
      return '';
    }
    $address_value = $node->field_event_address->getValue();
    $address = $address_value[0];
    $location = '';

    // Set event location.
    if (!empty($address['address_line1'])) {
      $location .= $address['address_line1'] . ' ';
    }
    if (!empty($address['address_line2'])) {
      $location .= $address['address_line2'] . ', ';
    }
    if (!empty($address['locality'])) {
      $location .= $address['locality'] . ', ';
    }
    if (!empty($address['administrative_area'])) {
      $location .= $address['administrative_area'] . ' ';
    }
    if (!empty($address['postal_code'])) {
      $location .= $address['postal_code'] . ', ';
    }
    if (!empty($address['country_code'])) {
      $location .= $address['country_code'];
    }

    return $location;
  }

}
