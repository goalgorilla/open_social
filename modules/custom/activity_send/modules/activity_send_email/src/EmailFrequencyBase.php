<?php

namespace Drupal\activity_send_email;

use Drupal\Component\Plugin\PluginBase;

/**
 * Class EmailFrequencyBase.
 *
 * Implements common functions for all EmailFrequency classes.
 */
class EmailFrequencyBase extends PluginBase implements EmailFrequencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

}
