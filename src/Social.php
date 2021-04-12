<?php

namespace Drupal\social;

/**
 * Static Open Social metadata.
 */
class Social {

  /**
   * The current system version.
   */
  public const VERSION = '10.0.0';

  /**
   * The count of service container changes.
   *
   * This can be used in the deployment_identifier to ensure the services
   * container is properly invalidated before updates.
   *
   * ```
   * $settings['deployment_identifier'] = \Drupal::VERSION . "-" . Social::VERSION . "-" . Social::CONTAINER_COUNTER;
   * ```
   */
  public const CONTAINER_COUNTER = '1';

}
