<?php

namespace Drupal\kpi_analytics;

/**
 * Interface KPIBuilderInterface.
 *
 * @package Drupal\kpi_analytics
 */
interface KPIBuilderInterface {

  /**
   * Lazy builder callback for displaying a kpi analytics.
   *
   * @param string $entity_type_id
   *   Entity type.
   * @param string|int $entity_id
   *   Entity id.
   * @param string|null $block_id
   *   Block id.
   *
   * @return array
   *   A render array for the action link, empty if the user does not have
   *   access.
   */
  public function build(string $entity_type_id, $entity_id, ?string $block_id): array;

}
