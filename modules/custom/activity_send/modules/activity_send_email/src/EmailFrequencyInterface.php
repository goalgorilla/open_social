<?php

namespace Drupal\activity_send_email;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Common interface for all EmailFrequencies.
 *
 */
interface EmailFrequencyInterface extends PluginInspectionInterface {

  /**
   * Return the name of the ice cream flavor.
   *
   * @return string
   */
  public function getName();

  /**
   * Returns the emailfrequency interval.
   *
   * @return integer
   */
  public function getInterval();
}
