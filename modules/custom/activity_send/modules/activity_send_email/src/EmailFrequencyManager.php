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
