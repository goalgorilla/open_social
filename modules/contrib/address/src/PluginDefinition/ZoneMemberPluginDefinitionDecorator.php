<?php

namespace Drupal\address\PluginDefinition;

use Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator;

/**
 * Provides a zone member plugin definition decorator.
 */
class ZoneMemberPluginDefinitionDecorator extends ArrayPluginDefinitionDecorator {

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->arrayDefinition['name'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->arrayDefinition['name']) ? $this->arrayDefinition['name'] : NULL;
  }

}
