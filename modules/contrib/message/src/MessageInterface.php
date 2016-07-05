<?php

/**
 * @file
 * Contains \Drupal\message\MessageInterface.
 */

namespace Drupal\message;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\Language;

/**
 * Provides an interface defining a Message entity.
 */
interface MessageInterface extends ContentEntityInterface {

  /**
   * Set the message type.
   *
   * @param MessageTypeInterface $type
   *   Message type.
   *
   * @return \Drupal\message\MessageInterface
   *   Returns the message object.
   */
  public function setType(MessageTypeInterface $type);

  /**
   * Get the type of the message type.
   *
   * @return \Drupal\message\MessageTypeInterface
   *   Returns the message object.
   */
  public function getType();

  /**
   * Retrieve the time stamp of the message.
   *
   * @return int
   *   The Unix timestamp.
   */
  public function getCreatedTime();

  /**
   * Setting the timestamp.
   *
   * @param int $timestamp
   *   The Unix timestamp.
   *
   * @return \Drupal\message\MessageInterface
   *   Returns the message object.
   */
  public function setCreatedTime($timestamp);

  /**
   * Return the UUID.
   *
   * @return string
   *   Return the UUID.
   */
  public function getUUID();

  /**
   * Retrieve the message arguments.
   *
   * @return array
   *   The arguments of the message.
   */
  public function getArguments();

  /**
   * Set the arguments of the message.
   *
   * @param array $values
   *   Array of arguments.
   *
   * @code
   *   $values = [
   *     '@name_without_callback' => 'John doe',
   *     '@name_with_callback' => [
   *       'callback' => 'User::load',
   *       'arguments' => [1],
   *     ],
   *   ];
   * @endcode
   *
   * @return \Drupal\message\MessageInterface
   *   Returns the message object.
   */
  public function setArguments(array $values);

  /**
   * Set the language that should be used.
   *
   * @param string $language
   *   The language to load from the message type when fetching the text.
   */
  public function setLanguage($language);

  /**
   * Replace arguments with their placeholders.
   *
   * @param string $langcode
   *   The language code.
   * @param NULL|int $delta
   *   The delta of the message to return. If NULL all the message text will be
   *   returned.
   *
   * @return array
   *   The message text.
   */
  public function getText($langcode = Language::LANGCODE_NOT_SPECIFIED, $delta = NULL);

  /**
   * Delete multiple message.
   *
   * @param array $ids
   *   The messages IDs to delete.
   */
  public static function deleteMultiple(array $ids);

  /**
   * Run a EFQ over messages from a given type.
   *
   * @param string $type
   *   The entity type.
   *
   * @return array
   *   Array of message IDs.
   */
  public static function queryByType($type);

  /**
   * Convert message contents to a string.
   *
   * @return string
   *   The message contents.
   */
  public function __toString();

}
