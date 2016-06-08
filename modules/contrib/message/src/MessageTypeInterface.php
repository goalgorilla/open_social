<?php

/**
 * @file
 * Contains \Drupal\message\MessageTypeInterface.
 */

namespace Drupal\message;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\Language;

/**
 * Provides an interface defining a Message type entity.
 */
interface MessageTypeInterface extends ConfigEntityInterface {


  /**
   * Set the message type description.
   *
   * @param string $description
   *   Description for the message type.
   *
   * @return \Drupal\message\MessageTypeInterface
   *   Returns the message type instance.
   */
  public function setDescription($description);

  /**
   * Get the message type description.
   *
   * @return string
   *   Returns the message type description.
   */
  public function getDescription();

  /**
   * Set the message type label.
   *
   * @param string $label
   *   The message type label.
   *
   * @return \Drupal\message\MessageTypeInterface
   *   Returns the message type instance.
   */
  public function setLabel($label);

  /**
   * Get the message type label.
   *
   * @return string
   *   Returns the message type label.
   */
  public function getLabel();

  /**
   * Set the message type.
   *
   * @param string $type
   *   The message type.
   *
   * @return \Drupal\message\MessageTypeInterface
   *   Returns the message type instance.
   */
  public function setType($type);

  /**
   * Get the message type.
   *
   * @return string
   *   Returns the message type.
   */
  public function getType();

  /**
   * Set the UUID.
   *
   * @param string $uuid
   *   The UUID.
   *
   * @return \Drupal\message\MessageTypeInterface
   *   Returns the message type instance.
   */
  public function setUuid($uuid);

  /**
   * Get the UUID.
   *
   * @return string
   *   Returns the UUID.
   */
  public function getUuid();

  /**
   * Retrieves the configured message text in a certain language.
   *
   * @param string $langcode
   *   The language code of the Message text field, the text should be
   *   extracted from.
   * @param int $delta
   *   Optional; Represents the partial number. If not provided - all partials
   *   will be returned.
   *
   * @return array
   *   An array of the text field values.
   */
  public function getText($langcode = Language::LANGCODE_NOT_SPECIFIED, $delta = NULL);

  /**
   * Set additional settings for the message type.
   */
  public function setSettings(array $settings);

  /**
   * Return the message type settings.
   *
   * @return array
   *   Array of the message type settings.
   */
  public function getSettings();

  /**
   * Return a single setting by key.
   *
   * @param string $key
   *   The key to return.
   * @param mixed $default_value
   *   The default value to use in case the key is missing. Defaults to NULL.
   *
   * @return mixed
   *   The value of the setting or the default value if none found.
   */
  public function getSetting($key, $default_value = NULL);

  /**
   * Check if the message is new.
   *
   * @return bool
   *   Returns TRUE is the message is new.
   */
  public function isLocked();

}
