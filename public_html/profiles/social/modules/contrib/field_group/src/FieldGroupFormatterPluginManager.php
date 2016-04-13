<?php

/**
 * @file
 * Contains \Drupal\field_group\FieldgroupFormatterPluginManager.
 */

namespace Drupal\field_group;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin type manager for all fieldgroup formatters.
 */
class FieldGroupFormatterPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new FieldGroupFormatterPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/field_group/FieldGroupFormatter', $namespaces, $module_handler, 'Drupal\field_group\FieldGroupFormatterInterface', 'Drupal\field_group\Annotation\FieldGroupFormatter');

    $this->alterInfo('field_group_formatter_info');
    $this->setCacheBackend($cache_backend, 'field_group_formatter_info');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition);
    }

    return new $plugin_class($plugin_id, $plugin_definition, $configuration['group'], $configuration['settings'], $configuration['label']);
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - format_type: The current format type.
   *   - group: The current group.
   *   - prepare: (bool, optional) Whether default values should get merged in
   *     the 'configuration' array. Defaults to TRUE.
   *   - configuration: (array) the configuration for the formatter. The
   *     following key value pairs are allowed, and are all optional if
   *     'prepare' is TRUE:
   *     - label: (string) Position of the label. The default 'field' theme
   *       implementation supports the values 'inline', 'above' and 'hidden'.
   *       Defaults to 'above'.
   *     - settings: (array) Settings specific to the formatter. Each setting
   *       defaults to the default value specified in the formatter definition.
   *
   * @return \Drupal\field_group\FieldGroupFormatterInterface|null
   *   A formatter object or NULL when plugin is not found.
   */
  public function getInstance(array $options) {
    $configuration = $options['configuration'];
    $format_type = $options['format_type'];
    $context = $options['group']->context;

    // Fill in default configuration if needed.
    if (!isset($options['prepare']) || $options['prepare'] == TRUE) {
      $configuration = $this->prepareConfiguration($format_type, $context, $configuration);
    }

    $plugin_id = $format_type;

    // Validate if plugin exists and it's allowed for current context.
    $definition = $this->getDefinition($format_type, FALSE);
    if (!isset($definition['class']) || !in_array($context, $definition['supported_contexts'])) {
      return NULL;
    }

    $configuration += array(
      'group' => $options['group'],
    );

    return $this->createInstance($plugin_id, $configuration);
  }

  /**
   * Merges default values for formatter configuration.
   *
   * @param string $format_type
   *   The format type
   * @param string $context
   *   The context to prepare configuration for.
   * @param array $properties
   *   An array of formatter configuration.
   *
   * @return array
   *   The display properties with defaults added.
   */
  public function prepareConfiguration($format_type, $context, array $configuration) {
    // Fill in defaults for missing properties.
    $configuration += array(
      'label' => '',
      'settings' => array(),
    );

    // Fill in default settings values for the formatter.
    $configuration['settings'] += $this->getDefaultSettings($format_type, $context);

    return $configuration;
  }

  /**
   * Returns the default settings of a field_group formatter.
   *
   * @param string $type
   *   A formatter type name.
   * @param string $context
   *   The context to get default values for.
   *
   * @return array
   *   The formatter type's default settings, as provided by the plugin
   *   definition, or an empty array if type or settings are undefined.
   */
  public function getDefaultSettings($type, $context) {
    $plugin_definition = $this->getDefinition($type, FALSE);
    if (!empty($plugin_definition['class'])) {
      $plugin_class = DefaultFactory::getPluginClass($type, $plugin_definition);
      return $plugin_class::defaultContextSettings($context);
    }
    return array();
  }

}
