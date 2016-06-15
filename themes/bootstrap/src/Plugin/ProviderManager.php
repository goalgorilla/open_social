<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\ProviderManager.
 */

namespace Drupal\bootstrap\Plugin;

use Drupal\bootstrap\Plugin\Provider\ProviderInterface;
use Drupal\bootstrap\Theme;

/**
 * Manages discovery and instantiation of Bootstrap CDN providers.
 */
class ProviderManager extends PluginManager {
  /**
   * The base file system path for CDN providers.
   *
   * @var string
   */
  const FILE_PATH = 'public://bootstrap/provider';

  /**
   * Constructs a new \Drupal\bootstrap\Plugin\ProviderManager object.
   *
   * @param \Drupal\bootstrap\Theme $theme
   *   The theme to use for discovery.
   */
  public function __construct(Theme $theme) {
    parent::__construct($theme, 'Plugin/Provider', 'Drupal\bootstrap\Plugin\Provider\ProviderInterface', 'Drupal\bootstrap\Annotation\BootstrapProvider');
    $this->setCacheBackend(\Drupal::cache('discovery'), 'theme:' . $theme->getName() . ':provider', $this->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    /** @var ProviderInterface $provider */
    $provider = new $definition['class'](['theme' => $this->theme], $plugin_id, $definition);
    $provider->processDefinition($definition, $plugin_id);
  }

}
