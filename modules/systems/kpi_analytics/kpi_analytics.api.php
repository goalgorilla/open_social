<?php

/**
 * @file
 * Hooks provided by the KPI Analytics module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide a method to alter vids of term for KPI term filter config.
 *
 * @param array $vids
 *   List of taxonomy vocabulary IDs.
 *
 * @see \Drupal\kpi_analytics\Plugin\Block\KPIBlockContentBlock
 */
function hook_kpi_analytics_term_vocabularies_alter(array &$vids) {
  $vids[] = 'topic_types';
}

/**
 * @} End of "addtogroup hooks".
 */
