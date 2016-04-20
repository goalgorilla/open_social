<?php

namespace Drupal\config_update;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event context class for configuration revert/import events.
 *
 * This class is passed in as the event when the
 * \Drupal\config_update\ConfigRevertInterface::IMPORT,
 * \Drupal\config_update\ConfigDeleteInterface::DELETE, and
 * \Drupal\config_update\ConfigRevertInterface::REVERT events are triggered.
 */
class ConfigRevertEvent extends Event {

  /**
   * The type of configuration that is being imported or reverted.
   *
   * @var string
   */
  protected $type;

  /**
   * The name of the config item being imported or reverted, without prefix.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructs a new ConfigRevertEvent.
   *
   * @param string $type
   *   The type of configuration being imported or reverted.
   * @param string $name
   *   The name of the config item being imported/reverted, without prefix.
   */
  public function __construct($type, $name) {
    $this->type = $type;
    $this->name = $name;
  }

  /**
   * Returns the type of configuration being imported or reverted.
   *
   * @return string
   *   The type of configuration, either 'system.simple' or a config entity
   *   type machine name.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns the name of the config item, without prefix.
   *
   * @return string
   *   The name of the config item being imported/reverted/deleted, with the
   *   prefix.
   */
  public function getName() {
    return $this->name;
  }

}
