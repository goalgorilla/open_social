<?php

namespace Drupal\activity_send_email;

use Drupal\Component\Plugin\PluginBase;

class EmailFrequencyBase extends PluginBase implements EmailFrequencyInterface {

  public function getName() {
    return $this->pluginDefinition['name'];
  }

  public function getInterval() {
    return $this->pluginDefinition['interval'];
  }
}
