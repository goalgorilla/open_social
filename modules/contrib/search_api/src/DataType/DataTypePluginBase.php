<?php

namespace Drupal\search_api\DataType;

use Drupal\Core\Plugin\PluginBase;
use Drupal\search_api\Backend\BackendPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base class from which other data type classes may extend.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_tracker_info_alter(). The definition includes the following
 * keys:
 * - id: The unique, system-wide identifier of the data type class.
 * - label: The human-readable name of the data type class, translated.
 * - description: A human-readable description for the data type class,
 *   translated.
 * - fallback_type: (optional) The fallback data type for this data type. Needs
 *   to be one of the default data types defined in the Search API itself.
 *   Defaults to "text".
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiDataType(
 *   id = "my_data_type",
 *   label = @Translation("My data type"),
 *   description = @Translation("Some information about my data type"),
 *   fallback_type = "string"
 * )
 * @endcode
 *
 * Search API comes with a couple of default data types. These have an extra
 * "default" property in the annotation. It is not allowed for custom data type
 * plugins to set this property.
 *
 * @see \Drupal\search_api\Annotation\SearchApiDataType
 * @see \Drupal\search_api\DataType\DataTypePluginManager
 * @see \Drupal\search_api\DataType\DataTypeInterface
 * @see plugin_api
 */
abstract class DataTypePluginBase extends PluginBase implements DataTypeInterface {

  /**
   * The backend plugin manager.
   *
   * @var \Drupal\search_api\Backend\BackendPluginManager|null
   */
  protected $backendManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $data_type */
    $data_type = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\search_api\Backend\BackendPluginManager $backend_manager */
    $backend_manager = $container->get('plugin.manager.search_api.backend');
    $data_type->setBackendManager($backend_manager);

    return $data_type;
  }

  /**
   * Retrieves the backend plugin manager.
   *
   * @return \Drupal\search_api\Backend\BackendPluginManager
   *   The backend plugin manager.
   */
  public function getBackendManager() {
    return $this->backendManager ?: \Drupal::service('plugin.manager.search_api.backend');
  }

  /**
   * Sets the backend plugin manager.
   *
   * @param \Drupal\search_api\Backend\BackendPluginManager $backend_manager
   *   The backend plugin manager.
   *
   * @return $this
   */
  public function setBackendManager(BackendPluginManager $backend_manager) {
    $this->backendManager = $backend_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackType() {
    return !empty($this->pluginDefinition['fallback_type']) ? $this->pluginDefinition['fallback_type'] : 'text';
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return !empty($this->pluginDefinition['default']);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['description'];
  }

}
