<?php

/**
 * @file
 * Definition of Drupal\message\Tests\MessageTestBase.
 */

namespace Drupal\message\Tests;

use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\message\Entity\MessageType;
use Drupal\simpletest\WebTestBase;

/**
 * Holds set of tools for the message testing.
 */
abstract class MessageTestBase extends WebTestBase {

  use MessageTypeCreateTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['message', 'views'];

  /**
   * The node access controller.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessController;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Load a message type easily.
   *
   * @param string $type
   *   The type of the message.
   *
   * @return \Drupal\message\Entity\MessageType
   *   The message Object.
   */
  protected function loadMessageType($type) {
    return MessageType::load($type);
  }

  /**
   * Return a config setting.
   *
   * @param string $config
   *   The config value.
   * @param string $storage
   *   The storing of the configuration. Default to message.message.
   *
   * @return mixed
   *   The value of the config.
   */
  protected function getConfig($config, $storage = 'message.settings') {
    return \Drupal::config($storage)->get($config);
  }

  /**
   * Set a config value.
   *
   * @param string $config
   *   The config name.
   * @param string $value
   *   The config value.
   * @param string $storage
   *   The storing of the configuration. Default to message.message.
   */
  protected function configSet($config, $value, $storage = 'message.settings') {
    \Drupal::configFactory()->getEditable($storage)->set($config, $value);
  }
}
