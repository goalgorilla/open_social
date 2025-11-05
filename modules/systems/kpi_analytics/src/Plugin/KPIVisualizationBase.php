<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for KPI Visualization plugins.
 */
abstract class KPIVisualizationBase extends PluginBase implements KPIVisualizationInterface, ContainerFactoryPluginInterface {

  /**
   * Contains a list with labels for chart.
   */
  protected array $labels = [];

  /**
   * Contains a list with colors for chart.
   */
  protected array $colors = [];

  /**
   * The uuid service.
   */
  protected UuidInterface $uuid;

  /**
   * KPIVisualizationBase constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UuidInterface $uuid) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('uuid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(array $data) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setLabels(array $labels) {
    $this->labels = $labels;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setColors(array $colors) {
    $this->colors = $colors;

    return $this;
  }

}
