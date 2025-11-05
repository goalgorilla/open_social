<?php

namespace Drupal\kpi_analytics\Plugin;

use Drupal\block_content\BlockContentInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for KPI Datasource plugins.
 */
interface KPIDatasourceInterface extends PluginInspectionInterface {

  /**
   * Query the datasource.
   *
   * @param \Drupal\block_content\BlockContentInterface $entity
   *   The 'block_content' entity.
   * @param \Drupal\block\BlockInterface|null $block
   *   The 'block' entity.
   */
  public function query(BlockContentInterface $entity, $block);

}
