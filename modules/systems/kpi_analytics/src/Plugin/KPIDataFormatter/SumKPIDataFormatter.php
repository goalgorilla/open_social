<?php

namespace Drupal\kpi_analytics\Plugin\KPIDataFormatter;

use Drupal\kpi_analytics\Plugin\KPIDataFormatterBase;

/**
 * Provides a 'SumKPIDataFormatter' KPI data formatter.
 *
 * @KPIDataFormatter(
 *  id = "sum_kpi_data_formatter",
 *  label = @Translation("Sum KPI data formatter"),
 * )
 */
class SumKPIDataFormatter extends KPIDataFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(array $data, $block = NULL): array {
    $formatted_data = [];

    // Sum the first value if it does not exist already.
    foreach ($data as $value) {
      $key = $value['created'];
      if (isset($formatted_data[$key])) {
        if (!in_array($value['uid'], $formatted_data[$key]['uids'], TRUE)) {
          $formatted_data[$key]['uids'][] = $value['uid'];
        }
      }
      else {
        // Count 1 for the created value.
        $formatted_data[$key] = [
          'created' => $value['created'],
          'uids' => [$value['uid']],
        ];
      }
    }

    $return_formatted_data = [];
    foreach ($formatted_data as $formatted_data_item) {
      $new_item = [];
      $new_item['count'] = count($formatted_data_item['uids']);
      $new_item['created'] = $formatted_data_item['created'];
      $return_formatted_data[] = $new_item;
    }

    return $return_formatted_data;
  }

}
