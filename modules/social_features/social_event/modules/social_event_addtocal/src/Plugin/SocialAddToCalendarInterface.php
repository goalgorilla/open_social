<?php

namespace Drupal\social_event_addtocal\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\node\NodeInterface;

/**
 * Defines an interface for Social add to calendar plugins.
 */
interface SocialAddToCalendarInterface extends PluginInspectionInterface {

  /**
   * Returns plugin name for label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Plugin name.
   */
  public function getName();

  /**
   * Returns the 'Add to calendar' link.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  public function generateUrl(NodeInterface $node);

  /**
   * Returns array of event settings for url options.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   Array of settings.
   */
  public function generateSettings(NodeInterface $node);

  /**
   * Returns array of event dates for calendar.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return array
   *   Array of dates values.
   */
  public function getEventDates(NodeInterface $node);

  /**
   * Returns the event description for calendar.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   Event description.
   */
  public function getEventDescription(NodeInterface $node);

  /**
   * Returns the event location for calendar.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   *
   * @return string
   *   Event location.
   */
  public function getEventLocation(NodeInterface $node);

}
