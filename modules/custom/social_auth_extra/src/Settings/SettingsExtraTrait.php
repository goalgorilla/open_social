<?php

namespace Drupal\social_auth_extra\Settings;

/**
 * Trait SettingsExtraTrait.
 *
 * @package Drupal\social_auth_extra\Settings
 */
trait SettingsExtraTrait {

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->config->get('status');
  }

}
