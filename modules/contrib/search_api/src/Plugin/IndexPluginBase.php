<?php

namespace Drupal\search_api\Plugin;

use Drupal\search_api\IndexInterface;

/**
 * Provides a base class for plugins linked to a search index.
 */
abstract class IndexPluginBase extends ConfigurablePluginBase implements IndexPluginInterface {

  /**
   * The index this processor is configured for.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    // @todo Change key to, e.g., '*index', to avoid potential collisions.
    if (!empty($configuration['index']) && $configuration['index'] instanceof IndexInterface) {
      $this->setIndex($configuration['index']);
      unset($configuration['index']);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndex(IndexInterface $index) {
    $this->index = $index;
  }

}
