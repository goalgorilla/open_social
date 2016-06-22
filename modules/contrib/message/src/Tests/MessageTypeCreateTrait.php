<?php
/**
 * @file
 * Contains \Drupal\message\Tests\MessageTypeCreateTrait.
 */

namespace Drupal\message\Tests;

use Drupal\Core\Language\Language;
use Drupal\message\Entity\MessageType;
use Drupal\message\MessageTypeInterface;

/**
 * Trait to assist message type creation for tests.
 */
trait MessageTypeCreateTrait {

  /**
   * Helper function to create and save a message type entity.
   *
   * @param string $type
   *   The message type.
   * @param string $label
   *   The message type label.
   * @param string $description
   *   The message type description.
   * @param array $text
   *   The text array for the message type.
   * @param array $settings
   *   Data overrides.
   * @param string $langcode
   *   The language to use.
   *
   * @return \Drupal\message\MessageTypeInterface
   *   A saved message type entity.
   */
  protected function createMessageType($type, $label, $description, array $text, array $settings = [], $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $settings += [
      'token options' => [
        'clear' => FALSE,
      ],
    ];
    $message_type = MessageType::Create([
      'type' => $type,
      'label' => $label,
      'description' => $description,
      'text' => $text,
      'settings' => $settings,
      'langcode' => $langcode,
    ]);
    $message_type->save();

    return $message_type;
  }

}
