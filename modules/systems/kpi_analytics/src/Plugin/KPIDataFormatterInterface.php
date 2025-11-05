<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\block\BlockInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for KPI Data Formatter plugins.
 */
interface KPIDataFormatterInterface extends PluginInspectionInterface {

  /**
   * Format the data.
   *
   * @param array $data
   *   Input array.
   * @param \Drupal\block\BlockInterface|null $block
   *   The 'block' entity.
   *
   * @return array
   *   Output array.
   */
  public function format(array $data, BlockInterface $block);

}
