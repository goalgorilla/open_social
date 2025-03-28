<?php

namespace Drupal\social_event_addtocal\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Social add to calendar plugins.
 */
abstract class SocialAddToCalendarBase extends PluginBase implements SocialAddToCalendarInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * Default date modifications for all day events.
   */
  const END_DATE_MODIFICATION_DEFAULT_VALUE = '+ 1 day';

  /**
   * Default date format for all day event.
   */
  const ALL_DAY_FORMAT_DEFAULT_VALUE = 'Ymd';

  /**
   * Default date format.
   */
  const DATE_FORMAT_DEFAULT_VALUE = 'Ymd\THis';

  /**
   * Default date format if users timezone is UTC.
   */
  const UTC_DATE_FORMAT_DEFAULT_VALUE = 'Ymd\THis\Z';

  /**
   * The module extension list.
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Constructs a SocialAddToCalendarBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleExtensionList $extension_list_module) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['label'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): string {
    $module_path = $this->moduleExtensionList
      ->getPath('social_event_addtocal');

    $plugin_icon = empty($this->pluginDefinition['id'])
      ? '/assets/icons/default-calendar.webp'
      : '/assets/icons/' . $this->pluginDefinition['id'] . '.webp';

    return base_path() . $module_path . $plugin_icon;
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
      'timezone' => date_default_timezone_get(),
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
    $all_day = !$node->get('field_event_all_day')->isEmpty() && $node->get('field_event_all_day')->getString() === '1';
    $start_date = new \DateTime($node->field_event_date->value, new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $end_date = new \DateTime($node->field_event_date_end->value, new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $date_time = [];

    // Set formats for event dates.
    $format = $this->pluginDefinition['dateFormat'] ?? self::DATE_FORMAT_DEFAULT_VALUE;
    if (date_default_timezone_get() === DateTimeItemInterface::STORAGE_TIMEZONE) {
      $format = $this->pluginDefinition['utcDateFormat'] ?? self::UTC_DATE_FORMAT_DEFAULT_VALUE;
    }
    $all_day_format = $this->pluginDefinition['allDayFormat'] ?? self::ALL_DAY_FORMAT_DEFAULT_VALUE;

    // Convert date to correct format.
    // Set dates array.
    if ($all_day) {
      $date_time['start'] = $start_date->format($all_day_format);
      $end_date->modify($this->pluginDefinition['endDateModification'] ?? self::END_DATE_MODIFICATION_DEFAULT_VALUE);
      $date_time['end'] = $end_date->format($all_day_format);
    }
    else {
      $start_date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      $end_date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
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
    // Get event URL.
    // It is impossible to generate canonical absolute URL for an entity without
    // ID - it will trigger EntityMalformedException. This could happen when
    // previewing the node, in that case we don't have to render a description.
    try {
      $description = $this->t('See the event page for details: @link', ['@link' => $node->toUrl('canonical', ['absolute' => TRUE])->toString()]);

      // Update event description with adding event link.
      return Unicode::truncate(strip_tags($description), 1000, TRUE, TRUE);
    }
    catch (EntityMalformedException) {
      return '';
    }

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
