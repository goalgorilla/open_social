<?php

namespace Drupal\config_update;

/**
 * Defines an interface for deleting config items.
 */
interface ConfigDeleteInterface {

  /**
   * Name of the event triggered on configuration delete.
   *
   * @see \Drupal\config_update\ConfigRevertEvent
   * @see \Drupal\config_update\ConfigDeleteInterface::delete()
   */
  const DELETE = 'config_update.delete';

  /**
   * Deletes a configuration item.
   *
   * This action triggers a ConfigDeleteInterface::DELETE event.
   *
   * @param string $type
   *   The type of configuration.
   * @param string $name
   *   The name of the config item, without the prefix.
   *
   * @return bool
   *   TRUE if the operation succeeded; FALSE if the base configuration could
   *   not be found to delete. May also throw exceptions if there is a
   *   problem during deleting the configuration.
   *
   * @see \Drupal\config_update\ConfigDeleteInterface::DELETE
   */
  public function delete($type, $name);

}
