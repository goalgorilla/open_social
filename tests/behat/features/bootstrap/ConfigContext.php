<?php

namespace Drupal\social\Behat;

use Drupal\DrupalExtension\Context\ConfigContext as BaseConfigContext;

/**
 * Disables cleanup in the underlying Drupal extension's ConfigContext.
 */
class ConfigContext extends BaseConfigContext {

  use AvoidCleanupTrait;

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanConfig() : void {}

}
