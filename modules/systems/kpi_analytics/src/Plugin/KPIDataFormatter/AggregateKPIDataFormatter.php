<?php

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;

/**
 * Provides a 'AggregateKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "aggregate_kpi_data_formatter",
 *  label = @Translation("Aggregate KPI data formatter"),
 * )
 */
class AggregateKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(array $data, $block = NULL): array {
    $formatted_data = [];

    // Combine multiple values in one value.
    foreach ($data as $value) {
      if (isset($value['created'])) {
        $value['created'] = $this->dateFormatter->format($value['created'], '', 'Y-m');
      }
      $formatted_data[] = $value;
    }
    return $formatted_data;
  }

}
