<?php

namespace Drupal\activity_send_email;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class EmailFrequencyManager.
 */
class EmailFrequencyManager extends DefaultPluginManager {

  /**
   * Email frequency daily.
   *
   * Emails will be sent once a day.
   */
  const FREQUENCY_DAILY = 'daily';

  /**
   * Email frequency immediately.
   *
   * Emails will be sent immediately on cron run.
   */
  const FREQUENCY_IMMEDIATELY = 'immediately';

  /**
   * Email frequency none.
   *
   * No emails will be sent.
   */
  const FREQUENCY_NONE = 'none';

  /**
   * Email frequency weekly.
   *
   * Emails will be sent as weekly digest.
   */
  const FREQUENCY_WEEKLY = 'weekly';

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/EmailFrequency',
      $namespaces,
      $module_handler,
      'Drupal\activity_send_email\EmailFrequencyInterface',
      'Drupal\activity_send_email\Annotation\EmailFrequency'
    );

    $this->alterInfo('emailfrequency_info');
    $this->setCacheBackend($cache_backend, 'emailfrequency');

  }

}
