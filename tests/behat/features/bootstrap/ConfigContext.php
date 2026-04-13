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

  /**
   * Clear config cache in behat.
   *
   * We sometimes make config changes in the Drupal site which may affect steps
   * that we take in the Behat context (e.g. change the search index
   * configuration). If we have cached config in our Behat side of things then
   * this can be break steps in fun and unexpected ways.
   *
   * @Given clear the config cache in Behat
   */
  public function clearSearchConfigCacheInBehat() : void {
    \Drupal::configFactory()->reset();
  }

}
