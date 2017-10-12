<?php

namespace Drupal\activity_send_email;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Common interface for all email frequencies.
 *
 */
interface EmailFrequencyInterface extends PluginInspectionInterface {

  /**
   * Return the name of the email frequency.
   *
   * @return string
   */
  public function getName();

  /**
   * Return the weight of the frequency option.
   *
   * @return integer
   */
  public function getWeight();

}
