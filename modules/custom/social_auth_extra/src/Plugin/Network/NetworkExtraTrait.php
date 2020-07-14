<?php

namespace Drupal\social_auth_extra\Plugin\Network;

/**
 * Trait NetworkExtraTrait.
 *
 * @package Drupal\social_auth_extra\Plugin\Network
 */
trait NetworkExtraTrait {

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->settings->isActive();
  }

}
